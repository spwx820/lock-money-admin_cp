<?php
/**
 * 后台发送消息
 *
 * @category   Leb
 * @package    Leb_Action
 * @author     lihui
 * @version    $Id: message.php 2014-09-25 13:58:00 lihui
 * @copyright (c) 2014 dianjoy.com
 * @license
 */
class messageController extends Application
{
    private $configModel;
    private $userModel;
    private $messageModel;
    private $messagePrivateModel;
    private $operateLogModel;
    private $transport;
    private $userClient;

    public function execute($plugins)
    {
        $this->configModel = C('global.php');
        $this->userModel = $this->loadAppModel('User');
        $this->messageModel = $this->loadModel('Message');
        $this->messagePrivateModel = $this->loadModel('Message_private');
        $this->operateLogModel = $this->loadModel('Operate_log', array(), 'admin');


        $GLOBALS['THRIFT_ROOT'] = '../thriftlib';
        require_once( $GLOBALS['THRIFT_ROOT'] . '/Thrift.php' );
        require_once( $GLOBALS['THRIFT_ROOT'] . '/transport/TSocket.php' );
        require_once( $GLOBALS['THRIFT_ROOT'] . '/transport/TBufferedTransport.php' );
        require_once( $GLOBALS['THRIFT_ROOT'] . '/protocol/TBinaryProtocol.php' );
        require_once( $GLOBALS['THRIFT_ROOT'] . '/packages/user_service/UserService.php' );

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

        $pageUrl = "/admin/message/";
        $messageSet = array();
        if (is_numeric($actionOs))
        {
            $messageSet['os_type'] = $actionOs;
            $pageUrl .= "?action_os=$actionOs";
        }
        if (is_numeric($actionStatus))
        {
            $messageSet['status'] = $actionStatus;
            $pageUrl .= is_numeric($actionOs) ? "&" : "?";
            $pageUrl .= "action_status=$actionStatus";
        }
        $messageSet['condition'] = " AND status>=0";
        $messageList = $this->messageModel->getMessageList($messageSet, $page, 20);
        $messageCount = $this->messageModel->getMessageCount($messageSet);
        $messagePages = pages($messageCount, $page, 20, $pageUrl, $array = array());

        $this->assign('messageList', $messageList);
        $this->assign("messageNotify", $this->configModel['message_notify']);
        $this->assign("messageType", $this->configModel['message_type']);
        $this->assign("messageStatus", $this->configModel['message_status']);
        $this->assign("messageOs", $this->configModel['message_os']);
        $this->assign('messagePages', $messagePages);
        $this->assign("actionStatus", $actionStatus);
        $this->assign("actionOs", $actionOs);
        $this->assign('page', $page);
        $this->assign('adminId', UID);

        $this->getViewer()->needLayout(false);
        $this->render('message_list');
    }

