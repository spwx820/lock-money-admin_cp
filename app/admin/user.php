<?php
/**
 * 用户管理
 *
 * @category   Leb
 * @package    Leb_Action
 * @author     lihui
 * @version    $Id: user.php 2014-09-03 9:58:00 lihui
 * @copyright (c) 2014 dianjoy.com
 * @license
 */
class userController extends Application
{
    private $userModel;
    private $configModel;

    public function  execute($plugins)
    {
        $this->userModel = $this->loadAppModel('User');
        $this->configModel = C('global.php');
    }

    public function indexAction()
    {
        $search = daddslashes($this->postVar('search', ''));
        $keyword = daddslashes($this->reqVar('keyword', ''));
        $keyword = addslashes($this->reqVar('keyword', ''));
        $startTime = daddslashes($this->reqVar('start_time', ''));
        $endTime = daddslashes($this->reqVar('end_time', ''));
        $type = (int)$this->reqVar('type', 0);
        $page = (int)$this->reqVar('page', 1);

        $pageUrl = "/admin/user/";
        if (!empty($keyword))
        {
            switch ($type)
            {
                case 1:
                    $userSet['pnum'] = $keyword;
                    break;
                case 2:
                    $userSet['uid'] = $keyword;
                    break;
                case 3:
                    $userSet['device_id'] = $keyword;
                    break;
                case 4:
                    $userSet['register_ip'] = $keyword;
                    break;
                case 5:
                    $userSet['channel'] = $keyword;
                    break;
                default:
                    $userSet['pnum'] = $keyword;
                    break;
            }
            $pageUrl .= "?type=$type&keyword=$keyword";
        }

        if (!empty($startTime))
        {
            $userSet['start_time'] = $startTime;
            $pageUrl .= !empty($keyword) ? '&' : '?';
            $pageUrl .= "start_time=$startTime";
        }

        if (!empty($endTime))
        {
            $userSet['end_time'] = $endTime;
            if (empty($keyword) && empty($startTime))
            {
                $pageUrl .= "?end_time=$endTime";
            } else
            {
                $pageUrl .= "&end_time=$endTime";
            }
        }

        if (!in_array($type, array(4, 5)) && !empty($keyword))
        {
            $userList = $this->userModel->getUserList($userSet, $page, 20);
            $userCount = $this->userModel->getUserCount($userSet);
            $userPages = pages($userCount, $page, 20, $pageUrl, $array = array());
        } elseif (!empty($keyword) && !empty($startTime) && !empty($endTime))
        {
            $userList = $this->userModel->getUserList($userSet, $page, 20);
            $userCount = $this->userModel->getUserCount($userSet);
            $userPages = pages($userCount, $page, 20, $pageUrl, $array = array());
        } else
        {
            $userList = $userPages = '';
        }

        $this->assign('keyword', $keyword);
        $this->assign('selectType', $type);
        $this->assign('startTime', $startTime);
        $this->assign('endTime', $endTime);
        $this->assign('userList', $userList);
        $this->assign("userStatus", $this->configModel['user_status']);
        $this->assign('userPages', $userPages);

        $this->getViewer()->needLayout(false);
        $this->render('user');
    }

    public function addAction()
    {
        $dosubmit = daddslashes($this->postVar('dosubmit', ''));
        $userAdd['pnum'] = daddslashes($this->postVar('pnum', ''));
        $userAdd['password'] = daddslashes($this->postVar('password', ''));
        $userAdd['device_id'] = daddslashes($this->postVar('device_id', ''));

        if (!empty($dosubmit) && !empty($userAdd['pnum']) && !empty($userAdd['password']) && !empty($userAdd['device_id']))
        {
            $userAdd['pnum_md5'] = md5($userAdd['pnum'] . 'dianABCDE5');
            $userAdd['password'] = md5($userAdd['password'] . 'dianABCDEF12');
            $userAdd['status'] = 1;
            $userAdd['channel'] = 'admin';
            $userAdd['ctime'] = date("Y-m-d H:i:s", time());
            //$this->userModel->addUser($userAdd);
            $this->redirect('', '/admin/user/', 0);
        }
        $this->getViewer()->needLayout(false);
        $this->render('user_add');
    }

