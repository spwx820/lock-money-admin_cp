<?php
/**
 * 后台打包管理
 *
 * @category   Leb
 * @package    Leb_Action
 * @author     lihui
 * @version    $Id: package.php 2014-09-30 10:58:00 lihui
 * @copyright (c) 2014 dianjoy.com
 * @license
 */
class packageController extends Application
{
    private $versionModel;
    private $packageModel;
    private $userModel;
    private $inviteCountModel;
    private $channelCountModel;
    private $configModel;
    private $channelSetModel;
    private $operateLogModel;
    private $userClient;
    private $transport;

    public function execute($plugins)
    {
        $this->versionModel = $this->loadAppModel('Version_set');
        $this->packageModel = $this->loadModel('Package');
        $this->userModel = $this->loadAppModel('User');
        $this->inviteCountModel = $this->loadAppModel('Invite_count');
        $this->channelCountModel = $this->loadAppModel('Channel_count');
        $this->configModel = C('global.php');
        $this->channelSetModel = $this->loadModel('Channel_set');
        $this->operateLogModel = $this->loadModel('Operate_log', array(), 'admin');

        $GLOBALS['THRIFT_ROOT'] = '../thriftlib';
        require_once($GLOBALS['THRIFT_ROOT'] . '/Thrift.php');
        require_once($GLOBALS['THRIFT_ROOT'] . '/transport/TSocket.php');
        require_once($GLOBALS['THRIFT_ROOT'] . '/transport/TBufferedTransport.php');
        require_once($GLOBALS['THRIFT_ROOT'] . '/protocol/TBinaryProtocol.php');
        require_once($GLOBALS['THRIFT_ROOT'] . '/packages/user_service/UserService.php');

        //包含thrift客户端库文件
        $socket = new TSocket(_PUSH_ANDROID_TSOCKET_USER, 9091);
        $this->transport = new TBufferedTransport($socket, 1024, 1024);
        $protocol = new TBinaryProtocol($this->transport);
        $this->userClient = new UserServiceClient($protocol);
    }

    public function indexAction()
    {
        $search = daddslashes($this->postVar('search', ''));
        $keyword = daddslashes($this->reqVar('keyword', ''));
        $startTime = daddslashes($this->reqVar('start_time', ''));
        $endTime = daddslashes($this->reqVar('end_time', ''));
        $actionOs = (int)$this->reqVar('action_os', 0);
        $type = (int)$this->reqVar('type', 0);
        $succ = (int)$this->reqVar('succ', 0);

        $page = (int)$this->reqVar('page', 1);

        $pageUrl = "/admin/package/";
        if (!empty($keyword))
        {
            if (1 == $type)
            {
                $packageSet['creater'] = $keyword;
            } elseif (2 == $type)
            {
                $packageSet['uid'] = $keyword;
            } elseif (3 == $type)
            {
                $packageSet['channel'] = $keyword;
            }
            $pageUrl .= "?type=$type&keyword=$keyword";
        }

        if (!empty($succ))
        {
            if(intval($succ) != 0 and intval($succ) != 1)
            {
                if(intval($succ) == 2)
                    $succ1 = 1;
                if(intval($succ) == 3)
                    $succ1 = 2;
                if(intval($succ) == 4)
                    $succ1 = -2;
                if(intval($succ) == 5)
                    $succ1 = -1;
                if(intval($succ) == 6)
                    $succ1 = -3;
                $packageSet['STATUS'] = $succ1;
                $pageUrl .= !empty($keyword) ? "&" : "?";
                $pageUrl .= "succ=$succ";
            }
        }
        if (!empty($actionOs))
        {
            $packageSet['pk_os'] = $actionOs;
            $pageUrl .= !(empty($keyword) and empty($succ)) ? "&" : "?";
            $pageUrl .= "&action_os=$actionOs";
        }

        $whereStr = "1";
        if (!empty($startTime))
        {
            $packageSet['start_time'] = $startTime;
            $whereStr .= " AND cdate >= '$startTime'";
            if (empty($keyword) && empty($actionOs))
            {
                $pageUrl .= "?start_time=$startTime";
            } else
            {
                $pageUrl .= "&start_time=$startTime";
            }
        }
        if (!empty($endTime))
        {
            $packageSet['end_time'] = $endTime;
            $whereStr .= " AND cdate <= '$endTime'";
            if (empty($keyword) && empty($actionOs) && empty($startTime))
            {
                $pageUrl .= "?end_time=$endTime";
            } else
            {
                $pageUrl .= "&end_time=$endTime";
            }
        }
        $packageList = $this->packageModel->getPackageList($packageSet, $page, 20);
        if ($packageList)
        {
            foreach ($packageList as $key => $val)
            {
                if (!empty($val['uid']))
                {
                    $pkWhereStr = $whereStr . " AND uid='{$val['uid']}'";
                    $sumRe = $this->inviteCountModel->query("SELECT SUM(num) as c_num FROM t_invite_count WHERE $pkWhereStr LIMIT 1");
                    $packageList[$key]['sum_num'] = (int)$sumRe[0]['c_num'];

                    $shareSumRe = $this->inviteCountModel->query("SELECT SUM(share_num) as s_num FROM t_invite_count WHERE $pkWhereStr LIMIT 1");
                    $packageList[$key]['share_num'] = (int)$shareSumRe[0]['s_num'];
                } else
                {
                    $pkWhereStr = $whereStr . " AND channel='{$val['channel']}'";
                    $sumRe = $this->channelCountModel->query("SELECT SUM(user_num) as c_num FROM t_channel_count WHERE $pkWhereStr LIMIT 1");
                    $packageList[$key]['sum_num'] = (int)$sumRe[0]['c_num'];
                    $packageList[$key]['share_num'] = 0;
                }

                //判断是否隐藏邀请码
                $plistUrl = 'https://www.hongbaosuoping.com/plist/';
                $val['channel'] = !empty($val['channel']) ? trim($val['channel']) : '';
                if (1 == $val['is_hidden_invite'] && !empty($val['uid']))
                {
                    $packageList[$key]['invite'] = 'c_' . $val['uid'];
                    $packageList[$key]['plist_url'] = $plistUrl . $this->pakName(trim( $val['channel']), $val['uid'], 'c') . '.plist';
                    $packageList[$key]['c'] = 'c';
                } elseif (1 == $val['is_hidden_invite'])
                {
                    $packageList[$key]['invite'] = 'c';
                    $packageList[$key]['plist_url'] = $plistUrl . $this->pakName(trim( $val['channel']), $val['uid'], 'c') . '.plist';
                    $packageList[$key]['c'] = 'c';
                } elseif (!empty($val['uid']))
                {
                    $packageList[$key]['invite'] = $val['uid'];
                    $packageList[$key]['plist_url'] = $plistUrl . $this->pakName(trim( $val['channel']), $val['uid']) . '.plist';
                    $packageList[$key]['c'] = '';
                } else
                {
                    $packageList[$key]['invite'] = '';
                    $packageList[$key]['plist_url'] = $plistUrl . $this->pakName(trim( $val['channel']), $val['uid']) . '.plist';
                    $packageList[$key]['c'] = '';
                }
            }
        }
        $packageCount = $this->packageModel->getPackageCount($packageSet);
        $packagePages = pages($packageCount, $page, 20, $pageUrl, array());

        $this->assign('startTime', $startTime);
        $this->assign('endTime', $endTime);
        $this->assign('keyword', $keyword);
        $this->assign('packageList', $packageList);
        $this->assign("publicRadio", $this->configModel['public_radio']);
        $this->assign("pkOs", $this->configModel['pk_os']);
        $this->assign("actionOs", $actionOs);
        $this->assign("page", $page);
        $this->assign('packagePages', $packagePages);
        $this->assign('apiUrl', _API_URL_);
        $this->assign('adminId', UID);
        $this->assign('succ', $succ);

        $this->getViewer()->needLayout(false);
        $this->render('package_list');
    }

