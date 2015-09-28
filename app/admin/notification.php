<?php

header("Content-Type: text/html; charset=utf-8");
/**
 * 通知管理
 *
 * @category   Leb
 * @package    Leb_Action
 * @author     lihui
 * @version    $Id: notification.php 2015-01-22 19:58:00 lihui
 * @copyright (c) 2015 dianjoy.com
 * @license
 */
class notificationController extends Application
{
    private $configModel;
    private $userModel;
    private $notificationModel;
    private $notificationPivateModel;
    private $pushModel;
    private $operateLogModel;
    private $userClient;
    private $transport;

    public function execute($plugins)
    {
        $this->configModel = C('global.php');
        $this->userModel = $this->loadAppModel('User');
        $this->notificationModel = $this->loadModel('Notification');
        $this->notificationPivateModel = $this->loadModel('Notification_private');
        $this->pushModel = $this->loadModel('Push_ios');
        $this->operateLogModel = $this->loadModel('Operate_log');

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
        $page = (int)$this->reqVar('page', 1);
        $actionOs = daddslashes(trim($this->reqVar('action_os', '')));
        $actionStatus = daddslashes($this->reqVar('action_status', ''));
        $actionType = daddslashes($this->reqVar('action_type', ''));


        $pageUrl = "/admin/notification/";
        $notificationSet = array();
        if (!empty($actionOs))
        {
            $notificationSet['os_type'] = $actionOs;
            $pageUrl .= "?action_os=$actionOs";
        }
        if (is_numeric($actionStatus))
        {
            $notificationSet['status'] = $actionStatus;
            $pageUrl .= !empty($actionOs) ? "&" : "?";
            $pageUrl .= "action_status=$actionStatus";
        }
        if (!empty($actionType))
        {
            $notificationSet['action'] = $actionType;
            if (!empty($actionOs) || is_numeric($actionStatus))
            {
                $pageUrl .= "&action_type=$actionType";
            } else
            {
                $pageUrl .= "?action_type=$actionType";
            }
        }

        $notificationList = $this->notificationModel->getNotificationList($notificationSet, $page, 60);

//        foreach($notificationList as &$var)
//        {
//        //    var_dump("SELECT COUNT(*) as num FROM a_notification WHERE id = {$var['id']}");die();
//
//            $a = $this->notificationModel->query("SELECT COUNT(*) as num FROM a_notification_public WHERE status = 1 and nid = {$var['id']}")[0]['num'];
//            $b = $this->notificationModel->query("SELECT COUNT(*) as num FROM a_notification_public_master WHERE status = 1 and nid = {$var['id']}")[0]['num'];
//
//            $good= intval($a) + intval($b);
//
//            $a = $this->notificationModel->query("SELECT COUNT(*) as num FROM a_notification_public WHERE status = 2 and nid = {$var['id']}")[0]['num'];
//            $b = $this->notificationModel->query("SELECT COUNT(*) as num FROM a_notification_public_master WHERE status = 2 and nid = {$var['id']}")[0]['num'];
//            $bad= intval($a) + intval($b);
//
//            $var['send_num'] = $good . "/" . $bad;
//        }

        $notificationCount = $this->notificationModel->getNotificationCount($notificationSet);
        $notificationPages = pages($notificationCount, $page, 60, $pageUrl, $array = array());

        $this->assign('notificationList', $notificationList);
        $this->assign('notificationCount', $notificationCount);
        $this->assign('notificationPages', $notificationPages);
        $this->assign("notificationStatus", $this->configModel['notification_status']);
        $this->assign("notificationType", $this->configModel['notification_type']);
        $this->assign("publicType", $this->configModel['public_type']);
        $this->assign("pkOs", $this->configModel['pk_os']);
        $this->assign("adStatus", $this->configModel['ad_status']);
        $this->assign("actionType", $actionType);
        $this->assign("actionStatus", $actionStatus);
        $this->assign("actionOs", $actionOs);
        $this->assign('page', $page);
        $this->assign('adminId', UID);

        $this->getViewer()->needLayout(false);
        $this->render('notification_list');
    }