    public function addAction()
    {
        $dosubmit = daddslashes($this->postVar('dosubmit', ''));
        $uidBatch = daddslashes($this->postVar('uid_batch', ''));
        $uidBatch = str_replace(array("\n", "\r", "\t", '，', ' '), array(',', ',', ',', ',', ''), $uidBatch);
        $fileUpload = $_FILES['file_uplode'];
        $dateNow = date("Y-m-d H:i:s", time());

        $messageAdd['info_title'] = daddslashes(trim($this->postVar('info_title', '')));
        $messageAdd['content'] = daddslashes($this->postVar('content', ''));
        $messageAdd['share_msg'] = daddslashes($this->postVar('share_msg', ''));
        $messageAdd['info_notify'] = (int)$this->postVar('info_notify', 0);
        $messageAdd['message_type'] = (int)$this->postVar('message_type', 0);
        $messageAdd['os_type'] = daddslashes($this->postVar('message_os', ''));
        $messageAdd['start_date'] = daddslashes(trim($this->postVar('start_date', '')));
        $messageAdd['end_time'] = daddslashes(trim($this->postVar('end_date', '')));
        $messageAdd['uid_batch'] = trim($uidBatch, ",");
        $messageAdd['creater'] = UNAME;
        $messageAdd['click_url'] = daddslashes(trim($this->postVar('click_url', '')));
        $messageAdd['button_text'] = daddslashes(trim($this->postVar('button_text', '')));

        $rate = $_POST['rate'];
        $messageAdd['rate'] = '';

        //默认结束时间为当前时间后的一个月
        $defaultDate = date("Y-m-d", time() + 2592000);
        if ($dosubmit)
        {
            foreach ($rate as $val)
            {
                $messageAdd['rate'] = $messageAdd['rate'] . $val;
            }

            if (empty($messageAdd['info_title']))
            {
                $this->redirect('请填写标题!', '', 5);
                die();
            }
            elseif (1 == $messageAdd['message_type'] and empty($messageAdd['rate']))
            {
                $this->redirect('请填写升级尾号!', '', 5);
                die();
            }
            elseif (empty($messageAdd['content']))
            {
                $this->redirect('请填写内容!', '', 5);
                die();
            } elseif (!is_numeric($messageAdd['os_type']))
            {
                $this->redirect('请选择系统!', '', 5);
                die();
            } elseif (empty($messageAdd['start_date']) || empty($messageAdd['end_time']))
            {
                $this->redirect('上、下线时间不能小于当前时间且下线时间不能小于上线时间。', '', 5);
                die();
            } elseif ($dateNow > $messageAdd['start_date'] || $dateNow > $messageAdd['end_time'])
            {
                $this->redirect('上、下线时间不能小于当前时间且下线时间不能小于上线时间!', '', 5);
                die();
            } elseif ($messageAdd['end_time'] < $messageAdd['start_date'])
            {
                $this->redirect('上、下线时间不能小于当前时间且下线时间不能小于上线时间', '', 5);
                die();
            }

            $userInfo = array();
            if (1 != $messageAdd['message_type'])
            {
                $uidArr = array_filter(explode(",", trim($uidBatch, ",")));
                $uidArr = array_unique($uidArr); //去重

                //过滤uid
                foreach ($uidArr as $val)
                {
                    if (!is_numeric($val))
                        continue;

                    $userSet['uid'] = $val;
                    $userSet['status'] = 1;
                    if (1 == $messageAdd['os_type'])
                    {
                        $userSet['os_type'] = 'android';
                    } elseif (2 == $messageAdd['os_type'])
                    {
                        $userSet['os_type'] = 'ios';
                    }
//                    var_dump($val);die();
                    //Thrift连接
//                    $this->transport->open();
//                    $tp = $this->transport->isOpen();
//                    if(!$tp){
//                        $this->redirect('获取用户信息服务无法连接!', '', 5);
//                    }
//                    $userRe = $this->userClient->getUserInfo($val, '', '', 0);
//                    var_dump($userRe);

                    $userRe = $this->userModel->getUser($userSet);
                    if ($userRe)
                        $userInfo[] = $val;
                }
                if (empty($userInfo) || 2000 < count($userInfo))
                {
                    $this->redirect('用户ID不存在或数量超出限制!', '', 5);
                    die();
                }
            }

            //上传消息图片
            if (!empty($fileUpload['name']))
            {
                $newFilename = 'm_' . md5(microtime(true) . rand(10000000, 99999999));
                $messageAdd['url_images'] = $this->uploadFile($fileUpload, $newFilename);
                if (empty($messageAdd['url_images']))
                {
                    $this->redirect('上传失败,请联系管理员!', '', 3);
                    die();
                }
            } else
            {
                $messageAdd['url_images'] = '';
            }
            $backId = $this->messageModel->addMessage($messageAdd);
            if(1 == $messageAdd['message_type'])
            {
                $this->messageModel->execute("UPDATE a_message_send SET click_url = '{$messageAdd['click_url']}', button_text = '{$messageAdd['button_text']}', rate = '{$messageAdd['rate']}'  WHERE id = $backId");
            }
            else{
                $this->messageModel->execute("UPDATE a_message_send SET click_url = '{$messageAdd['click_url']}', button_text = '{$messageAdd['button_text']}' WHERE id = $backId");
            }
            if ($backId)
            {
                //$userInfo不为空时为私有消息
                if (!empty($userInfo))
                {
                    foreach ($userInfo as $val)
                    {
                        $messagePriAdd['mid'] = $backId;
                        $messagePriAdd['creater'] = UNAME;
                        $messagePriAdd['uid'] = $val;
                        $this->messagePrivateModel->addMessage($messagePriAdd);
                    }
                }
                $this->redirect('', '/admin/message/', 0);
                die();
            }
        }

        $this->assign('uidBatch', $uidBatch);
        $this->assign('defaultDate', $defaultDate);
        $this->assign("messageOs", $this->configModel['message_os']);

        $this->getViewer()->needLayout(false);
        $this->render('message_add');
    }