    public function addAction()
    {
        $dosubmit = daddslashes($this->postVar('dosubmit', ''));
        $uid = (int)$this->postVar('uid', 0);
        $pkOs = (int)$this->postVar('pk_os', 0);
        $channelId = (int)$this->postVar('channel_id', 0);
        $isHiddenInvite = (int)$this->postVar('is_hidden_invite', 0);
        if ($dosubmit)
        {

            if (empty($channelId))
            {
                $this->redirect('渠道号不能为空!', '', 3);
            } elseif (!empty($uid))
            {
                //Thrift连接

                try
                {
                    $this->transport->open();
                } catch (Exception $e)
                {
                    var_dump($e);
                }

                $tp = $this->transport->isOpen();
                if (!$tp)
                {
                    $this->redirect('获取用户信息服务无法连接!', '', 5);
                }

                //验证用户
                $isUser = $this->userClient->existUser($uid);
                if (empty($isUser))
                {
                    $this->redirect('该邀请码不存在!', '', 3);
                }

//                //备份表验证暂停使用
//                $userSet['uid'] = $uid;
//                $userRe = $this->userModel->getUser($userSet);
//                if(!$userRe){
//                    $this->redirect('该邀请码不存在!', '', 3);
//                }
            } elseif (empty($pkOs))
            {
                $this->redirect('请选择系统!', '', 3);
            }


            $packageSet['uid'] = $uid;
            $packageSet['channel_id'] = $channelId;
            $packageSet['pk_os'] = $pkOs;
            $packageSet['is_hidden_invite'] = $isHiddenInvite;
            $packageRe = $this->packageModel->getPackage($packageSet);
            if ($packageRe)
            {
                $this->redirect('该打包记录已存在!', '', 3);
                die();
            }
            if (!empty($channelId))
            {
                $channelRe = $this->channelSetModel->getChannelSet(array('id' => $channelId));
                $channelRe['channel'] = !empty($channelRe['channel']) ? trim($channelRe['channel']) : '';
                if ($channelRe)
                {
                    $packageAdd['channel_id'] = $channelRe['id'];
                    $packageAdd['channel'] = trim($channelRe['channel']);
                }
            }

            $packageAdd['uid'] = $uid;
            $packageAdd['pk_os'] = $pkOs;
            $packageAdd['is_hidden_invite'] = $isHiddenInvite;
            $packageAdd['creater'] = UNAME;
            $backId = $this->packageModel->addPackage($packageAdd);


            if ($backId)
            {
                //判断默认打包
                if (2 == $pkOs)
                {
                    if (1 == $isHiddenInvite)
                        $re = $this->pakIos($channelRe['channel'], $uid, 'c');
                    else
                        $re = $this->pakIos($channelRe['channel'], $uid, '');

                    if (!empty($re['status']) && -1 == $re['status'] && !empty($re['error']))
                    {
                        $this->redirect($re['error'], '', 3);
                    }
                } else
                {
                    //判断是否隐藏邀请码
                    if (1 == $isHiddenInvite && !empty($uid))
                    {
                        $invite = 'c_' . $uid;
                    } elseif (1 == $isHiddenInvite)
                    {
                        $invite = 'c';
                    } elseif (!empty($uid))
                    {
                        $invite = $uid;
                    } else
                    {
                        $invite = '';
                    }
                    $pkurl = _API_URL_ . "/admin_invite_pk.do?ispak=1&uid={$invite}&channel={$channelRe['channel']}";


                    $this->curlGet($pkurl);
                }

                $this->redirect('', '/admin/package/', 0);
                die();
            }
        }

        $channelSet['status'] = 1;
        $channelSelect = $this->channelSetModel->getChannelSetList($channelSet, 1, 1000);
        $this->assign('channelSelect', $channelSelect);

        $this->assign("pkOs", $this->configModel['pk_os']);
        $this->getViewer()->needLayout(false);
        $this->render('package_add');
    }

