<?php
/**
 * 版本管理
 *
 * @category   Leb
 * @package    Leb_Action
 * @author     lihui
 * @version    $Id: version.php 2015-05-20 15:31:00 lihui
 * @copyright (c) 2015 dianjoy.com
 * @license
 */
class versionController extends Application
{
    private $configModel;
    private $operateModel;
    private $versionModel;

    public function  execute($plugins)
    {
        $this->configModel = C('global.php');
        $this->operateModel = $this->loadModel('Operate_log');
        $this->versionModel = $this->loadModel('Version_set');
    }

    public function indexAction()
    {
        $page = (int)$this->reqVar('page', 1);
        $actionOs = daddslashes(trim($this->reqVar('action_os', 'ios')));

        $pageUrl = "/admin/version/";
        $versionSet = array();
        $start = ($page-1) * 20;
        $end = ($page) * 20;

        if (!empty($actionOs))
        {
            $versionSet['os_type'] = $actionOs;
            $pageUrl .= "?action_os=$actionOs";
            $versionList = $this->versionModel->query("SELECT * FROM z_version where status in (0, 1) AND os_type='$actionOs' ORDER BY id DESC LIMIT $start, $end");

        }
        else{
            $versionList = $this->versionModel->query("SELECT * FROM z_version where status in (0, 1) ORDER BY id DESC LIMIT $start, $end");

        }


//        $versionList = $this->versionModel->query("SELECT * FROM z_version where status in (0, 1) ORDER BY id DESC LIMIT $start, $end");
        $versionCount = $this->versionModel->getVersionCount($versionSet);
        $versionPages = pages($versionCount, $page, 20, $pageUrl, array());

        $this->assign('versionList', $versionList);
        $this->assign('versionPages', $versionPages);
        $this->assign("packageOs", $this->configModel['pk_os']);
        $this->assign("publicRadio", $this->configModel['public_radio']);
        $this->assign("publicRadio_force", $this->configModel['public_radio_force']);

        $this->assign("versionStatus", $this->configModel['version_status']);
        $this->assign("actionOs", $actionOs);
        $this->assign('page', $page);
        $this->assign('adminId', UID);

        $this->getViewer()->needLayout(false);
        $this->render('version_list');
    }

    public function addAction()
    {
        $dosubmit = daddslashes($this->postVar('dosubmit', ''));

        $versionAdd['os_type'] = daddslashes(trim($this->postVar('os_type', '')));
        $versionAdd['version'] = daddslashes(trim($this->postVar('version', '')));
        $versionAdd['dl_url'] = daddslashes(trim($this->postVar('dl_url', '')));
        $rate = $_POST["rate"];
        $versionAdd['rate'] = '';

        $versionAdd['what_news'] = daddslashes(trim($this->postVar('what_news', '')));
        $versionAdd['update_is_force'] = (int)$this->postVar('is_force', 0);
        $versionAdd['update_is_recommend'] = (int)$this->postVar('is_recommend', 0);
        if ($dosubmit)
        {

            foreach ($rate as $val)
            {
                $versionAdd['rate'] = $versionAdd['rate'] . $val;
            }

            if (empty($versionAdd['os_type']) || !in_array($versionAdd['os_type'], array('android', 'ios')))
            {
                $this->redirect('请选择系统!', '', 3);
                die();
            } elseif (empty($versionAdd['rate']) and $versionAdd['rate'] != '0')
            {
                $this->redirect('投放比例为空!', '', 3);
                die();
            } elseif (empty($versionAdd['version']))
            {
                $this->redirect('版本不能为空!', '', 3);
                die();
            } else
            {
                $searchSet['os_type'] = $versionAdd['os_type'];
                $searchSet['version'] = $versionAdd['version'];
                $versionRe = $this->versionModel->getVersion($searchSet);
                if (!empty($versionRe))
                {
                    $this->redirect('该版本已存在!', '', 3);
                    die();
                } elseif (empty($versionAdd['dl_url']))
                {
                    $this->redirect('下载地址不能为空!', '', 3);
                    die();
                } elseif (empty($versionAdd['what_news']))
                {
                    $this->redirect('说明不能为空!', '', 3);
                    die();
                }
            }

            if($versionAdd['rate'] == '' or $versionAdd['rate'] == '0123456789')  // 非灰度升级
            {
                $version = $this->versionModel->query("SELECT MAX(version) as m_v FROM z_version WHERE os_type = '{$versionAdd['os_type']}' AND (rate = '0123456789' or rate = '' ) AND status in (0, 1) ORDER BY id DESC LIMIT 1;")[0]['m_v'];

                if( strnatcmp($version, $versionAdd['version']) == 1)
                {
                    $this->redirect("你设置的{$versionAdd['os_type']}全量更新版本{$versionAdd['version']}低于数据库中全量更新的版本{$version}!如需的确需要插入该版本, 请联系开发者.", '', 0);
                    return;
                }
            }
            else
            {
                $version = $this->versionModel->query("SELECT MAX(version) as m_v FROM z_version WHERE os_type = '{$versionAdd['os_type']}' AND (rate != '0123456789' and rate != '' ) AND status in (0, 1) ORDER BY id DESC LIMIT 1;")[0]['m_v'];

                if( strnatcmp($version, $versionAdd['version']) == 1)
                {
                    $this->redirect("你设置的{$versionAdd['os_type']}灰度更新版本{$versionAdd['version']}低于数据库中灰度更新的版本{$version}!如需的确需要插入该版本, 请联系开发者.", '', 0);
                    return;
                }
            }


            //添加版本
            if ($this->versionModel->addVersion($versionAdd))
            {
                $_id = intval($this->versionModel->query("SELECT id FROM z_version ORDER BY id DESC LIMIT 1;")[0]['id']);
                $this->versionModel->execute("UPDATE z_version SET rate = '{$versionAdd['rate']}' WHERE id = $_id;");

                //操作日志记录
                $this->oplog($versionAdd);
                $this->redirect('添加成功!', '/admin/version/', 0);
            }
        }
        $this->assign("packageOs", $this->configModel['pk_os']);
        $this->assign("publicRadio", $this->configModel['public_radio']);
        $this->assign("fromApp", $this->configModel['from_app']);

        $this->getViewer()->needLayout(false);
        $this->render('version_add');
    }