    public function deleteAction()
    {
        $uidArr = daddslashes($this->postVar('uid', ''));
        if (!empty($uidArr))
        {
            foreach ($uidArr as $key => $val)
            {
                $this->userModel->deleteUser($val);
            }
        }
        $userList = $this->userModel->getUserList();
        $this->assign('userList', $userList);

        $this->getViewer()->needLayout(false);
        $this->render('user');
    }

    public function ajaxpumAction()
    {
        $pnum = daddslashes($this->getVar('pnum', ''));
        if (!empty($pnum))
        {
            $userRe = $this->userModel->getUser(array("pnum" => $pnum));
            if ($userRe)
            {
                exit("0");
            }
        }
        exit("1");
    }

    public function ajaxdeviceidAction()
    {
        $device_id = daddslashes($this->getVar('device_id', ''));
        if (!empty($device_id))
        {
            $userRe = $this->userModel->getUser(array("device_id" => $device_id));
            if ($userRe)
            {
                exit("0");
            }
        }
        exit("1");
    }

    public function auditAction()
    {
        $dosubmit = daddslashes($this->postVar('dosubmit', ''));
        $audit = daddslashes($this->postVar('audit', ''));
        $uid = (int)$this->reqVar('uid', 0);
        if (!empty($uid))
        {
            if (!empty($dosubmit))
            {
                if (1 == $audit)
                {
//                    $this->userModel->playUser($uid);
                } elseif (2 == $audit)
                {
//                    $this->userModel->noPlayUser($uid);
                }
                $this->redirect('', '/admin/user/', 0);
            }
            $userRe = $this->userModel->getUser(array("uid" => $uid));
            $this->assign('pnum', $userRe['pnum']);
            $this->assign('uid', $userRe['uid']);
            $this->assign('audit', $userRe['status']);
        }
        $this->getViewer()->needLayout(false);
        $this->render('user_audit');
    }

    public function detailAction()
    {
        $uid = (int)$this->reqVar('uid', 0);
        $page = (int)$this->reqVar('page', 1);
        if (!empty($uid))
        {
            $userRe = $this->userModel->getUser(array("uid" => $uid));
            $inviteList = $this->userModel->getUserList(array("invite_code" => $uid), $page, 100);
            $inviteCount = $this->userModel->getUserCount(array("invite_code" => $uid));
            $invitePages = pages($inviteCount, $page, 100, '', $array = array());

            $this->assign('userRe', $userRe);
            $this->assign("userStatus", $this->configModel['user_status']);
            $this->assign('inviteList', $inviteList);
            $this->assign('invitePages', $invitePages);
        }
        $this->getViewer()->needLayout(false);
        $this->render('user_detail');
    }



    public function install_listAction()  // 按比例 是否上传应用安装列表
    {
        $rate_c = $_POST["rate"];
        $rate = '';
        $dosubmit = daddslashes($this->postVar('dosubmit', ''));

        if ($dosubmit)
        {
            foreach ($rate_c as $val)
            {
                $rate = $rate . $val;
            }

            $sql = "INSERT INTO z_sys_conf  (sys_key, sys_value, discription) VALUES ('install_list_key', '$rate', '控制按尾号上传应用列表的开关') ON DUPLICATE KEY UPDATE  sys_value = '$rate';";
            $this->userModel->query($sql);
        } else
        {
            $sql = "SELECT sys_value FROM z_sys_conf WHERE sys_key = 'install_list_key'";
            $rate_c = $this->userModel->query($sql);
            $rate = '';
            if(!empty($rate_c))
                $rate = $rate_c[0]['sys_value'];

        }

        $rate_list = array(0, 0, 0, 0, 0, 0, 0, 0, 0);
        for ($ii = 0; $ii < strlen($rate); $ii++)
        {
            $rate_list[intval($rate[$ii])] = 1;
        }

        $this->assign("rate_list", $rate_list);

        $this->getViewer()->needLayout(false);
        $this->render('kub_install_list');

    }