    public function ajaxuserAction()
    {
        $uid = (int)$this->getVar('uid', 0);
        if (!empty($uid))
        {
            //Thrift连接
            $this->transport->open();
            $tp = $this->transport->isOpen();
            if ($tp)
            {
                //验证用户
                $isUser = $this->userClient->existUser($uid);
                if (!empty($isUser))
                {
                    exit("1");
                }
            }

//            //备份表验证暂停使用
//            $userRe = $this->userModel->getUser(array("uid"=>$uid));
//            if($userRe){
//                exit("1");
//            }
        }
        exit("0");
    }

    public function countAction()
    {
        $page = (int)$this->reqVar('page', 1);
        $packageSet['id'] = (int)$this->reqVar('id', 0);
        $packageRe = $this->packageModel->getPackage($packageSet);
        if (!empty($packageRe['uid']))
        {
            $inviteSet['uid'] = $packageRe['uid'];
            $inviteSet['orderby'] = "cdate desc";
            $inviteList = $this->inviteCountModel->getInviteCountList($inviteSet, $page, 60);
            $inviteCount = $this->inviteCountModel->getInviteCountC($inviteSet);
            $invitePages = pages($inviteCount, $page, 60, '', $array = array());

            $this->assign('tag', "邀请码：{$packageRe['uid']}");
            $this->assign('inviteList', $inviteList);
            $this->assign('cPages', $invitePages);
        } elseif (!empty($packageRe['channel']))
        {
            $channelCountModel = $this->loadAppModel('Channel_count');

            $channelSet['channel'] = $packageRe['channel'];
            $channelSet['orderby'] = "cdate desc";
            $channelList = $channelCountModel->getChannelCountList($channelSet, $page, 60);
            $channelCount = $channelCountModel->getChannelCountC($channelSet);
            $channelPages = pages($channelCount, $page, 60, '', $array = array());

            $this->assign('tag', "渠道号：{$packageRe['channel']}");
            $this->assign('channelList', $channelList);
            $this->assign('cPages', $channelPages);
        }

        if (!empty($packageRe['channel']))
        {
            //统计外部URL
            $startDate = date("Y-m-d", time() - 604800);
            $endDate = date("Y-m-d", time());
            $passwd = md5("dianjoy" . trim($packageRe['channel']));

            $outUrl = "/api/channel/";
            $outUrl .= "?channel={$packageRe['channel']}&passwd={$passwd}&start_date={$startDate}&end_date={$endDate}";
            $this->assign('outUrl', $outUrl);
        }

        $this->getViewer()->needLayout(false);

        $this->assign('packageRe', $packageRe);
        $this->render('package_count');
    }

    public function detailAction()
    {
        $dateNow = date("Y-m-d H:i:s", time());
        $page = (int)$this->reqVar('page', 1);
        $packageSet['id'] = (int)$this->reqVar('id', 0);
        $packageRe = $this->packageModel->getPackage($packageSet);
        if (!empty($packageRe))
        {
            $tag = '';
            $userSet['end_time'] = $dateNow;
            if (!empty($packageRe['uid']))
            {
                $userSet['invite_code'] = $packageRe['uid'];
                $tag = "邀请码：" . $packageRe['uid'];
                $this->assign('tag', $tag);
            } elseif (!empty($packageRe['channel_id']))
            {
                $userSet['channel'] = $packageRe['channel_id'];
                $tag = " 渠道号：" . $packageRe['channel'];
                $this->assign('tag', $tag);
            }

            if (in_array($packageRe['pk_os'], array(1, 2)) && (!empty($packageRe['uid']) || !empty($packageRe['channel_id'])))
            {
                if (2 == $packageRe['pk_os'])
                {
                    $userSet['os_type'] = 'ios';
                } else
                {
                    $userSet['os_type'] = 'android';
                }
                $inviteList = $this->userModel->getUserList($userSet, $page, 50);
                $inviteCount = $this->userModel->getUserCount($userSet);
                $invitePages = pages($inviteCount, $page, 50, '', $array = array());

                $this->assign("userStatus", $this->configModel['user_status']);
                $this->assign('inviteList', $inviteList);
                $this->assign('invitePages', $invitePages);
            }
        }

        $this->assign('page', $page);
        $this->assign('packageRe', $packageRe);
        $this->getViewer()->needLayout(false);
        $this->render('package_detail');
    }