    public function getPackageName($ad_id)
    {
        $url = "http://a.dianjoy.com/dev/api/adlist/get_ad_rmb.php?ad_id=" . $ad_id;

        $handle = fopen($url, "rb");
        $contents = stream_get_contents($handle);
        fclose($handle);

        $strList = explode('"', $contents);
        foreach ($strList as $val)
        {
//            echo $val . "<br>";
            if ($val == "pack_name")
                break;
        }
        next($strList);

        return current($strList);

    }

    public function add_iosAction()
    {
        $dosubmit = daddslashes($this->postVar('dosubmit', ''));
        $uidBatch = daddslashes($this->reqVar('uid_batch', ''));
        $nAdd['uid_batch'] = str_replace(array("\n", "\r", "\t", '，', ' '), array(',', ',', ',', ',', ''), $uidBatch);

        $nAdd['os_type'] = 2;
        $nAdd['title'] = daddslashes(trim($this->reqVar('title', '')));
        $nAdd['n_type'] = daddslashes(trim($this->reqVar('n_type', '')));
        $nAdd['protocol'] = 'intent';
        $nAdd['action'] = daddslashes(trim($this->reqVar('ac', '')));
        $nAdd['message_id'] = (int)$this->reqVar('message_id', 0);
        $nAdd['start_date'] = daddslashes(trim($this->reqVar('start_date', '')));
        $nAdd['end_date'] = daddslashes(trim($this->reqVar('end_date', '')));
        $nAdd['limit_num'] = (int)($this->reqVar('limit_num', ''));
        $nAdd['creater'] = UNAME;

        if ($nAdd['action'] == 'integral_detail')
        {
            $nAdd['ad_id'] = daddslashes(trim($this->reqVar('ad_id', '')));
            $nAdd['ad_pack'] = self:: getPackageName($nAdd['ad_id']);

//            var_dump($nAdd);

//            die("dfd");


        } else
        {
            $nAdd['ad_id'] = '';
        }

        if (!empty($dosubmit))
        {
            $pageUrl = "/admin/notification/add_ios?uid_batch=$uidBatch&title={$nAdd['title']}&n_type={$nAdd['n_type']}&ac={$nAdd['action']}";
            $pageUrl .= "&ad_id={$nAdd['ad_id']}&message_id={$nAdd['message_id']}&start_date={$nAdd['start_date']}&end_date={$nAdd['end_date']}";
            $pageUrl .= "&limit_num={$nAdd['limit_num']}";

            //验证参数
            $checkRe = $this->addIosCheck($nAdd);
            if (empty($checkRe['s']) && !empty($checkRe['e']))
            {
                $this->redirect($checkRe['e'], $pageUrl, 3);
                die();
            }

            $userInfo = array();

            if (empty($nAdd['n_type']))
            {// private notif


                $uidArr = array_filter(explode(",", trim($nAdd['uid_batch'], ",")));
                $uidArr = array_unique($uidArr); //去重

                //Thrift连接
                try
                {
                    $this->transport->open();
                } catch (Exception $e)
                {
                    echo $e->getMessage();
                }

                $tp = $this->transport->isOpen();
                if (!$tp)
                {
                    $this->redirect('获取用户信息服务无法连接!', $pageUrl, 5);
                }

                //过滤uid
                foreach ($uidArr as $val)
                {
                    if (!is_numeric($val))
                        continue;

                    //验证用户
                    $isUser = $this->userClient->existUser($val);
                    if (!empty($isUser))
                    {
                        $pushSet['uid'] = $val;
                        $getPush = $this->pushModel->getPush($pushSet);
                        if ($getPush)
                            $userInfo[] = $val;
                    }

//                    //备份表验证暂停使用
//                    $userSet['uid'] = $val;
//                    $userSet['status'] = 1;
//                    $userRe = $this->userModel->getUser($userSet);
//                    if($userRe){
//                        $pushSet['uid'] = $val;
//                        $getPush = $this->pushModel->getPush($pushSet);
//                        if($getPush)
//                            $userInfo[] = $val;
//                    }
                }
                if (empty($userInfo) || 500 < count($userInfo))
                {
                    $this->redirect('用户ID不存在或数量超出限制!', $pageUrl, 5);
                    die();
                }
            }

            $nId = $this->notificationModel->addNotification($nAdd);
            if ($nId)
            {
                //$userInfo不为空时为私有消息
                if (!empty($userInfo))
                {
                    foreach ($userInfo as $val)
                    {
                        $nPivateAdd['nid'] = $nId;
                        $nPivateAdd['creater'] = UNAME;
                        $nPivateAdd['uid'] = $val;
                        $this->notificationPivateModel->addNotification($nPivateAdd);
                    }
                }

                //操作日志记录
                $this->oplog($nAdd);
                $this->redirect('添加成功', '/admin/notification/', 1);
                die();
            }
        }

        //APP消息
        $publicMsg = $this->messageSelect();

        //类型配置
        $nType = $this->configModel['notification_type'];
        unset($nType['external']);
        unset($nType['external_url']);
        unset($nType['cooperatelist']);
        unset($nType['uninstall']);

        $this->assign("notificationType", $nType);
        $this->assign('publicMsg', $publicMsg);
        $this->assign("pkOs", $this->configModel['pk_os']);

        $this->assign("acUidBatch", $uidBatch);
        $this->assign("acTitle", $nAdd['title']);
        $this->assign("acNType", $nAdd['n_type']);
        $this->assign("acAc", $nAdd['action']);
        $this->assign("acAdId", $nAdd['ad_id']);
        $this->assign("acMessageId", $nAdd['message_id']);
        $this->assign("acStartDate", $nAdd['start_date']);
        $this->assign("acEndDate", $nAdd['end_date']);
        if (!empty($nAdd['limit_num']))
        {
            $this->assign("acLimitNum", $nAdd['limit_num']);
        } else
        {
            $this->assign("acLimitNum", '');
        }

        $this->getViewer()->needLayout(false);
        $this->render('notification_add_ios');
    }

