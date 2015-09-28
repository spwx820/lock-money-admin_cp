<?php
/**
 * 后台短信给用户发送密码
 *
 * @category   Leb
 * @package    Leb_Action
 * @author     lihui
 * @version    $Id: smspasswd.php 2014-09-15 9:58:00 lihui
 * @copyright (c) 2014 dianjoy.com
 * @license
 */
class smspasswdController extends Application
{
    private $configModel;
    private $userModel;
    private $smsSendModel;
    private $smspassModel;
    private $userClient;
    private $transport;

    public function  execute($plugins)
    {
        $this->configModel = C('global.php');
        $this->userModel = $this->loadAppModel('User');
        $this->smsSendModel = $this->loadAppModel('Smssend_passwd');
        $this->smspassModel = $this->loadModel('Smspasswd');

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
        $mobile = daddslashes($this->postVar('sms_mobile', ''));
        $startTime = daddslashes($this->postVar('start_time', ''));
        $endTime = daddslashes($this->postVar('end_time', ''));
        $page = (int)$this->reqVar('page', 1);

        if (!empty($search) && !empty($mobile))
        {
            $passwdSet['mobile'] = $mobile;
        }

        if (!empty($startTime))
        {
            $passwdSet['start_time'] = $startTime;
        }
        if (!empty($endTime))
        {
            $passwdSet['end_time'] = $endTime;
        }

        $passwdList = $this->smspassModel->getPasswdList($passwdSet, $page, 20);
        $passwdCount = $this->smspassModel->getPasswdCount($passwdSet);
        $passwdPages = pages($passwdCount, $page, 20, '', $array = array());

        $this->assign('mobile', $mobile);
        $this->assign('passwdList', $passwdList);
        $this->assign("smspwStatus", $this->configModel['smspw_status']);
        $this->assign('passwdPages', $passwdPages);

        $this->getViewer()->needLayout(false);
        $this->render('smspasswd');
    }

    public function addAction()
    {
        $dosubmit = daddslashes($this->postVar('dosubmit', ''));
        $password = daddslashes($this->postVar('sms_password', ''));
        $smsAdd['mobile'] = daddslashes($this->postVar('sms_mobile', ''));
        $smsAdd['creater'] = UNAME;
        $smsAdd['client_ip'] = Util::getRealIp();
        if (!empty($dosubmit) && !empty($smsAdd['mobile']) && !empty($password))
        {
            $smsAdd['password'] = md5($password . 'dianABCDEF12');
            $backId = $this->smspassModel->addPasswd($smsAdd);
            if (!empty($backId))
            {
                //Thrift连接
                $this->transport->open();
                $tp = $this->transport->isOpen();
                if (!$tp)
                {
                    $this->redirect('获取用户信息服务无法连接!', '', 5);
                }

                //获取用户信息
                $userRe = $this->userClient->getUserInfo($smsAdd['mobile'], '', '', 0);
                if (empty($userRe->uid) || empty($userRe->mobile))
                {
                    $this->redirect('无法获取用户信息!', '', 5);
                }

                $smsSend = $smsAdd;
                $smsSend['client_ip'] = '218.247.145.70';
                $smsSend['mobile'] = trim($smsAdd['mobile']);
                $smsSend['password'] = $password;
                $re = $this->smsSendModel->send($smsSend);
                if (!empty($re['re']) && $re['re'] == 1)
                {
                    $this->smspassModel->passwdSendSucceed($backId);
                    $this->userClient->updateUserPassword($password, $userRe->uid, $userRe->mobile);

//                    //清楚旧用户缓存
//                    $this->clearRedis($userRe['uid'],$userRe['pnum'],$userRe['device_id']);
//                    $this->userModel->updatePassword($smsAdd['mobile'],$smsAdd['password']);
                } else
                {
                    $wrongMsg = $re['re'] . ',' . $re['msg'];
                    $this->smspassModel->passwdSendFail($backId, $wrongMsg);
                }
            }
            $this->redirect('', '/admin/smspasswd/index', 0);
        }

        $this->getViewer()->needLayout(false);
        $this->render('smspasswd_add');
    }

//    private function clearRedis($uid,$pnum,$deviceId)
//    {
//        //清理redis缓存
//        $redis = Leb_Dao_Redis::getInstance();
//
//        if(!empty($uid)){
//            $uKey  = '_ZHUAN_U_L'.$uid;
//            $uInfo = $redis->get($uKey);
//            if($uInfo)
//                $redis->del($uKey);
//
//            $uGKey  = '_ZHUAN_U_S_L_G_'.$uid;
//            $uGInfo = $redis->get($uGKey);
//            if($uGInfo)
//                $redis->del($uGInfo);
//
//            $uPKey  = '_ZHUAN_P_U'.$uid;
//            $uPInfo = $redis->get($uPKey);
//            if($uPInfo)
//                $redis->del($uPInfo);
//        }
//
//        if(!empty($pnum)){
//            $pKey  = '_ZHUAN_U_P'.$pnum;
//            $pInfo = $redis->get($pKey);
//            if($pInfo)
//                $redis->del($pInfo);
//        }
//
//        if(!empty($deviceId)){
//            $dKey = '_ZHUAN_U_I_B_D_I'.$deviceId;
//            $dInfo = $redis->get($dKey);
//            if($dInfo)
//                $redis->del($dInfo);
//        }
//    }

    public function ajaxmobileAction()
    {
        $mobile = daddslashes($this->getVar('sms_mobile', ''));
        if (!empty($mobile))
        {
            //Thrift连接
            $this->transport->open();
            $tp = $this->transport->isOpen();
            if ($tp)
            {
                //获取用户信息
                $userRe = $this->userClient->getUserInfo($mobile, '', '', 0);
                if ($userRe && !empty($userRe->uid))
                {
                    exit("1");
                }
            }
        }
        exit("0");
    }

}