    public function excelAction()
    {
        $startTime = daddslashes($this->getVar('start_time', ''));
        $endTime = daddslashes($this->getVar('end_time', ''));
        $creater = daddslashes($this->getVar('creater', ''));
        $actionOs = (int)$this->getVar('action_os', '');
        if (empty($startTime) || empty($endTime))
        {
            $this->redirect('导出失败,请选择时间!', '', 1);
            die();
        }
        $excelContent = $this->packageExcelTemplate($startTime, $endTime, $creater, $actionOs);
        if (empty($excelContent))
        {
            $this->redirect('导出失败,无法获取内容!', '', 1);
            die();
        }
        $excelData = iconv('utf-8', 'gbk', $excelContent);
        header('Content-type:application/vnd.ms-excel;charset=gbk');
        header("Content-Disposition:filename=" . iconv('utf-8', 'gbk', '打包记录') . ".csv");
        echo $excelData;
    }

    private function packageExcelTemplate($startTime, $endTime, $creater, $actionOs)
    {
        $whereListStr = $whereStr = "1";
        if (!empty($startTime))
        {
            $whereListStr .= " AND createtime >= '$startTime 00:00:00'";
            $whereStr .= " AND cdate >= '$startTime'";

        }
        if (!empty($endTime))
        {
            $whereListStr .= " AND createtime <= '$endTime 23:59:59'";
            $whereStr .= " AND cdate <= '$endTime'";
        }
        if (!empty($creater))
        {
            $whereListStr .= " AND creater = '$creater'";
        }
        if (!empty($actionOs))
        {
            $whereListStr .= " AND pk_os = '$actionOs'";
        }

        $packageList = $this->packageModel->query("SELECT * FROM a_package_log WHERE $whereListStr ORDER BY id DESC");
        if (!$packageList) return;

        $excelContent = "邀请码,渠道号,安装量,隐藏邀请码,系统,操作人,创建时间\r\n";
        foreach ($packageList as $key => $val)
        {
            $whereStrSql = $whereStr . " AND uid='{$val['uid']}'";
            $sumRe = $this->inviteCountModel->query("SELECT SUM(num) as c_num FROM t_invite_count WHERE $whereStrSql LIMIT 1");
            if (1 == $val['is_hidden_invite'])
            {
                $isHidden = "是";
            } else
            {
                $isHidden = "否";
            }
            if (!empty($val['pk_os']))
            {
                $pkOS = $this->configModel['pk_os'][$val['pk_os']];
            } else
            {
                $pkOS = '';
            }
            $excelContent .= $val['uid'] . ',' . $val['channel'] . ',' . (int)$sumRe[0]['c_num'] . ',' . $isHidden . ',' . $pkOS . ',' . $val['creater'] . ',' . $val['createtime'] . "\r\n";
        }
        return $excelContent;
    }

    //删除操作
    public function delAction()
    {
        $pidArr = daddslashes($this->postVar('pid', ''));
        if (!empty($pidArr))
        {
            $delArr = array();
            foreach ($pidArr as $key => $val)
            {
                $re = $this->packageModel->deletePackage($val);
                if ($re)
                {
                    $delArr[] = $val;
                }
            }
            if ($delArr)
            {
                $logAdd['app'] = $this->_application;
                $logAdd['controller'] = $this->_controller;
                $logAdd['action'] = $this->_action;
                $logAdd['content'] = json_encode($delArr);
                $logAdd['ip'] = get_real_ip();
                $logAdd['operat'] = UNAME;
                $this->operateLogModel->addOpLog($logAdd);
            }
        }
        $this->redirect('', '/admin/package/', 0);
    }

    //ios打包操作
    public function pakAction()
    {
        $channel = daddslashes(trim($this->reqVar('channel', '')));
        $invite = (int)$this->reqVar('invite', 0);
        $c = daddslashes(trim($this->reqVar('c', '')));
        $page = (int)$this->reqVar('page', '1');


        $pakRe = $this->pakIos($channel, $invite, $c);
        if (1 == $pakRe['status'])
        {
            $this->redirect('打包成功', '/admin/package/?page=' . $page, 0);

        } else
        {
            $this->redirect($pakRe['error'], '/admin/package/?page=' . $page, 0);
        }
    }