    private function addIosCheck($nAdd)
    {
        $dateNow = date("Y-m-d H:i:s", time());
        if (empty($nAdd['title']) || (10 > strlen($nAdd['title']) && 200 < strlen($nAdd['title'])))
        {
            return array('s' => 0, 'e' => '标题限制在10-200个字符');
        } elseif (empty($nAdd['n_type']) && empty($nAdd['uid_batch']))
        {
            return array('s' => 0, 'e' => '请填写用户ID');
        } elseif (empty($nAdd['action']))
        {
            return array('s' => 0, 'e' => '请选择类别');
        } elseif (empty($nAdd['message_id']) && in_array($nAdd['action'], array('message_detail')))
        {
            return array('s' => 0, 'e' => '请选择消息');
        } elseif (empty($nAdd['ad_id']) && in_array($nAdd['action'], array('integral_detail')))
        {
            return array('s' => 0, 'e' => '请填写积分墙广告ID');
        } elseif (empty($nAdd['start_date']) || empty($nAdd['end_date']))
        {
            return array('s' => 0, 'e' => '上、下线时间不能小于当前时间且下线时间不能小于上线时间。');
        } elseif ($dateNow > $nAdd['start_date'] || $dateNow > $nAdd['end_date'])
        {
            return array('s' => 0, 'e' => '上、下线时间不能小于当前时间且下线时间不能小于上线时间!');
        } elseif ($nAdd['end_date'] < $nAdd['start_date'])
        {
            return array('s' => 0, 'e' => '上、下线时间不能小于当前时间且下线时间不能小于上线时间');
        } else
        {
            return array('s' => 1, 'e' => '');
        }
    }