    public function editAction()
    {
        $dosubmit = daddslashes(trim($this->postVar('dosubmit', '')));
        $vId = (int)$this->reqVar('id', 0);
        $page = (int)$this->getVar('page', 1);
        $versionSave['dl_url'] = daddslashes(trim($this->postVar('dl_url', '')));
        $versionSave['what_news'] = daddslashes(trim($this->postVar('what_news', '')));
        $versionSave['update_is_force'] = (int)$this->postVar('is_force', 0);
        $versionSave['update_is_recommend'] = (int)$this->postVar('is_recommend', 0);
        $rate = $_POST["rate"];
        $versionSave['rate'] = '';
        $rate_list = array(0, 0, 0, 0, 0, 0, 0, 0, 0);
        foreach ($rate as $val)
        {
            $rate_list[intval($val)] = 1;
            $versionSave['rate'] = $versionSave['rate'] . $val;
        }
        $rate = $this->getVar('rate', '');
        for ($ii = 0; $ii < strlen($rate); $ii++)
        {
            $rate_list[intval($rate[$ii])] = 1;
        }
        if ($vId > 0)
        {
            $getVersion = $this->versionModel->getVersion(array('id' => $vId));
            if ($getVersion && !empty($dosubmit))
            {

                $versionSave['status'] = 0;
                if (empty($versionSave['dl_url']))
                {
                    $this->redirect('下载地址不能为空!', '', 3);
                    die();
                } elseif (empty($versionSave['what_news']))
                {
                    $this->redirect('说明不能为空!', '', 3);
                    die();
                }

                $this->versionModel->execute("UPDATE z_version SET rate = '{$versionSave['rate']}',
                                                                    dl_url = '{$versionSave['dl_url']}',
                                                                    what_news = '{$versionSave['what_news']}',
                                                                    update_is_force = '{$versionSave['update_is_force']}',
                                                                    update_is_recommend = '{$versionSave['update_is_recommend']}'
                                                                    WHERE id = $vId;");

                //操作日志记录
                $logAdd = $versionSave;
                $logAdd['id'] = $vId;
                $this->oplog($logAdd);
                $this->redirect('修改成功!', '/admin/version/?page=' . $page, 1);
            }
            $getVersion['rate'] = intval($getVersion['rate']) * 10;
            $this->assign('getVersion', $getVersion);
        }


        $this->assign("rate_list", $rate_list);
        $this->assign("packageOs", $this->configModel['pk_os']);
        $this->assign("publicRadio", $this->configModel['public_radio']);

        $this->getViewer()->needLayout(false);
        $this->render('version_edit');
    }

    public function auditAction()
    {
        $vId = daddslashes(trim($this->getVar('id', 0)));
        $page = (int)$this->getVar('page', 1);
        $getVersion = $this->versionModel->getVersion(array('id' => $vId));
        if ($getVersion)
        {
            $this->versionModel->auditVersion($vId);

            //操作日志记录
            $logAdd['id'] = $vId;
            $this->oplog($logAdd);
        }
        $this->redirect('', '/admin/version/?page=' . $page, 0);
    }


    public function stopAction()
    {
        $vId = daddslashes(trim($this->getVar('id', 0)));
        $page = (int)$this->getVar('page', 1);
//        $getVersion = $this->versionModel->getVersion(array('id' => $vId));
//        if ($getVersion)
//        {
        $this->versionModel->execute("UPDATE z_version SET status = 0 WHERE id = $vId;");

        //操作日志记录
        $logAdd['id'] = $vId;
        $this->oplog($logAdd);
//        }
        $this->redirect('', '/admin/version/?page=' . $page, 0);
    }


    private function ajaxVersion($pkVersion)
    {
        if (!empty($pkVersion))
        {
            $packageSet['version'] = $pkVersion;
            $getVersion = $this->versionModel->getVersion($packageSet);
            if (!$getVersion)
            {
                exit("1");
            }
        }
        exit("0");
    }

    private function oplog($addContent)
    {
        if (empty($addContent))
        {
            return false;
        }

        //操作日志记录
        $logAdd['app'] = $this->_application;
        $logAdd['controller'] = $this->_controller;
        $logAdd['action'] = $this->_action;
        $logAdd['content'] = json_encode($addContent);
        $logAdd['ip'] = get_real_ip();
        $logAdd['operat'] = UNAME;
        $this->operateModel->addOpLog($logAdd);
    }

    public function ios_infoAction()
    {
        $bundle_identifier = daddslashes(trim($this->postVar('bundle_identifier', '')));
        $ios_version = daddslashes(trim($this->postVar('ios_version', '')));
        $update_time =  daddslashes(trim($this->postVar('update_time', date('Y-m-d', time()))));
        $dosubmit = daddslashes(trim($this->postVar('dosubmit', '')));

        $bundle_identifier_rq = daddslashes(trim($this->reqVar('bundle_identifier', '')));
        $ios_version_rq = daddslashes(trim($this->reqVar('ios_version', '')));
        $dosubmit_rq = daddslashes(trim($this->reqVar('dosubmit', '')));
        $plist = daddslashes(trim($this->reqVar('plist', '')));
//        plist=
        if(!empty($bundle_identifier_rq))
        {
            $bundle_identifier = $bundle_identifier_rq;
        }
        if(!empty($ios_version_rq))
        {
            $ios_version = $ios_version_rq;
        }
        if(!empty($dosubmit_rq))
        {
            $dosubmit = $dosubmit_rq;
        }

        if($dosubmit)
        {

            if($plist == "mihenzhongyao") // 灾备plist覆盖线上plist, 千万要注意
            {
                exec("sh ../app/tools/plist_bak.sh zaibei", $info);
                $this->oplog(array("zaibei" => "xianshang"));

                $sql = "INSERT INTO z_sys_conf  (sys_key, sys_value, discription) VALUES ('bundle_identifier', '$bundle_identifier', '') ON DUPLICATE KEY UPDATE  sys_value = '$bundle_identifier';";
                $this->versionModel->query($sql);
                $sql = "INSERT INTO z_sys_conf  (sys_key, sys_value, discription) VALUES ('ios_version', '$ios_version', '') ON DUPLICATE KEY UPDATE  sys_value = '$ios_version';";
                $this->versionModel->query($sql);
                $sql = "INSERT INTO z_sys_conf  (sys_key, sys_value, discription) VALUES ('ios_update_time', '$update_time', '') ON DUPLICATE KEY UPDATE  sys_value = '$update_time';";
                $this->versionModel->query($sql);

                $this->oplog(array("$bundle_identifier" => "$ios_version"));

               $this->redirect("$info[0]" . "<br>" . "plist文件覆盖: $info[1]", "/admin/version/ios_info_zaibei", "100");

            }

            $sql = "INSERT INTO z_sys_conf  (sys_key, sys_value, discription) VALUES ('bundle_identifier', '$bundle_identifier', '') ON DUPLICATE KEY UPDATE  sys_value = '$bundle_identifier';";
            $this->versionModel->query($sql);
            $sql = "INSERT INTO z_sys_conf  (sys_key, sys_value, discription) VALUES ('ios_version', '$ios_version', '') ON DUPLICATE KEY UPDATE  sys_value = '$ios_version';";
            $this->versionModel->query($sql);
            $sql = "INSERT INTO z_sys_conf  (sys_key, sys_value, discription) VALUES ('ios_update_time', '$update_time', '') ON DUPLICATE KEY UPDATE  sys_value = '$update_time';";
            $this->versionModel->query($sql);

            $this->oplog(array("$bundle_identifier" => "$ios_version"));

            $sql = "SELECT operat, operatetime FROM a_operate_log WHERE action = 'ios_info' ORDER BY id DESC LIMIT 1";
            $res = $this->versionModel->query($sql)[0];
            $usr = $res["operat"];
            $operate_time = $res["operatetime"];

        }
        else{
            $sql = "SELECT sys_value FROM z_sys_conf WHERE sys_key = 'bundle_identifier'";
            $bundle_identifier = $this->versionModel->query($sql)[0]['sys_value'];
            $sql = "SELECT sys_value FROM z_sys_conf WHERE sys_key = 'ios_version'";
            $ios_version = $this->versionModel->query($sql)[0]['sys_value'];
            $sql = "SELECT sys_value FROM z_sys_conf WHERE sys_key = 'ios_update_time'";
            $update_time = $this->versionModel->query($sql)[0]['sys_value'];
            $sql = "SELECT operat, operatetime FROM a_operate_log WHERE action = 'ios_info' ORDER BY id DESC LIMIT 1";
            $res = $this->versionModel->query($sql)[0];
            $usr = $res["operat"];
            $operate_time = $res["operatetime"];
        }

        $this->assign("bundle_identifier", $bundle_identifier);
        $this->assign("ios_version", $ios_version);
        $this->assign("update_time", $update_time);
        $this->assign("operate_time", $operate_time);
        $this->assign("usr", $usr);

        $this->getViewer()->needLayout(false);
        $this->render('ios_info_edit');
    }


    public function ios_info_zaibeiAction()
    {
        $bundle_identifier = daddslashes(trim($this->postVar('bundle_identifier', '')));
        $ios_version = daddslashes(trim($this->postVar('ios_version', '')));
        $update_time =  daddslashes(trim($this->postVar('update_time', '')));

        $dosubmit = daddslashes(trim($this->postVar('dosubmit', '')));

        if($dosubmit)
        {

            $sql = "INSERT INTO z_sys_conf  (sys_key, sys_value, discription) VALUES ('bundle_identifier_zaibei', '$bundle_identifier', '') ON DUPLICATE KEY UPDATE  sys_value = '$bundle_identifier';";
            $this->versionModel->query($sql);
            $sql = "INSERT INTO z_sys_conf  (sys_key, sys_value, discription) VALUES ('ios_version_zaibei', '$ios_version', '') ON DUPLICATE KEY UPDATE  sys_value = '$ios_version';";
            $this->versionModel->query($sql);
            $sql = "INSERT INTO z_sys_conf  (sys_key, sys_value, discription) VALUES ('ios_update_time_zaibei', '$update_time', '') ON DUPLICATE KEY UPDATE  sys_value = '$update_time';";
            $this->versionModel->query($sql);

//            $usr = UNAME;
//            $sql = "INSERT INTO z_sys_conf  (sys_key, sys_value, discription) VALUES ('ios_info_operator_zaibei', '$usr', '') ON DUPLICATE KEY UPDATE  sys_value = '$usr';";
//            $this->versionModel->query($sql);
            $this->oplog(array("$bundle_identifier" => "$ios_version"));
            $sql = "SELECT operat, operatetime FROM a_operate_log WHERE action = 'ios_info_zaibei' ORDER BY id DESC LIMIT 1";
            $res = $this->versionModel->query($sql)[0];
            $usr = $res["operat"];
            $operate_time = $res["operatetime"];

            $sql = 'SELECT operat, operatetime FROM a_operate_log WHERE content = \'{"zaibei":"xianshang"}\' ORDER BY id DESC LIMIT 1';
            $res = $this->versionModel->query($sql)[0];
            $usr_c = $res["operat"];
            $operate_time_c = $res["operatetime"];

        }
        else{
            $sql = "SELECT sys_value FROM z_sys_conf WHERE sys_key = 'bundle_identifier_zaibei'";
            $bundle_identifier = $this->versionModel->query($sql)[0]['sys_value'];
            $sql = "SELECT sys_value FROM z_sys_conf WHERE sys_key = 'ios_version_zaibei'";
            $ios_version = $this->versionModel->query($sql)[0]['sys_value'];
            $sql = "SELECT sys_value FROM z_sys_conf WHERE sys_key = 'ios_update_time_zaibei'";
            $update_time = $this->versionModel->query($sql)[0]['sys_value'];

            $sql = "SELECT operat, operatetime FROM a_operate_log WHERE action = 'ios_info_zaibei' ORDER BY id DESC LIMIT 1";
            $res = $this->versionModel->query($sql)[0];
            $usr = $res["operat"];
            $operate_time = $res["operatetime"];

            $sql = 'SELECT operat, operatetime FROM a_operate_log WHERE content = \'{"zaibei":"xianshang"}\' ORDER BY id DESC LIMIT 1';
            $res = $this->versionModel->query($sql)[0];
            $usr_c = $res["operat"];
            $operate_time_c = $res["operatetime"];

        }

        $this->assign("bundle_identifier", $bundle_identifier);
        $this->assign("ios_version", $ios_version);
        $this->assign("update_time", $update_time);
        $this->assign("usr", $usr);
        $this->assign("operate_time", $operate_time);
        $this->assign("usr_c", $usr_c);
        $this->assign("operate_time_c", $operate_time_c);

        $this->getViewer()->needLayout(false);
        $this->render('ios_info_zaibei_edit');
    }


    public function android_infoAction()
    {
        $android_version = daddslashes(trim($this->postVar('android_version', '')));
        $update_time =  daddslashes(trim($this->postVar('update_time', '')));


        $dosubmit = daddslashes(trim($this->postVar('dosubmit', '')));

        if($dosubmit)
        {

            $sql = "INSERT INTO z_sys_conf  (sys_key, sys_value, discription) VALUES ('android_version', '$android_version', '') ON DUPLICATE KEY UPDATE  sys_value = '$android_version';";
            $this->versionModel->query($sql);
            $sql = "INSERT INTO z_sys_conf  (sys_key, sys_value, discription) VALUES ('android_update_time', '$update_time', '') ON DUPLICATE KEY UPDATE  sys_value = '$update_time';";
            $this->versionModel->query($sql);

//            $usr = UNAME;
//            $sql = "INSERT INTO z_sys_conf  (sys_key, sys_value, discription) VALUES ('android_info_operator', '$usr', '') ON DUPLICATE KEY UPDATE  sys_value = '$usr';";
//            $this->versionModel->query($sql);
            $this->oplog(array("$update_time" => "$android_version"));
            $sql = "SELECT operat, operatetime FROM a_operate_log WHERE action = 'android_info' ORDER BY id DESC LIMIT 1";
            $res = $this->versionModel->query($sql)[0];
            $usr = $res["operat"];
            $operate_time = $res["operatetime"];

        }
        else{

            $sql = "SELECT sys_value FROM z_sys_conf WHERE sys_key = 'android_version'";
            $android_version = $this->versionModel->query($sql)[0]['sys_value'];
            $sql = "SELECT sys_value FROM z_sys_conf WHERE sys_key = 'android_update_time'";
            $update_time = $this->versionModel->query($sql)[0]['sys_value'];

            $sql = "SELECT operat, operatetime FROM a_operate_log WHERE action = 'android_info' ORDER BY id DESC LIMIT 1";
            $res = $this->versionModel->query($sql)[0];
            $usr = $res["operat"];
            $operate_time = $res["operatetime"];
        }



        $this->assign("android_version", $android_version);
        $this->assign("update_time", $update_time);
        $this->assign("usr", $usr);
        $this->assign("operate_time", $operate_time);

        $this->getViewer()->needLayout(false);
        $this->render('android_info_edit');
    }

}