    private function pakIos($channel, $invite, $c)
    {
        if (!empty($channel))
        {
            $path = "../package/";
            if (!file_exists($path))
            {
                //mkdir("$path", 0700);
            }
            $ipaUrl = _PAK_IOS_IP_ . "/pack.php?channel=$channel&c=$c";
            if (!empty($invite))
            {
                $ipaUrl .= "&code=$invite";
            }

//            $versionRe = $this->versionModel->getIosVersion();
            $sql = "SELECT sys_value from z_sys_conf WHERE sys_key = 'ios_version_zaibei'";
            $versionRe['version'] = $this->versionModel->query($sql)[0]['sys_value'];

            if (empty($versionRe['version']))
            {
                return array('status' => -1, 'error' => '版本号无法获取');
            }
            $ipaName = $this->pakName($channel, $invite, $c) . "_" . trim($versionRe['version']) . ".ipa";
            $plistName = $this->pakName($channel, $invite, $c) . ".plist";

            //生成plist文件
            $plistContent = $this->iosTem($ipaName, trim($versionRe['version']));

            $isWrite = $this->wPlistfile($plistName, $plistContent);

            if (!$isWrite)
            {
                return array('status' => -2, 'error' => 'plist文件生成失败');
            }
            if (file_exists($path . $ipaName))
            {
                @unlink($path . $ipaName);
            }

            //生成ipa包
            $packName = $path . $ipaName;
            $headerFile = 'headerFile';
            exec("wget --server-response -O $packName  -o $headerFile '$ipaUrl'"); // 获取ipa包

            $file_size = $this->getContentLengthFromHeaderFile($headerFile);


            if ((file_exists($path . $ipaName) and filesize($path . $ipaName) > 4000000 and filesize($path . $ipaName) < 9000000))  // 更新数据库的包状态
            {
                $sql = "UPDATE a_package_log SET status = 1 WHERE uid = '{$invite}' AND channel like '{$channel}%' AND pk_os = 2;";  // 本地包正常
                $this->versionModel->query($sql);
            } else
            {
                $sql = "UPDATE a_package_log SET status = -1 WHERE uid = '{$invite}' AND channel like '{$channel}%' AND pk_os = 2;";  // 本地包异常
                $this->versionModel->query($sql);
            }

            if ($file_size < 4000000 or $file_size > 9000000)
            {
                $sql = "UPDATE a_package_log SET status = -2 WHERE uid = '{$invite}' AND channel like '{$channel}%' AND pk_os = 2;";  // 获取包异常
                $this->versionModel->query($sql);
            }

            curl_short(_DOMAIN_ . "/cron/ipa_all/checkCDN?ipaName=$ipaName&invite=$invite&channel=$channel");  // 异步访问 chackcdn链接, 改变打包状态

//            $ipaName = "dfdsf_pack";

            if (app()->user->getId() == 1)
            {
                echo 'sql : ';
                var_dump("UPDATE a_package_log SET status = 1 WHERE uid = '{$invite}' AND channel like '{$channel}%' AND pk_os = 2;");
                echo '<br>';

                echo 'filename : ';
                var_dump($path . $ipaName);
                echo '<br>';
                echo 'filesize : ';
                var_dump(filesize($path . $ipaName));
                echo '<br>';
                echo 'ipaUrl :';
                var_dump($ipaUrl);
                echo '<br>';
                echo 'checkCDN :';
                var_dump(_DOMAIN_ . "/cron/ipa_all/checkCDN?ipaName=$ipaName&invite=$invite&channel=$channel");
                echo '<br>';
                echo 'response_header size:';
                var_dump($file_size);

            }

            //判断远程文件是否已存在
            $plistUrl = 'https://www.hongbaosuoping.com/plist/' . $plistName; // how it works
            if (!file_get_contents($plistUrl))
            {
                return array('status' => -3, 'error' => '无法获取plist文件');
            }
            return array('status' => 1, 'error' => '成功', "url" => $plistUrl);
        }
        return array('status' => 0, 'error' => '打包失败');
    }



    private function getContentLengthFromHeaderFile($filename_header)
    {
        $result = 0;
        if (!file_exists($filename_header)) {
            var_dump("debug");
            return $result;
        }


        // parse coupon
        $handle = @fopen($filename_header, "r");
        if ($handle)
        {
            while (!feof($handle))
            {
                $buffer = fgets( $handle);  // 按行读取
                $buffer = trim($buffer);
                if(strstr($buffer, "Content-Length:"))
                {

                    $file_size = intval(substr($buffer, 16, strlen($buffer)));
                    if($result < $file_size)
                        $result = $file_size;
                }

            }
            fclose($handle);
        }
        return $result;
    }

    private function pakName($channel, $invite, $c = '')
    {
        if ($c == 'c' && !empty($invite))
        {
            $pakName = $channel . '-c_' . $invite;
        } elseif ($c == 'c')
        {
            $pakName = $channel . '-c';
        } elseif (!empty($invite))
        {
            $pakName = $channel . '-' . $invite;
        } else
        {
            $pakName = $channel;
        }
        return $pakName;
    }

    //plist文件生成
    private function wPlistfile($filename, $plistContent)
    {
        $isSucceed = 0;
        $plistPath = "/data/plist/";

        if (!file_exists($plistPath))
        {
            //会导致到红包官网软连接失效
            //mkdir("$plistPath", 0700);
        }
        $plistPath .= $filename;
        $fp = fopen($plistPath, "w+"); //打开文件指针，创建文件


        if (!is_writable($plistPath))
        {
            file_put_contents($plistPath, $plistContent);
        }
        if (fwrite($fp, $plistContent))
        {
            $isSucceed = 1;
        }
        fclose($fp);  //关闭指针
        if ($isSucceed)
        {
            return true;
        }
        return false;
    }

    //plist模板
    private function iosTem($pkName, $version)
    {
        $sql = "SELECT sys_value from z_sys_conf WHERE sys_key = 'bundle_identifier'";
        $pack_ID = $this->versionModel->query($sql)[0]['sys_value'];

        $sql = "SELECT sys_value from z_sys_conf WHERE sys_key = 'ios_version'";
        $version = $this->versionModel->query($sql)[0]['sys_value'];

        $outInfo = '<?xml version="1.0.0.1" encoding="UTF-8"?>
                    <!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">
                    <plist version="1.0.0.1">
                    <dict>
                        <key>items</key>
                        <array>
                            <dict>
                                <key>assets</key>
                                <array>
                                    <dict>
                                        <key>kind</key>
                                        <string>software-package</string>
                                        <key>url</key>
                                        <string>' . _PHOTO_URL_ . '/hongbao/' . $pkName . '</string>
                                    </dict>
                                    <dict>
                                        <key>kind</key>
                                        <string>display-image</string>
                                        <key>needs-shine</key>
                                        <true/>
                                        <key>url</key>
                                        <string>http://fast-cdn.dianjoy.com/hongbao/hongbaosuoping_icon_114.png</string>
                                    </dict>
                                </array>
                                <key>metadata</key>
                                <dict>
                                    <key>bundle-identifier</key>
                                    <string>' . $pack_ID . '</string>
                                    <key>bundle-version</key>
                                    <string>' . $version . '</string>
                                    <key>kind</key>
                                    <string>software</string>
                                    <key>subtitle</key>
                                    <string>hongbaosuoping</string>
                                    <key>title</key>
                                    <string>红包锁屏</string>
                                </dict>
                            </dict>
                        </array>
                    </dict>
                    </plist>';
        return $outInfo;
    }