    public function add_androidAction()
    {
        $dosubmit = daddslashes($this->postVar('dosubmit', ''));
        $uidBatch = daddslashes($this->reqVar('uid_batch', ''));
        $nAdd['uid_batch'] = str_replace(array("\n", "\r", "\t", '，', ' '), array(',', ',', ',', ',', ''), $uidBatch);

        $nAdd['os_type'] = 1;
        $nAdd['title'] = daddslashes(trim($this->reqVar('title', '')));
        $nAdd['subtitle'] = daddslashes(trim($this->reqVar('subtitle', '')));
        $nAdd['n_type'] = daddslashes(trim($this->reqVar('n_type', '')));
        $nAdd['protocol'] = 'intent';
        $nAdd['action'] = daddslashes(trim($this->reqVar('ac', '')));
        $nAdd['ad_pack'] = daddslashes(trim($this->reqVar('ad_pack', '')));
        $nAdd['message_id'] = (int)$this->reqVar('message_id', 0);
        $nAdd['click_url'] = daddslashes(trim($this->reqVar('click_url', '')));
        $nAdd['is_popup'] = (int)$this->reqVar('is_popup', 0);
        $nAdd['start_date'] = daddslashes(trim($this->reqVar('start_date', '')));
        $nAdd['end_date'] = daddslashes(trim($this->reqVar('end_date', '')));
        $nAdd['limit_num'] = (int)($this->reqVar('limit_num', ''));
        $nAdd['creater'] = UNAME;
        if ($nAdd['action'] == 'integral_detail')
        {
            $nAdd['ad_id'] = daddslashes(trim($this->reqVar('ad_id', '')));
        } else
        {
            $nAdd['ad_id'] = '';
        }

        $fileUpload = $_FILES['file_uplode'];
        if (!empty($dosubmit))
        {
            $pageUrl = "/admin/notification/add_android?uid_batch=$uidBatch&title={$nAdd['title']}&subtitle={$nAdd['subtitle']}";
            $pageUrl .= "&n_type={$nAdd['n_type']}&ac={$nAdd['action']}&ad_id={$nAdd['ad_id']}&ad_pack={$nAdd['ad_pack']}";
            $pageUrl .= "&message_id={$nAdd['message_id']}&click_url={$nAdd['click_url']}&is_popup={$nAdd['is_popup']}";
            $pageUrl .= "&start_date={$nAdd['start_date']}&end_date={$nAdd['end_date']}&limit_num={$nAdd['limit_num']}";

            //验证参数
            $checkRe = $this->addAndroidCheck($nAdd);
            if (empty($checkRe['s']) && !empty($checkRe['e']))
            {
                $this->redirect($checkRe['e'], $pageUrl, 3);
                die();
            }

            $userInfo = array();
            if (empty($nAdd['n_type']))
            {
                $uidArr = array_filter(explode(",", trim($nAdd['uid_batch'], ",")));
                $uidArr = array_unique($uidArr); //去重

                //Thrift连接
                $this->transport->open();
                $tp = $this->transport->isOpen();
                if (!$tp)
                {
                    $this->redirect('获取用户信息服务无法连接!', $pageUrl, 5);
                }

                //过滤uid
                foreach ($uidArr as $val)
                {
                    if (!is_numeric($val))
                        continue;

                    //验证用户
                    $isUser = $this->userClient->existUser($val);
                    if (!empty($isUser))
                    {
                        $userInfo[] = $val;
                    }

//                    //备份表验证暂停使用
//                    $userSet['uid'] = $val;
//                    $userSet['status'] = 1;
//                    $userRe = $this->userModel->getUser($userSet);
//                    if($userRe){
//                        $userInfo[] = $val;
//                    }
                }
                if (empty($userInfo) || 500 < count($userInfo))
                {
                    $this->redirect('用户ID不存在或数量超出限制!', $pageUrl, 5);
                    die();
                }
            }

            if (!empty($fileUpload['name']))
            {
                list($width_img, $height_img) = getimagesize($fileUpload['tmp_name']);
                if ($fileUpload['size'] > 50200)
                {
                    $this->redirect('图片大小不能超过50KB', $pageUrl, 3);
                    die();
                } elseif ($width_img > 72 || $height_img > 72)
                {
                    $this->redirect('图片尺寸不能超出72*72', $pageUrl, 3);
                    die();
                } else
                {
                    $nAdd['url_images'] = $this->uploadFile($fileUpload);
                    if (empty($nAdd['url_images']))
                    {
                        $this->redirect('上传失败,请联系管理员!', $pageUrl, 3);
                        die();
                    }
                }
            }

            $nId = $this->notificationModel->addNotification($nAdd);
            if ($nId)
            {
                //$userInfo不为空时为私有消息
                if (!empty($userInfo))
                {
                    foreach ($userInfo as $val)
                    {
                        $nPivateAdd['nid'] = $nId;
                        $nPivateAdd['uid'] = $val;
                        $this->notificationPivateModel->addNotification($nPivateAdd);
                    }
                }

                //操作日志记录
                $this->oplog($nAdd);
                $this->redirect('添加成功', '/admin/notification/', 1);
                die();
            }
        }

        //APP消息
        $publicMsg = $this->messageSelect();

        //类型配置
        $notificationType = $this->configModel['notification_type'];

        $this->assign("notificationType", $notificationType);
        $this->assign('publicMsg', $publicMsg);
        $this->assign("pkOs", $this->configModel['pk_os']);

        $this->assign("acUidBatch", $uidBatch);
        $this->assign("acTitle", $nAdd['title']);
        $this->assign("acSubtitle", $nAdd['subtitle']);
        $this->assign("acNType", $nAdd['n_type']);
        $this->assign("acAc", $nAdd['action']);
        $this->assign("acAdId", $nAdd['ad_id']);
        $this->assign("acAdPack", $nAdd['ad_pack']);
        $this->assign("acMessageId", $nAdd['message_id']);
        $this->assign("acClickUrl", $nAdd['click_url']);
        $this->assign("acIsPopup", $nAdd['is_popup']);
        $this->assign("acStartDate", $nAdd['start_date']);
        $this->assign("acEndDate", $nAdd['end_date']);
        if (!empty($nAdd['limit_num']))
        {
            $this->assign("acLimitNum", $nAdd['limit_num']);
        } else
        {
            $this->assign("acLimitNum", '');
        }

        $this->getViewer()->needLayout(false);
        $this->render('notification_add_android');
    }