    public function editAction()
    {
        $messageSet['id'] = (int)$this->reqVar('mid', 0);
        $getMessage = $this->messageModel->getMessage($messageSet);
        if ($messageSet['id'] && !empty($getMessage['id']))
        {
            $dosubmit = daddslashes($this->postVar('dosubmit', ''));
            $uidBatch = daddslashes(trim($this->postVar('uid_batch', '')));
            $uidBatch = str_replace(array("\n", "\r", "\t", '，', ' '), array(',', ',', ',', ',', ''), $uidBatch);
            $fileUpload = $_FILES['file_uplode'];
            $dateNow = date("Y-m-d H:i:s", time());

            $messageSave['info_title'] = daddslashes(trim($this->postVar('info_title', '')));
            $messageSave['content'] = daddslashes($this->postVar('content', ''));
            $messageSave['share_msg'] = daddslashes($this->postVar('share_msg', ''));
            $messageSave['info_notify'] = (int)$this->postVar('info_notify', 0);
            $messageSave['os_type'] = daddslashes(trim($this->postVar('message_os', '')));
            $messageSave['start_date'] = daddslashes(trim($this->postVar('start_date', '')));
            $messageSave['end_time'] = daddslashes(trim($this->postVar('end_date', '')));

            $messageAdd['click_url'] = daddslashes(trim($this->postVar('click_url', '')));
            $messageAdd['button_text'] = daddslashes(trim($this->postVar('button_text', '')));

            $rate = $_POST['rate'];
            $messageAdd['rate'] = '';

            $messageSave['uid_batch'] = trim($uidBatch, ",");
            if ($dosubmit)
            {
                foreach ($rate as $val)
                {
                    $messageAdd['rate'] = $messageAdd['rate'] . $val;
                }

                if (empty($messageSave['info_title']))
                {
                    $this->redirect('请填写标题!', '', 5);
                    die();
                }

                elseif (empty($messageSave['content']))
                {
                    $this->redirect('请填写内容!', '', 5);
                    die();
                } elseif (!is_numeric($messageSave['os_type']))
                {
                    $this->redirect('请选择系统!', '', 5);
                    die();
                } elseif (empty($messageSave['start_date']) || empty($messageSave['end_time']))
                {
                    $this->redirect('上、下线时间不能小于当前时间且下线时间不能小于上线时间。', '', 5);
                    die();
                } elseif ($dateNow > $messageSave['start_date'] || $dateNow > $messageSave['end_time'])
                {
                    $this->redirect('上、下线时间不能小于当前时间且下线时间不能小于上线时间!', '', 5);
                    die();
                } elseif ($messageSave['end_time'] < $messageSave['start_date'])
                {
                    $this->redirect('上、下线时间不能小于当前时间且下线时间不能小于上线时间', '', 5);
                    die();
                }
                elseif ( 1 == $getMessage['message_type'] and empty($messageAdd['rate']))
                {
                    $this->redirect('请填写升级尾号!', '', 5);
                    die();
                }
                $userInfo = array();
                if (1 != $getMessage['message_type'])
                {
                    $uidArr = array_filter(explode(",", trim($uidBatch, ",")));
                    $uidArr = array_unique($uidArr); //去重

                    //过滤uid
                    foreach ($uidArr as $val)
                    {
                        if (!is_numeric($val))
                            continue;

                        $userSet['uid'] = $val;
                        $userSet['status'] = 1;
                        if (1 == $messageAdd['os_type'])
                        {
                            $userSet['os_type'] = 'android';
                        } elseif (2 == $messageAdd['os_type'])
                        {
                            $userSet['os_type'] = 'ios';
                        }
//                    var_dump($val);die();
                        //Thrift连接
//                    $this->transport->open();
//                    $tp = $this->transport->isOpen();
//                    if(!$tp){
//                        $this->redirect('获取用户信息服务无法连接!', '', 5);
//                    }
//                    $userRe = $this->userClient->getUserInfo($val, '', '', 0);
//                    var_dump($userRe);

                        $userRe = $this->userModel->getUser($userSet);
                        if ($userRe)
                            $userInfo[] = $val;
                    }
                    if (empty($userInfo) || 2000 < count($userInfo))
                    {
                        $this->redirect('用户ID不存在或数量超出限制!', '', 5);
                        die();
                    }
                }

                //上传消息图片
                if (!empty($fileUpload['name']))
                {
                    $newFilename = 'm_' . md5(microtime(true) . rand(10000000, 99999999));
                    $messageSave['url_images'] = $this->uploadFile($fileUpload, $newFilename);
                    if (empty($messageSave['url_images']))
                    {
                        $this->redirect('上传失败,请联系管理员!', '', 3);
                        die();
                    }
                } else
                {
                    $messageSave['url_images'] = '';
                }
                //api有一个小时缓存
                $messageRe = $this->messageModel->saveMessage($getMessage['id'], $messageSave);

                if(1 == $getMessage["message_type"])
                {
                    $this->messageModel->execute("UPDATE a_message_send SET click_url = '{$messageAdd['click_url']}', button_text = '{$messageAdd['button_text']}', rate = '{$messageAdd['rate']}'  WHERE id = {$getMessage['id']}");
                }
                else{
                    $this->messageModel->execute("UPDATE a_message_send SET click_url = '{$messageAdd['click_url']}', button_text = '{$messageAdd['button_text']}' WHERE id = {$getMessage['id']}");
                }

                if (!empty($messageRe))
                {
                    //记录操作日志
                    $logAdd['app'] = $this->_application;
                    $logAdd['controller'] = $this->_controller;
                    $logAdd['action'] = $this->_action;
                    $logAdd['content'] = json_encode($messageSet['id']);
                    $logAdd['ip'] = get_real_ip();
                    $logAdd['operat'] = UNAME;
                    $this->operateLogModel->addOpLog($logAdd);
                }

                //$userInfo不为空时为私有消息
                if ($messageRe && !empty($userInfo))
                {
                    $messageDelRe = $this->messagePrivateModel->saveMessage($getMessage['id'], array("status" => -1));
                    if ($messageDelRe)
                    {
                        foreach ($userInfo as $val)
                        {
                            $messagePriSet['mid'] = $getMessage['id'];
                            $messagePriSet['uid'] = $val;
                            if ($this->messagePrivateModel->getMessage($messagePriSet))
                            {
                                $this->messagePrivateModel->saveMessage($getMessage['id'], array("status" => 0), $val);
                            } else
                            {
                                $messagePriAdd['mid'] = $getMessage['id'];
                                $messagePriAdd['creater'] = UNAME;
                                $messagePriAdd['uid'] = $val;
                                $this->messagePrivateModel->addMessage($messagePriAdd);
                            }
                        }
                    }
                }
                $this->redirect('', '/admin/message/', 0);
                die();
            }
            $this->assign('getMessage', $getMessage);
            $this->assign("messageOs", $this->configModel['message_os']);
        }

        $this->getViewer()->needLayout(false);
        $this->render('message_edit');
    }

