<?php
/**
 * 兑换记录管理
 *
 * @category   Leb
 * @package    Leb_Action
 * @author     lihui
 * @version    $Id: exchange.php 2014-09-03 9:58:00 lihui
 * @copyright (c) 2014 dianjoy.com
 * @license
 */
class exchangeController extends Application
{
    private $exchangeModel;
    private $inviteHoldLogModel;
    private $inviteHoldExceptionModel;
    private $userModel;
    private $configModel;
    private $operateLogModel;
    private $userClient;
    private $transport;

    public function execute($plugins)
    {
        $this->exchangeModel = $this->loadAppModel('Exchange');
        $this->inviteHoldLogModel = $this->loadModel('Invite_hold_log');
        $this->inviteHoldExceptionModel = $this->loadModel('Invite_hold_exception');
        $this->userModel = $this->loadAppModel('User');
        $this->configModel = C('global.php');
        $this->operateLogModel = $this->loadModel('Operate_log',array(),'admin');

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
        $search  = daddslashes($this->postVar('search',''));
        $keyword = daddslashes($this->reqVar('keyword',''));
        $startTime = daddslashes($this->reqVar('start_time',''));
        $endTime   = daddslashes($this->reqVar('end_time',''));
        $actionType = (int)$this->reqVar('action_type',0);
        $actionPayType = (int)$this->reqVar('action_pay_type',0);
        $actionPayStatus = daddslashes($this->reqVar('action_pay_status',''));
        $pages = (int)$this->reqVar('page',1);

        $pageUrl = "/admin/exchange/";
        if(!empty($keyword)){
            if(1== $actionType){
                $exchangeSet['uid'] = $keyword;
            }elseif(2== $actionType){
                $exchangeSet['id'] = $keyword;
            }elseif(3 == $actionType){
                //Thrift连接
                $this->transport->open();
                $tp = $this->transport->isOpen();
                if(!$tp){
                    $this->redirect('获取用户信息服务无法连接!', '', 5);
                }

                //获取用户信息
                $userRe = $this->userClient->getUserInfoAllUser($keyword, '', '', 0);
                if(!empty($userRe->uid)){
                    $exchangeSet['uid'] = $userRe->uid;
                }
            }elseif(4== $actionType){
                $exchangeSet['present_id'] = $keyword;
            }elseif(5== $actionType){
                $exchangeSet['device_id'] = $keyword;
            }elseif(6== $actionType){
                $exchangeSet['ip'] = $keyword;
            }elseif(7== $actionType){
                $exchangeSet['admin'] = $keyword;
            }
            $pageUrl .= "?action_type=$actionType&keyword=$keyword";
        }

        if(!empty($startTime)){
            $exchangeSet['start_time'] = $startTime;
            if(!empty($keyword)){
                $pageUrl .= "&start_time=$startTime";
            }else{
                $pageUrl .= "?start_time=$startTime";
            }
        }
        if(!empty($endTime)){
            $exchangeSet['end_time'] = $endTime;
            if(!empty($keyword) || !empty($startTime)){
                $pageUrl .= "&end_time=$endTime";
            }else{
                $pageUrl .= "?end_time=$endTime";
            }
        }
        if(!empty($actionPayType)){
            $exchangeSet['ptype'] = $actionPayType;
            if(!empty($keyword) || !empty($startTime) || !empty($endTime) || !empty($actionPayType)){
                $pageUrl .= "&action_pay_type=$actionPayType";
            }else{
                $pageUrl .= "?action_pay_type=$actionPayType";
            }
        }
        if(!empty($actionPayStatus)){
            $exchangeSet['pay_status'] = $actionPayStatus;
            if(!empty($keyword) || !empty($startTime) || !empty($endTime) || !empty($actionPayType)){
                $pageUrl .= "&action_pay_status=$actionPayStatus";
            }else{
                $pageUrl .= "?action_pay_status=$actionPayStatus";
            }
        }

        $lastHour = date("Y-m-d H:i:s",time()-3600);
        $exchangeSet['condition'] = " AND ctime <= '$lastHour' ";
        $exchangeList  = $this->exchangeModel->getExchangeList($exchangeSet,$pages,20);
        $exchangeCount = $this->exchangeModel->getExchangeCount($exchangeSet);
        $exchangePages = pages($exchangeCount,$pages,20,$pageUrl,array());

        $this->assign('keyword', $keyword);
        $this->assign('startTime', $startTime);
        $this->assign('endTime', $endTime);
        $this->assign('exchangeList', $exchangeList);
        $this->assign("exchangeSelect", $this->configModel['exchange_select']);
        $this->assign("payType", $this->configModel['pay_type']);
        $this->assign("payStatus", $this->configModel['pay_status']);
        $this->assign('actionType', $actionType);
        $this->assign('actionPayType', $actionPayType);
        $this->assign('actionPayStatus', $actionPayStatus);
        $this->assign('exchangePages', $exchangePages);
        $this->assign('adminId', UID);

        $this->assign('page', $pages);

        $this->getViewer()->needLayout(false);
        $this->render('exchange');
    }