    //检查输出
    private function addAndroidCheck($nAdd)
    {
        $dateNow = date("Y-m-d H:i:s", time());
        if (empty($nAdd['title']) || (10 > strlen($nAdd['title']) && 50 < strlen($nAdd['title'])))
        {
            return array('s' => 0, 'e' => '标题限制在10-50个字符');
        }
        if (empty($nAdd['subtitle']) || (10 > strlen($nAdd['subtitle']) && 50 < strlen($nAdd['subtitle'])))
        {
            return array('s' => 0, 'e' => '标题限制在10-50个字符');
        } elseif (empty($nAdd['n_type']) && empty($nAdd['uid_batch']))
        {
            return array('s' => 0, 'e' => '请填写用户ID');
        } elseif (empty($nAdd['action']))
        {
            return array('s' => 0, 'e' => '请选择类别');
        } elseif (empty($nAdd['message_id']) && in_array($nAdd['action'], array('message_detail')))
        {
            return array('s' => 0, 'e' => '请选择消息');
        } elseif (empty($nAdd['ad_id']) && in_array($nAdd['action'], array('integral_detail')))
        {
            return array('s' => 0, 'e' => '请填写积分墙广告ID');
        } elseif (empty($nAdd['ad_pack']) && $nAdd['action'] == 'external')
        {
            return array('s' => 0, 'e' => '请填写包名');
        } elseif (empty($nAdd['click_url']) && $nAdd['action'] == 'external_url')
        {
            return array('s' => 0, 'e' => '请填写跳转地址');
        } elseif (empty($nAdd['start_date']) || empty($nAdd['end_date']))
        {
            return array('s' => 0, 'e' => '上、下线时间不能小于当前时间且下线时间不能小于上线时间。');
        } elseif ($dateNow > $nAdd['start_date'] || $dateNow > $nAdd['end_date'])
        {
            return array('s' => 0, 'e' => '上、下线时间不能小于当前时间且下线时间不能小于上线时间!');
        } elseif ($nAdd['end_date'] < $nAdd['start_date'])
        {
            return array('s' => 0, 'e' => '上、下线时间不能小于当前时间且下线时间不能小于上线时间');
        } else
        {
            return array('s' => 1, 'e' => '');
        }
    }

    private function messageSelect()
    {
        $publicMsgModel = $this->loadAppModel('Public_msg');

        $msgSet['condition'] = " AND os_type in(0,2)";
        $publicMsg = $publicMsgModel->getMsgList($msgSet, 1, 60);
        return $publicMsg;
    }