    public function auditAction()
    {
        $mid = (int)$this->reqVar('mid', 0);
        $page = (int)$this->reqVar('page', 1);
        $getMessage = $this->messageModel->getMessage(array('id' => $mid));
        if ($getMessage)
        {
            $messageaudit = $this->messageModel->messageauditSucceed($mid);
            if (!empty($messageaudit))
            {
                //记录操作日志
                $logAdd['app'] = $this->_application;
                $logAdd['controller'] = $this->_controller;
                $logAdd['action'] = $this->_action;
                $logAdd['content'] = json_encode($mid);
                $logAdd['ip'] = get_real_ip();
                $logAdd['operat'] = UNAME;
                $this->operateLogModel->addOpLog($logAdd);
            }
        }
        $this->redirect('', '/admin/message/?page=' . $page, 0);
    }

    public function submitAction()
    {
        $mid = (int)$this->reqVar('mid', 0);
        $page = (int)$this->reqVar('page', 1);
        $getMessage = $this->messageModel->getMessage(array('id' => $mid));
        if ($getMessage)
        {
            if (1 == $getMessage['message_type'])
            {
                $getRealIp = get_real_ip();
                $getRealIpStr = substr($getRealIp, 0, 7);

                //公用消息操作限制IP段
                $limitIp = array('192.168');
                if (empty($getRealIp) || !in_array($getRealIpStr, $limitIp))
                {
//                    $this->redirect('无权限操作,请联系管理员!', '/admin/message/', 5);
//                    die();
                }
            }
            $disposeRe = $this->messageModel->disposeSucceed($mid);
            if (!empty($disposeRe))
            {
                //记录操作日志
                $logAdd['app'] = $this->_application;
                $logAdd['controller'] = $this->_controller;
                $logAdd['action'] = $this->_action;
                $logAdd['content'] = json_encode($mid);
                $logAdd['ip'] = get_real_ip();
                $logAdd['operat'] = UNAME;
                $this->operateLogModel->addOpLog($logAdd);
            }
            $this->redirect('提交成功', '/admin/message/?page=' . $page, 1);
        } else
        {
//          $this->messageModel->getLastSql();
            die();
        }
    }