    public function detailAction()
    {
        $payId = (int)$this->reqVar('id',0);
        $actionPayType = (int)$this->reqVar('pay_type',0);
        $actionPayStatus = (int)$this->reqVar('pay_status',0);
        $listpage = (int)$this->reqVar('listpage',1);
        $page = (int)$this->reqVar('page',1);

        $pageUrl = "/admin/exchange/detail?id=$payId";
        $pageUrl .= "&listpage=$listpage&pay_type=$actionPayType&pay_status=$actionPayStatus";

        $deviceTag = 0;
        $auditSet['id'] = $payId;
        $exchangeRe = $this->exchangeModel->getExchange($auditSet);
        if(!empty($exchangeRe) && !empty($exchangeRe['id']) && !empty($exchangeRe['uid'])){
            //设备号不匹配标红
            if(!empty($exchangeRe['device_id'])){
                $deviceIdStr = substr($exchangeRe['device_id'],0,2);
                if(!in_array($deviceIdStr,array('86','35','99','a0','a1','45','48'))){
                    $deviceTag = 1;
                }
            }

            //按UID统计充值记录数
            $auditUidSet['uid'] = $exchangeRe['uid'];
            $auditUidSet['condition'] = " AND pay_status IN(1,2,3)";
            $exchangeUidCount = $this->exchangeModel->getExchangeCount($auditUidSet);

            //按pay_content统计充值数量
            $auditContentSet['pay_content'] = $exchangeRe['pay_content'];
            $auditContentSet['condition'] = " AND pay_status IN(1,2,3)";
            $exchangePayCount = $this->exchangeModel->getExchangeCount($auditContentSet);

            //判断上次一条有效记录
            $lastValidRe = $this->exchangeModel->getExchange(array('uid'=>$exchangeRe['uid'],'pay_status'=>3,'orderby'=>' ctime desc'));
            if(!empty($lastValidRe) && !empty($lastValidRe['ctime'])){
                $lastValidTime = $lastValidRe['ctime'];
            }else{
                $lastValidTime = '';
            }

            //邀请统计
            $inviteCountRe[0] = array('c_num'=>0,'600p'=>0,'520p'=>0,'500p'=>0,'has_ad'=>0,'has_rcatch'=>0,'has_3rcatch'=>0,
                'has_invitation'=>0,'MIN'=>0,'MAX'=>0,'AVG'=>0);
            $inviteCountRe = $this->userModel->query("SELECT COUNT(*) as c_num,
                                                        SUM(IF(score>600,1,0)) AS 600p,
                                                        SUM(IF(score>520,1,0)) AS 520p,
                                                        SUM(IF(score>500,1,0)) AS 500p,
                                                        SUM(IF(score_ad>0,1,0)) AS has_ad,
                                                        SUM(IF(score_right_catch>0,1,0)) AS has_rcatch,
                                                        SUM(IF(score_right_catch>20,1,0)) AS has_3rcatch,
                                                        SUM(IF(score_register>0,1,0)) AS has_invitation,
                                                        MIN(IF(score>500,score,5000)) AS MIN,
                                                        MAX(score) AS MAX,
                                                        AVG(score) AS AVG
                                                        FROM z_user WHERE invite_code='{$exchangeRe['uid']}'");
            $inviteCount = $inviteCountRe[0]['c_num'];

            //Thrift连接
            $this->transport->open();
            $tp = $this->transport->isOpen();
            if(!$tp){
                $this->redirect('获取用户信息服务无法连接!', '', 5);
            }

            //用户信息
            //$userRe = $this->userModel->getUser(array("uid"=>$exchangeRe['uid']));
            $userInfo = $this->userClient->getUserInfoByUid($exchangeRe['uid']);
            $userRe['uid'] = !empty($userInfo->uid) ? $userInfo->uid : '';
            $userRe['password'] = !empty($userInfo->pword) ? $userInfo->pword : '';
            $userRe['pnum'] = !empty($userInfo->mobile) ? $userInfo->mobile : '';
            $userRe['device_id'] = !empty($userInfo->device_id) ? $userInfo->device_id : '';
            $userRe['imsi'] = !empty($userInfo->imsi) ? $userInfo->imsi : '';
            $userRe['status'] = !empty($userInfo->status) ? $userInfo->status : '';
            $userRe['invite_code'] = !empty($userInfo->invite_code) ? $userInfo->invite_code : '';
            $userRe['ctime'] = !empty($userInfo->ctime) ? $userInfo->ctime : '';

            //用户积分信息
            $userScore = $this->userClient->getUserScoreList($exchangeRe['uid']);
            $userRe['score'] = !empty($userScore['score']) ? $userScore['score'] : 0;
            $userRe['score_ad'] = !empty($userScore['score_ad']) ? $userScore['score_ad'] : 0;
            $userRe['score_right_catch'] = !empty($userScore['score_right_catch']) ?$userScore['score_right_catch'] :0;
            $userRe['score_register'] = !empty($userScore['score_register']) ? $userScore['score_register'] :0;
            $userRe['score_other'] = !empty($userScore['score_other']) ? $userScore['score_other'] : 0;
            $userRe['score_task'] = !empty($userScore['score_task']) ? $userScore['score_task'] : 0;
            $userRe['update_time'] = !empty($userScore['update_time']) ? date("Y-m-d H:i:s",$userScore['update_time']) : '';

            $inviteList = $this->userModel->getUserList(array("invite_code"=>$exchangeRe['uid']),$page,100);
            if($inviteList){
                foreach($inviteList as $key=>$val){
                    $isHold = $this->inviteHoldLogModel->getInviteHoldLog(array('uid'=>$val['uid']));
                    if($isHold){
                        $inviteList[$key]['hold'] = 1;
                    }else{
                        $inviteList[$key]['hold'] = 0;
                    }
                }
            }
            $invitePages = pages($inviteCount,$page,100,$pageUrl,$array = array());

            //判断是否为特例
            $isException = 0;
            $exceptionRe = $this->inviteHoldExceptionModel->getInviteHoldException($exchangeRe['uid']);
            if($exceptionRe){
                $isException = 1;
            }

            $this->assign('exchangeRe', $exchangeRe);
            $this->assign('deviceTag', $deviceTag);
            $this->assign('exchangeUidCount', $exchangeUidCount);
            $this->assign('exchangePayCount', $exchangePayCount);
            $this->assign('lastValidTime', $lastValidTime);
            $this->assign('inviteCountRe', $inviteCountRe[0]);
            $this->assign('userRe', $userRe);
            $this->assign("payType", $this->configModel['pay_type']);
            $this->assign("payStatus", $this->configModel['pay_status']);
            $this->assign("userStatus", $this->configModel['user_status']);
            $this->assign('inviteList', $inviteList);
            $this->assign('inviteCount', $inviteCount);
            $this->assign('invitePages', $invitePages);
            $this->assign('isException', $isException);
        }

        $this->getViewer()->needLayout(false);
        $this->render('exchange_detail');
    }

    public function refundAction()
    {
        if(!in_array(UID,array(1,2,3,4,5,6,29,30))){
            $this->redirect('无该操作权限!', '', 3);
            die();
        }
        $payId = (int)$this->reqVar('pay_id',0);
        $dosubmit = daddslashes($this->postVar('dosubmit',''));
        $remark = daddslashes($this->postVar('remark',''));

        $setTimeOut = 0;

        //支付宝退款
        $exchangeRe = $this->exchangeModel->getExchange(array('id'=>$payId));
        if(!empty($exchangeRe['id']) && !empty($exchangeRe['uid'])){
            if(!empty($dosubmit)){
                if(empty($remark)){
                    $this->redirect('说明不能为空!', '', 5);
                    die();
                }
                //退款
                $this->exchangeModel->artificialRefund($exchangeRe['id'],$remark,UNAME);
                $setTimeOut = 1;

                //邮件提醒
                $this->sendMail($exchangeRe['id'],$remark);

                //操作记录
                $opSet['pay_id'] = $payId;
                $this->oplog($opSet);

            }
        }
        $this->assign('setTimeOut', $setTimeOut);
        $this->assign('payId', $payId);
        $this->getViewer()->needLayout(false);
        $this->render('exchange_refund');
    }

    private function sendMail($exchangeId,$remark)
    {
    }

    public function sendAction()
    {
        die("11");
        // 实例化邮件类
        $sendMail = new Plugin_Mail();

        // 发送邮件
        $headers['Subject'] = '123123';
        $test = $sendMail->send('hui.li@dianjoy.com',$headers,'2222');
        var_dump($test);
        die("sdf");
    }

    private function oplog($addContent)
    {
        if(empty($addContent)){
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



    public function refund_listAction()
    {
        $pages = (int)$this->reqVar('page',1);
        $pageUrl = "/admin/exchange/refund_list";


        $startTime = daddslashes($this->reqVar('start_time', ''));
        $endTime = daddslashes($this->reqVar('end_time', date('Y-m-d', time())));

        if (!empty($endTime))
        {
            $endTime = date("Y-m-d", strtotime($endTime . ' + 1 day'));

            $pageUrl .= "?end_time=$endTime";
        }
        if (!empty($startTime))
        {
            $pageUrl .= "&start_time=$startTime";
        }


        $limit_start = ($pages-1) * 60 ;

        $op_list = $this->exchangeModel->query("SELECT content FROM a_operate_log WHERE ACTION  = 'refund' AND operatetime > '$startTime' AND operatetime < '$endTime'  limit $limit_start, 60;");

        $exchangeCount = intval($this->exchangeModel->query("SELECT count(*) as count_ FROM a_operate_log WHERE ACTION  = 'refund' AND operatetime > '$startTime' AND operatetime < '$endTime';")[0]['count_']);

        $pay_id_list = [];
        foreach($op_list as $val)
        {
            $pay_id_list[] = substr($val['content'], 10, strlen($val['content']) - 11);
        }
        $pay_id_str = "(" . join(",", $pay_id_list) . ")";

        $refund_list = $this->exchangeModel->query("SELECT * FROM z_present_exchange WHERE pay_status = 6 AND id in $pay_id_str;");

        $exchangePages = pages($exchangeCount, $pages, 60, $pageUrl, array());

        $endTime = date("Y-m-d", strtotime($endTime . ' - 1 day'));

        $sum_cur = $this->exchangeModel->query("SELECT SUM(pay) as s FROM z_present_exchange WHERE pay_status = 6 AND id in $pay_id_str;")[0]['s'];


        $op_list = $this->exchangeModel->query("SELECT content FROM a_operate_log WHERE ACTION  = 'refund' AND operatetime > '$startTime' AND operatetime < '$endTime';");

        $pay_id_list = "(";
        foreach($op_list as $val)
        {
            //   {"pay_id":998883}
            $pay_id_list .= substr($val['content'], 10, strlen($val['content']) - 11) . ", ";
        }
        $pay_id_list .= "0)";

        $sum_all = $this->exchangeModel->query("SELECT SUM(pay) as s FROM z_present_exchange WHERE pay_status = 6 and update_time > '$startTime' AND update_time < '$endTime' AND id in $pay_id_list")[0]['s'];

        $this->assign('startTime', $startTime);
        $this->assign('endTime', $endTime);
        $this->assign('exchangeList', $refund_list);
        $this->assign('sum_cur', $sum_cur);
        $this->assign('sum_all', $sum_all);

        $this->assign('exchangePages', $exchangePages);
        $this->assign('adminId', UID);
        $this->assign('page', $pages);

        $this->getViewer()->needLayout(false);
        $this->render('exchange_refund_list');
    }


}