    private function curlGet($url, $timeout = 5, $port = 80)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_POST, 0);

        $result = array();
        $result['result'] = curl_exec($ch);
        if (0 != curl_errno($ch))
        {
            $result['error'] = "Error:\n" . curl_error($ch);
        }
        curl_close($ch);
        return $result;
    }

    public function batchAction()
    {
        $dosubmit = daddslashes($this->reqVar('dosubmit', ''));
        if (!in_array(UID, array(1, 2, 3, 4, 5, 29)))
        {
            echo "无该操作权限";
        } elseif ($dosubmit)
        {
            $redis = Leb_Dao_Redis::getInstance();
            $redisLen = $redis->llen("ZHUAN_ADMIN_TASK_PACKAGE_BATCH");
            if (empty($redisLen))
            {
                $redis->delete("ZHUAN_ADMIN_TASK_PACKAGE_BATCH_RESULT");
                $packageList = $this->packageModel->query("SELECT * FROM a_package_log WHERE pk_os = 1 order by id asc LIMIT 2000");
                if ($packageList)
                {
                    foreach ($packageList as $key => $val)
                    {
                        if (1 == $val['is_hidden_invite'] && !empty($val['uid']))
                        {
                            $val['c'] = 'c_' . $val['uid'];
                        } elseif (1 == $val['is_hidden_invite'])
                        {
                            $val['uid'] = 'c';
                        }

                        //循环加入队列
                        $pushValue = json_encode(array("uid" => $val['uid'], "channel" => $val['channel']));
                        $redis->lPush("ZHUAN_ADMIN_TASK_PACKAGE_BATCH", $pushValue);
                    }
                }
                $this->redirect('打包提交成功', '/admin/package/batch_ios', 3);
            }
        } else
        {
            echo "<a href='/admin/package/batch?dosubmit=1'>打包提交</a>&nbsp;&nbsp;&nbsp;&nbsp;";
            echo "<a href='/admin/package/batch'>打包记录</a>&nbsp;&nbsp;&nbsp;&nbsp;";

            $redis = Leb_Dao_Redis::getInstance();
            $redisGet = $redis->lRange("ZHUAN_ADMIN_TASK_PACKAGE_BATCH_RESULT", 0, -1);


            echo "记录数：" . count($redisGet) . "<br/>";
            if ($redisGet)
            {
                foreach ($redisGet as $key => $val)
                {
                    $vRe = json_decode($val, true);
                    echo "邀请码：" . $vRe['uid'] . ",渠道号：" . $vRe['channel'] . ",成功：" . $vRe['rtn'] . ",时间：" . $vRe['ctime'] . "<br/>";
                }
            }
            echo "<br/>";
        }
    }

    public function batch_iosAction()
    {
        $abnormal = daddslashes($this->reqVar('abnormal', ''));
        $dosubmit = daddslashes($this->reqVar('dosubmit', ''));
        if (!in_array(UID, array(1, 2, 3, 4, 5, 8, 29, 30)))
        {
            $this->redirect('打包提交成功', '/admin/package', 3);
        } elseif ($dosubmit)
        {
            $redis = Leb_Dao_Redis::getInstance();
            $redisLen = $redis->llen("ZHUAN_ADMIN_TASK_PACKAGE_BATCH_IOS");
            if (empty($redisLen))
            {
                $redis->delete("ZHUAN_ADMIN_TASK_PACKAGE_BATCH_RESULT_IOS");

                if(empty($abnormal))
                {
                    $packageList = $this->packageModel->query("SELECT * FROM a_package_log WHERE pk_os =2 order by id asc");
                }
                else
                {
                    $packageList = $this->packageModel->query("SELECT * FROM a_package_log WHERE pk_os =2 AND STATUS < 0 order by id asc");
                }

                if ($packageList)
                {
                    foreach ($packageList as $key => $val)
                    {
                        if (1 == $val['is_hidden_invite'])
                        {
                            $val['c'] = 'c';
                        } else
                        {
                            $val['c'] = '';
                        }

                        //循环加入队列
                        $pushValue = json_encode(array("uid" => $val['uid'], "channel" => $val['channel'], "c" => $val['c']));
                        $redis->lPush("ZHUAN_ADMIN_TASK_PACKAGE_BATCH_IOS", $pushValue);
                    }
                }
                $this->oplog(array("batch_ios" => "submit"));

                $this->redirect('打包提交成功', '/admin/package/batch_ios', 3);
            }
            $this->redirect('正在执行打包操作,请稍等..', '/admin/package/batch_ios', 3);
        } else
        {
            $redisGetList = array();
            $succeeCount = $failCount = 0;
            $redis = Leb_Dao_Redis::getInstance();
            $redisGet = $redis->lRange("ZHUAN_ADMIN_TASK_PACKAGE_BATCH_RESULT_IOS", 0, -1);
            if ($redisGet)
            {
                foreach ($redisGet as $key => $val)
                {
                    $vRe = json_decode($val, true);
                    if (!empty($vRe['rtn']) && 1 == $vRe['rtn'])
                    {
                        $succeeCount = $succeeCount + 1;
                        $redisGetList[$key]['status'] = "成功";
                    } else if (!empty($vRe['rtn']) && 11 == $vRe['rtn'])
                    {
                        $succeeCount = $succeeCount + 1;
                        $redisGetList[$key]['status'] = "文件正常, 不用打包";
                    } elseif (!empty($vRe['error']))
                    {
                        $failCount = $failCount + 1;
                        $redisGetList[$key]['status'] = '<span class="red">失败:' . $vRe['error'] . '</span>';
                    } else
                    {
                        $failCount = $failCount + 1;
                        $redisGetList[$key]['status'] = '<span class="red">失败</span>';
                    }
                    $redisGetList[$key]['uid'] = $vRe['uid'];
                    $redisGetList[$key]['channel'] = $vRe['channel'];
                    $redisGetList[$key]['ctime'] = $vRe['ctime'];
                }
            }
            $sumCount = count($redisGet);

            $this->assign('redisGetList', $redisGetList);
            $this->assign('sumCount', $sumCount);
            $this->assign('succeeCount', $succeeCount);
            $this->assign('failCount', $failCount);
            $this->getViewer()->needLayout(false);
            $this->render('package_batch');
        }
    }



    public function batch_ios_zaibeiAction()
    {
        $abnormal = daddslashes($this->reqVar('abnormal', ''));
        $dosubmit = daddslashes($this->reqVar('dosubmit', ''));
        if (!in_array(UID, array(1, 2, 3, 4, 5, 8, 29, 30)))
        {
            $this->redirect('打包提交成功', '/admin/package', 3);
        } elseif ($dosubmit)
        {
            $redis = Leb_Dao_Redis::getInstance();
            $redisLen = $redis->llen("ZHUAN_ADMIN_TASK_PACKAGE_BATCH_IOS_ZAIBEI");
            if (empty($redisLen))
            {
                $redis->delete("ZHUAN_ADMIN_TASK_PACKAGE_BATCH_RESULT_IOS_ZAIBEI");

                if(empty($abnormal))
                {
                    $packageList = $this->packageModel->query("SELECT * FROM a_package_log WHERE pk_os =2 order by id asc");
                }
                else
                {
                    $packageList = $this->packageModel->query("SELECT * FROM a_package_log WHERE pk_os =2 AND STATUS < 0 order by id asc");
                }

                if ($packageList)
                {
                    foreach ($packageList as $key => $val)
                    {
                        if (1 == $val['is_hidden_invite'])
                        {
                            $val['c'] = 'c';
                        } else
                        {
                            $val['c'] = '';
                        }

                        //循环加入队列
                        $pushValue = json_encode(array("uid" => $val['uid'], "channel" => $val['channel'], "c" => $val['c']));
                        $redis->lPush("ZHUAN_ADMIN_TASK_PACKAGE_BATCH_IOS_ZAIBEI", $pushValue);
                    }
                }
                $this->oplog(array("batch_ios_zaibei" => "submit"));

                $this->redirect('打包提交成功', '/admin/package/batch_ios_zaibei', 3);
            }
            $this->redirect('正在执行打包操作,请稍等..', '/admin/package/batch_ios_zaibei', 3);
        } else
        {
            $redisGetList = array();
            $succeeCount = $failCount = 0;
            $redis = Leb_Dao_Redis::getInstance();
            $redisGet = $redis->lRange("ZHUAN_ADMIN_TASK_PACKAGE_BATCH_RESULT_IOS_ZAIBEI", 0, -1);
            if ($redisGet)
            {
                foreach ($redisGet as $key => $val)
                {
                    $vRe = json_decode($val, true);
                    if (!empty($vRe['rtn']) && 1 == $vRe['rtn'])
                    {
                        $succeeCount = $succeeCount + 1;
                        $redisGetList[$key]['status'] = "成功";
                    } else if (!empty($vRe['rtn']) && 11 == $vRe['rtn'])
                    {
                        $succeeCount = $succeeCount + 1;
                        $redisGetList[$key]['status'] = "文件正常, 不用打包";
                    } elseif (!empty($vRe['error']))
                    {
                        $failCount = $failCount + 1;
                        $redisGetList[$key]['status'] = '<span class="red">失败:' . $vRe['error'] . '</span>';
                    } else
                    {
                        $failCount = $failCount + 1;
                        $redisGetList[$key]['status'] = '<span class="red">失败</span>';
                    }
                    $redisGetList[$key]['uid'] = $vRe['uid'];
                    $redisGetList[$key]['channel'] = $vRe['channel'];
                    $redisGetList[$key]['ctime'] = $vRe['ctime'];
                }
            }
            $sumCount = count($redisGet);

            $this->assign('redisGetList', $redisGetList);
            $this->assign('sumCount', $sumCount);
            $this->assign('succeeCount', $succeeCount);
            $this->assign('failCount', $failCount);
            $this->getViewer()->needLayout(false);
            $this->render('package_batch_zaibei');
        }
    }




    public function batch_check_cdnAction()
    {

        $packageList = $this->packageModel->query("SELECT * FROM a_package_log WHERE pk_os =2 AND STATUS < 0 order by id asc");
//        $versionRe = $this->versionModel->getIosVersion();
        $sql = "SELECT sys_value from z_sys_conf WHERE sys_key = 'ios_version'";
        $versionRe['version'] = $this->versionModel->query($sql)[0]['sys_value'];

        foreach ($packageList as $val)
        {
            if (1 == $val['is_hidden_invite'] && !empty($val['uid']))
            {
                $pack['invite'] = 'c_' . $val['uid'];
                $pack['c'] = 'c';
            } elseif (1 == $val['is_hidden_invite'])
            {
                $pack['invite'] = '';
                $pack['c'] = 'c';
            } elseif (!empty($val['uid']))
            {
                $pack['invite'] = $val['uid'];
                $pack['c'] = '';
            } else
            {
                $pack['invite'] = '';
                $pack['c'] = '';
            }

            $ipaName = $this->pakName(trim($val['channel']), $pack['invite'], $pack['c']) . "_" . trim($versionRe['version']) . ".ipa";

            $cdn_url = "http://fast-cdn.dianjoy.com/hongbao/$ipaName";

            var_dump($val['is_hidden_invite']);
            var_dump($val['channel']);
            var_dump($pack['invite']);
            var_dump($pack['c']);

            var_dump($cdn_url);
            echo "<br>";
//            continue;

            $responseInfo = get_headers($cdn_url, 1);
            $file_size = intval($responseInfo['Content-Length']);

            var_dump($cdn_url);
            var_dump($file_size);

            if ($file_size < 4000000 or $file_size > 9000000)
            {
                echo "cdn包异常";
                $sql = "UPDATE a_package_log SET status = -3 WHERE uid = '{$pack['invite']}' AND channel like '{$val['channel']}%' AND pk_os = 2;";  // cdn包异常
                $this->versionModel->query($sql);
            } else
            {
                echo "cdn包正常";

                $sql = "UPDATE a_package_log SET status = 2 WHERE uid = '{$pack['invite']}' AND channel like '{$val['channel']}%' AND pk_os = 2;";  // cdn包正常
                $this->versionModel->query($sql);
            }
        }
        $this->redirect('', '/admin/package/', 0);

    }


    public function upAction()
    {
        die("暂停");
        $packageList = $this->packageModel->query("SELECT id,channel FROM a_package_log WHERE pk_os=1 and channel='student_proxy	' order by id asc limit 200");
        if ($packageList)
        {
            foreach ($packageList as $key => $val)
            {
                if (!empty($val['id']))
                {
                    $re = $this->packageModel->where(" id = '{$val['id']}'")->save(array("channel" => trim($val['channel'])));
                    if ($re)
                    {
                        echo $val['id'] . "--" . $val['channel'] . "--成功<br/>";
                    } else
                    {
                        echo $val['id'] . "--" . $val['channel'] . "--失败<br/>";
                    }
                }
            }
        }
        die();
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
        $this->operateLogModel->addOpLog($logAdd);
    }


    public function clear_zaibeiCacheAction()
    {
        $page = (int)$this->getVar('page', 1);

        $packageList = $this->packageModel->query("SELECT * FROM a_package_log WHERE pk_os =2 order by id asc");

        $sql = "SELECT sys_value from z_sys_conf WHERE sys_key = 'ios_version'";
        $versionRe['version'] = $this->versionModel->query($sql)[0]['sys_value'];

        foreach ($packageList as $val)
        {
            if (1 == $val['is_hidden_invite'] && !empty($val['uid']))
            {
                $pack['invite'] = 'c_' . $val['uid'];
                $pack['c'] = 'c';
            } elseif (1 == $val['is_hidden_invite'])
            {
                $pack['invite'] = '';
                $pack['c'] = 'c';
            } elseif (!empty($val['uid']))
            {
                $pack['invite'] = $val['uid'];
                $pack['c'] = '';
            } else
            {
                $pack['invite'] = '';
                $pack['c'] = '';
            }

            $ipaName = $this->pakName(trim($val['channel']), $pack['invite'], $pack['c']) . "_" . trim($versionRe['version']) . ".ipa";

            //清除快网缓存
            $urlArr = array("http://fast-cdn.dianjoy.com/hongbao/$ipaName");
            $dirArr = array(_PHOTO_URL_ . '/hongbao/');
            $data = $this->clearCache($urlArr, $dirArr);

            var_dump($urlArr);
            echo "<br>";
            var_dump($dirArr);
            echo "<br>";
            var_dump($data);
            echo "<br>";

        }
        $this->oplog(array("clear_zaibeiCache" => "submit"));

        $this->redirect('', '/admin/package', 100);
    }

    private function clearCache($urlArr, $dirArr)
    {
        $data = array('rs' => 0, 'error' => '');
        if (empty($urlArr) || empty($dirArr))
        {
            return array('rs' => -1, 'error' => 'URL无法获取');
        }

        $push = new Plugin_pushcontent();
        $push->setUserInfo('dianjoy.com', 'TeHvNV06', 'dianjoycomFW');
        $call_return = $push->pushData($urlArr, $dirArr);
        if (is_array($call_return))
        {
            switch ($call_return['result'])
            {
                //成功调用，无错误，在break前添加您的成功后处理代码
                case 'success':
                {
                    $data = array('rs' => 1, 'error' => '清除成功');
                    break;
                }
                case 'error':
                {
                    //输出错误信息，在此添加您的错误处理代码
                    $data = array('rs' => -2, 'error' => $call_return['detail_info']);
                    break;
                }
                case 'warning':
                {
                    //部分提交更新成功，但存在有问题的URL
                    //输出提示信息
                    $data = array('rs' => -3, 'error' => $call_return['detail_info']);
                    break;
                }
            }
        }
        return $data;
    }
}