    public function detailAction()
    {
        $mid = (int)$this->reqVar('mid', 0);
        $page = (int)$this->reqVar('page', 1);
        if ($mid > 0)
        {
            $getMessage = $this->messageModel->getMessage(array("id" => $mid));

            $messageSet['mid'] = $mid;
            $messageCount = $this->messagePrivateModel->getMessageCount($messageSet);
            $messageList = $this->messagePrivateModel->getMessageList($messageSet, $page, 50);

            $pageUrl = "/admin/message/detail?mid=$mid&page=$page";
            $messagePages = pages($messageCount, $page, 60, $pageUrl, $array = array());

            $this->assign('getMessage', $getMessage);
            $this->assign('messageList', $messageList);
            $this->assign("messageStatus", $this->configModel['message_detail_status']);
            $this->assign('messagePages', $messagePages);

        }

        $this->assign('mid', $mid);
        $this->assign('page', $page);
        $this->assign("messageNotify", $this->configModel['message_notify']);
        $this->assign("messageType", $this->configModel['message_type']);
        $this->assign("messageStatus", $this->configModel['message_status']);
        $this->assign("messagePriStatus", $this->configModel['message_pri_status']);
        $this->getViewer()->needLayout(false);
        $this->render('message_detail');
    }

    //消息删除
    public function delAction()
    {
        $midArr = daddslashes($this->postVar('mid', ''));


        if (!empty($midArr))
        {
            $delArr = $delApiArr = array();
            foreach ($midArr as $key => $val)
            {

                $apiSendCallBack = '';
                $messageSet['id'] = $val;
                $messageSet['message_type'] = 1;
                // $messageSet['status'] = -1;
                $getMessage = $this->messageModel->getMessage($messageSet);

                if(intval($getMessage['status']) == 0)
                {
                    $re = $this->messageModel->deleteMessage($val);
                }

                else if (!empty($getMessage['callback_info']) && in_array(UID, array(1, 2, 3, 4, 5)))
                {

                    $re = $this->messageModel->deleteMessage($val);

                    if ($re)
                    {
                        //删除发送成功的公共消息

                        $sendData['msgid'] = $getMessage['callback_info'];
                        $apiData = json_encode($sendData);
                        $apiData = urlencode($apiData);

                        $apiSendJsonRe = file_get_contents(_API_URL_ . "/admin_public_del_msg.do?data={$apiData}");


                        $apiSendRe = json_decode($apiSendJsonRe, true);
                        if (!empty($apiSendRe['data']['rs']) && 1 == $apiSendRe['data']['rs'])
                        {
                            $apiSendCallBack = "succ";
                        } elseif (isset($apiSendRe['errcode']) && isset($apiSendRe['msg']))
                        {
                            $apiSendCallBack = $apiSendRe['msg'] . "/errcode_" . $apiSendRe['errcode'];
                        }
                        $delArr[$val] = $apiSendCallBack;
                    }

                } else
                {

                    $timeInterval = (5 - intval(time() - strtotime($getMessage['createtime'])) / 60);
                    if ($timeInterval < 0)
                        $timeInterval = 0;

                    $timeIntervalStr = strval($timeInterval);
                    echo "<script>if (confirm('暂时不能删除，请于审核通过后5分钟尝试，如果失败，请联系开发确认消息状态！'))
                            location.href = '/admin/message/'; </script>";
//                    confirm("暂时不能删除" . "请于" . strval((time() - strtotime($getMessage['createtime'])) / 60) . "分钟后尝试！");
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

                $this->redirect('', '/admin/message/', 0);

            }

        }

        $this->redirect('', '/admin/message/', 0);

    }

    private function uploadFile($filename, $newFilename)
    {
        if (empty($filename) || empty($newFilename))
        {
            return false;
        }

        $path = "../data/message/"; //上传路径
        if (!file_exists($path))
        {
            mkdir("$path", 0700);
        }
        //允许上传的文件格式
        $tp = array("image/gif", "image/pjpeg", "image/jpeg", "image/jpg", "image/png");
        if (!in_array($filename["type"], $tp))
        {
            return false;
        }

        $flag = 0;
        if ($filename["name"] && !empty($newFilename))
        {
            $imgType = explode("/", $filename["type"]);
            if (in_array($imgType[1], array('pjpeg', 'jpeg', 'jpg')))
            {
                $imgType[1] = 'jpg';
            }
            $file2name = $newFilename . '.' . $imgType[1];
            $file2 = $path . $file2name;
            $flag = 1;
        }

        $result = false;
        if ($flag)
            $result = move_uploaded_file($filename["tmp_name"], $file2);

        if ($result)
        {
            return _PHOTO_URL_ . '/hbdata/message/' . $file2name;
        } else
        {
            return false;
        }
    }

}