    private function uploadFile($filename)
    {
        if (empty($filename))
        {
            return false;
        }

        $path = "../data/notification/"; //上传路径
        if (!file_exists($path))
        {
            mkdir("$path", 0700);
        }
        //允许上传的文件格式
        $tp = array("image/pjpeg", "image/jpeg", "image/jpg", "image/png");
        if (!in_array($filename["type"], $tp))
        {
            return false;
        }

        $flag = 0;
        if ($filename["name"])
        {
            $imgType = explode("/", $filename["type"]);
            if (in_array($imgType[1], array('pjpeg', 'jpeg', 'jpg')))
            {
                $imgType[1] = 'jpg';
            }

            $file2name = md5_file($filename["tmp_name"]) . '.' . $imgType[1];
            $file2 = $path . $file2name;
            $flag = 1;
        }
        $result = false;
        if ($flag)
            $result = move_uploaded_file($filename["tmp_name"], $file2);

        if ($result)
        {
            return _PHOTO_URL_ . '/hbdata/notification/' . $file2name;
        } else
        {
            return false;
        }
    }

    public function auditAction()
    {
        $nid = (int)$this->postVar('nid', 0);
        $page = (int)$this->postVar('page', 1);
        $getNotification = $this->notificationModel->getNotification(array('id' => $nid));
        if (!$getNotification)
        {
            $this->redirect('审核失败,无法验证信息', '/admin/notification/?page=' . $page, 1);
        }

        //私有通知更新为处理成功
        if (0 == $getNotification['n_type'])
        {
            $this->notificationPivateModel->auditSucceed($nid);
        }

        //操作日志记录
        $this->oplog($nid);

        $this->notificationModel->auditSucceed($nid);
        $this->redirect('', '/admin/notification/?page=' . $page, 0);
    }

    public function submitAction()
    {
        $nid = (int)$this->postVar('nid', 0);
        $page = (int)$this->postVar('page', 1);
        $getNotification = $this->notificationModel->getNotification(array('id' => $nid));
        if (!$getNotification)
        {
            $this->redirect('提交失败,无法验证信息', '/admin/notification/?page=' . $page, 1);
        }

        if (1 == $getNotification['n_type'])
        {
            $getRealIp = get_real_ip();
            $getRealIpStr = substr($getRealIp, 0, 7);

            //公用消息操作限制IP段
            $limitIp = array('192.168');
            if (empty($getRealIp) || !in_array($getRealIpStr, $limitIp))
            {
//                $this->redirect('无权限操作,请联系管理员!', '/admin/message/', 5);
//                die();
            }
        }

        //私有通知更新为处理成功
        if (0 == $getNotification['n_type'])
        {
            $this->notificationPivateModel->disposeSucceed($nid);
        }

        //操作日志记录
        $this->oplog($nid);

        $this->notificationModel->disposeSucceed($nid);

        $this->redirect('提交成功', '/admin/notification/?page=' . $page, 1);
    }


    public function delAction()
    {
        $nid = (int)$this->postVar('nid', 0);
        $page = (int)$this->postVar('page', 1);
        $getNotification = $this->notificationModel->getNotification(array('id' => $nid));
        if (!$getNotification)
        {
            $this->redirect('删除失败,无法验证信息', '/admin/notification/?page=' . $page, 1);
        }

        $this->notificationModel->delNotification($nid);
        if (0 == $getNotification['n_type'])
        {
            $this->notificationPivateModel->delNotification($nid);
        }
        //操作日志记录
        $this->oplog($nid);

        $this->redirect('删除成功', '/admin/notification/?page=' . $page, 1);
    }

    public function detailAction()
    {
        $nid = (int)$this->reqVar('nid', 0);
        $page = (int)$this->reqVar('page', 1);
        if ($nid > 0)
        {
            $getNotification = $this->notificationModel->getNotification(array('id' => $nid));

            $nPivateSet['nid'] = $nid;
            $nPivateList = $this->notificationPivateModel->getNotificationList($nPivateSet, 1, 500);

            $this->assign('getNotification', $getNotification);
            $this->assign('nPivateList', $nPivateList);
        }

        $this->assign('nid', $nid);
        $this->assign('page', $page);
        $this->assign("notificationStatus", $this->configModel['notification_status']);
        $this->assign("notificationType", $this->configModel['notification_type']);
        $this->assign("notificationOs", $this->configModel['pk_os']);
        $this->assign("adStatus", $this->configModel['ad_status']);
        $this->assign("publicType", $this->configModel['public_type']);

        $this->getViewer()->needLayout(false);
        $this->render('notification_detail');
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

}