    public function uninstall_rateAction()  // 按比例卸载竞品
    {
        $rate_c = $_POST["rate"];
        $rate = '';
        $dosubmit = daddslashes($this->postVar('dosubmit', ''));

        if ($dosubmit)
        {
            foreach ($rate_c as $val)
            {
                $rate = $rate . $val;
            }

            $sql = "INSERT INTO z_sys_conf  (sys_key, sys_value, discription) VALUES ('install_rate_key', '$rate', '控制按尾号卸载的开关') ON DUPLICATE KEY UPDATE  sys_value = '$rate';";
            $this->userModel->query($sql);
        } else
        {
            $sql = "SELECT sys_value FROM z_sys_conf WHERE sys_key = 'install_rate_key'";
            $rate_c = $this->userModel->query($sql);
            $rate = '';
            if(!empty($rate_c))
                $rate = $rate_c[0]['sys_value'];
        }

        $rate_list = array(0, 0, 0, 0, 0, 0, 0, 0, 0);
        for ($ii = 0; $ii < strlen($rate); $ii++)
        {
            $rate_list[intval($rate[$ii])] = 1;
        }

        $this->assign("rate_list", $rate_list);

        $this->getViewer()->needLayout(false);
        $this->render('kub_uninstall_rate');

    }



    public function lock_rateAction() // 按比例 遮盖 竞品锁屏界面
    {
        $rate_c = $_POST["rate"];
        $rate = '';
        $dosubmit = daddslashes($this->postVar('dosubmit', ''));

        if ($dosubmit)
        {
            foreach ($rate_c as $val)
            {
                $rate = $rate . $val;
            }

            $sql = "INSERT INTO z_sys_conf  (sys_key, sys_value, discription) VALUES ('lock_rate_key', '$rate', '控制按尾号卸载的开关') ON DUPLICATE KEY UPDATE  sys_value = '$rate';";
            $this->userModel->query($sql);
        } else
        {
            $sql = "SELECT sys_value FROM z_sys_conf WHERE sys_key = 'lock_rate_key'";
            $rate_c = $this->userModel->query($sql);
            $rate = '';
            if(!empty($rate_c))
                $rate = $rate_c[0]['sys_value'];
        }

        $rate_list = array(0, 0, 0, 0, 0, 0, 0, 0, 0);
        for ($ii = 0; $ii < strlen($rate); $ii++)
        {
            $rate_list[intval($rate[$ii])] = 1;
        }

        $this->assign("rate_list", $rate_list);

        $this->getViewer()->needLayout(false);
        $this->render('kub_lock_rate');

    }



    public function lock_name_listAction() // 按比例 遮盖 竞品锁屏界面
    {

        $sql = "SELECT sys_key, sys_value FROM z_sys_conf WHERE sys_key like 'kub_screen_name_%'";
        $screen_list = $this->userModel->query($sql);
//        $rate = $rate_c[0]['sys_value'];
        $this->assign("screen_list", $screen_list);

        $this->getViewer()->needLayout(false);
        $this->render('kub_lock_list');

    }



    public function lock_editAction() // 按比例 遮盖 竞品锁屏界面
    {
        $dosubmit = daddslashes($this->postVar('dosubmit', ''));
        $dosubmit = daddslashes($this->postVar('id', ''));


        if ($dosubmit)
        {
            
            $sql = "INSERT INTO z_sys_conf  (sys_key, sys_value, discription) VALUES ('lock_rate_key', '$rate', '控制按尾号卸载的开关') ON DUPLICATE KEY UPDATE  sys_value = '$rate';";
            $this->userModel->query($sql);
        } else
        {
            $sql = "SELECT sys_value FROM z_sys_conf WHERE sys_key = 'lock_rate_key'";
            $rate_c = $this->userModel->query($sql);
            $rate = '';
            if(!empty($rate_c))
                $rate = $rate_c[0]['sys_value'];
        }

        $rate_list = array(0, 0, 0, 0, 0, 0, 0, 0, 0);
        for ($ii = 0; $ii < strlen($rate); $ii++)
        {
            $rate_list[intval($rate[$ii])] = 1;
        }

        $this->assign("rate_list", $rate_list);

        $this->getViewer()->needLayout(false);
        $this->render('kub_lock_rate');

    }




}