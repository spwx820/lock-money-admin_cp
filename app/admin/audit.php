<?php
/**
 * 审核管理
 *
 * @category   Leb
 * @package    Leb_Action
 * @author     lihui
 * @version    $Id: audit.php 2014-09-03 9:58:00 lihui
 * @copyright (c) 2014 dianjoy.com
 * @license
 */
class auditController extends Application
{
    private $exchangeModel;
    private $exchangeHModel;
    private $userModel;
    private $inviteHoldLogModel;
    private $inviteHoldExceptionModel;
    private $operateLogModel;
    private $configModel;
    private $userClient;
    private $transport;

    public function execute($plugins)
    {
        $this->exchangeModel = $this->loadAppModel('Exchange');
        $this->exchangeHModel= $this->loadAppModel('Exchange_hold');
        $this->userModel = $this->loadAppModel('User');
        $this->inviteHoldLogModel = $this->loadModel('Invite_hold_log');
        $this->inviteHoldExceptionModel = $this->loadModel('Invite_hold_exception');
        $this->operateLogModel = $this->loadModel('Operate_log',array(),'admin');
        $this->configModel = C('global.php');

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
        $actionPayType = (int)$this->reqVar('action_pay_type',0);
        $actionType = (int)$this->reqVar('action_type',0);
        $page = (int)$this->reqVar('page',1);

        $pageUrl = "/admin/audit/";
        if(!empty($keyword)){
            if(1 == $actionType){
                $auditSet['uid'] = $keyword;
            }elseif(2 == $actionType){
                $auditSet['id'] = $keyword;
            }elseif(3 == $actionType){
                //Thrift连接
                $this->transport->open();
                $tp = $this->transport->isOpen();
                if(!$tp){
                    $this->redirect('获取用户信息服务无法连接!', '', 5);
                }

                //获取用户信息
                $userRe = $this->userClient->getUserInfo($keyword, '', '', 0);
                if(!empty($userRe->uid)){
                    $auditSet['uid'] = $userRe->uid;
                }
            }
            $pageUrl .= "?action_type=$actionType&keyword=$keyword";
        }
        if(!empty($actionPayType)){
            $auditSet['ptype'] = $actionPayType;
            $pageUrl .= !empty($keyword) ? '&' : '?';
            $pageUrl .= "action_pay_type=$actionPayType";
        }
        $auditSet['pay_status'] = 1;
        $auditSet['end_time'] = date("Y-m-d H:i:s",time()-3600);
        $auditList = $this->exchangeModel->getExchangeList($auditSet,$page,60);
        if($auditList){
            foreach($auditList as $key=>$val){
                $exchangeHSet['exchange_id'] = $val['id'];
                $isH = $this->exchangeHModel->getExchangeH($exchangeHSet);
                if($isH){
                    $auditList[$key]['ish'] = 1;
                    $auditList[$key]['remark'] = $isH['remark'];
                }else{
                    $auditList[$key]['ish'] = 0;
                    $auditList[$key]['remark'] = '';
                }
            }
        }

        $auditCount = $this->exchangeModel->getExchangeCount($auditSet);
        $auditSum = $this->exchangeModel->getExchangeSum($auditSet);
        $auditFinishCount = $this->exchangeModel->getExchangeCount(array("pay_status"=>2));
        $auditFinishSum = $this->exchangeModel->getExchangeSum(array("pay_status"=>2));
        $auditErrorCount = $this->exchangeModel->getExchangeCount(array("pay_status"=>4));
        $auditErrorSum = $this->exchangeModel->getExchangeSum(array("pay_status"=>4));

        $auditTodaySet['pay_status'] = 3;
        $dateNow = date("Y-m-d 00:00:00",time());
        $auditTodaySet['condition'] = " AND update_time>='{$dateNow}'";
        $auditToDayCount = $this->exchangeModel->getExchangeCount($auditTodaySet);
        $auditToDaySum = $this->exchangeModel->getExchangeSum($auditTodaySet);

        $auditPages = pages($auditCount,$page,60,$pageUrl,$array = array());

        $this->assign('keyword', $keyword);
        $this->assign('actionPayType', $actionPayType);
        $this->assign('actionType', $actionType);
        $this->assign('auditList', $auditList);
        $this->assign('auditCount', $auditCount);
        $this->assign('auditSum', (int)$auditSum);
        $this->assign('auditFinishCount', $auditFinishCount);
        $this->assign('auditFinishSum', (int)$auditFinishSum);
        $this->assign('auditErrorCount', $auditErrorCount);
        $this->assign('auditErrorSum', (int)$auditErrorSum);
        $this->assign('auditToDayCount', (int)$auditToDayCount);
        $this->assign('auditToDaySum', (int)$auditToDaySum);

        $this->assign("payType", $this->configModel['pay_type']);
        $this->assign("payStatus", $this->configModel['pay_status']);
        $this->assign('auditPages', $auditPages);
        $this->assign('page', $page);

        $this->getViewer()->needLayout(false);
        $this->render('audit_list');
    }

    public function auditAction()
    {
        $dosubmit = daddslashes($this->postVar('dosubmit',''));
        $payId = (int)$this->reqVar('pay_id',0);
        $audit = (int)$this->postVar('audit',0);
        $page  = (int)$this->reqVar('page',1);
        $listpage = (int)$this->reqVar('listpage',1);
        $paytype  = (int)$this->reqVar('pay_type',0);

        $deviceTag = 0;
        $auditSet['pay_status'] = 1;
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

            //跳转到下一页
            if(!empty($paytype)){
                $nextPageSet['ptype'] = $paytype;
            }
            $nextPageSet['pay_status'] = 1;
            $nextPageSet['condition'] = " AND id<$payId";
            $nextPageSet['orderby'] = " id desc";
            $nextPageRe = $this->exchangeModel->getExchange($nextPageSet);

            $nextPage = !empty($nextPageRe['id'])?$nextPageRe['id']:0;
            $this->assign('nextPay', $nextPage);

            //Thrift连接
            $this->transport->open();
            $tp = $this->transport->isOpen();
            if(!$tp){
                $this->redirect('获取用户信息服务无法连接!', '', 5);
            }

            if(!empty($dosubmit)){
                $content = '';
                if(1 == $audit && 3 == $exchangeRe['ptype']){
                    //支付状态特殊处理
                    $this->exchangeModel->alipaySucceed($exchangeRe['id'],UNAME);
                    $this->delhold($exchangeRe['id']);
                    $content = '通过(支付宝)';
                }elseif(1 == $audit){
                    $this->exchangeModel->paySucceed($exchangeRe['id'],UNAME);
                    $this->delhold($exchangeRe['id']);
                    $content = '通过';
                }elseif(2 == $audit){
//                    $this->userModel->noPlayUser($exchangeRe['uid']);
                    $this->userClient->updateUserStatus($exchangeRe['uid'], 2);

                    $this->exchangeModel->noPaySucceed($exchangeRe['uid'],UNAME);
                    $this->delhold($exchangeRe['id']);
                    $content = '封号';
                }elseif(3 == $audit){
                    //退款
                    $this->exchangeModel->refundSucceed($exchangeRe['id'],'',UNAME);
                    $this->delhold($exchangeRe['id']);
                    $content = '退款';
                }

                $logAdd['app'] = $this->_application;
                $logAdd['controller'] = $this->_controller;
                $logAdd['action'] = $this->_action;
                $logAdd['content'] = json_encode(array($payId=>$content));
                $logAdd['ip'] = get_real_ip();
                $logAdd['operat'] = UNAME;
                $this->operateLogModel->addOpLog($logAdd);

                //跳转到下一页
                if($nextPageRe){
                    $reUrl = '/admin/audit/audit?pay_id='.$nextPageRe['id'].'&listpage='.$listpage;
                    if(!empty($paytype)){
                        $reUrl .= '&pay_type='.$paytype;
                    }
                }else{
                    $reUrl = '/admin/audit/?page='.$listpage;
                    if(!empty($paytype)){
                        $reUrl .= '&action_pay_type='.$paytype;
                    }
                }
                $this->redirect('', $reUrl, 0);
            }

            //暂缓判断
            $exchangeHSet['exchange_id'] = $payId;
            $exchangeH = $this->exchangeHModel->getExchangeH($exchangeHSet);
            if($exchangeH){
                $this->assign('exchangeHStatus', "暂缓");
                $this->assign('exchangeHRemark', $exchangeH['remark']);
            }else{
                $this->assign('exchangeHStatus', "待审核");
                $this->assign('exchangeHRemark', '');
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

//           $inviteCount = $this->userModel->getUserCount(array("invite_code"=>$exchangeRe['uid']));
            $inviteCount = $inviteCountRe[0]['c_num'];

            //用户信息
            $userRe = $this->userModel->getUser(array("uid"=>$exchangeRe['uid']));
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
            $invitePages = pages($inviteCount,$page,100,'',$array = array());

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
            $this->assign("userStatus", $this->configModel['user_status']);
            $this->assign('inviteList', $inviteList);
            $this->assign('inviteCount', $inviteCount);
            $this->assign('invitePages', $invitePages);
            $this->assign('listpage', $listpage);
            $this->assign('auditPayType', $paytype);
            $this->assign('isException', $isException);
        }
        $this->assign('payId', $payId);
        $this->getViewer()->needLayout(false);
        $this->render('audit');
    }

    private function delhold($exchange_id)
    {
        $exchangeHSet['exchange_id'] = $exchange_id;
        $isH = $this->exchangeHModel->getExchangeH($exchangeHSet);
        if($isH){
            $this->exchangeHModel->deleteExchangeH($exchange_id);
        }
        return true;
    }

    public function auditholdAction()
    {
        $payId = (int)$this->reqVar('pay_id',0);
        $dosubmit = daddslashes($this->postVar('dosubmit',''));
        $remark   = daddslashes($this->postVar('remark',''));
        $setTimeOut = 0;

        $exchangeRe = $this->exchangeModel->getExchange(array('id'=>$payId));
        if(!empty($exchangeRe['id']) && !empty($exchangeRe['uid'])){
            if(!empty($dosubmit)){
                $exchangeHSet['exchange_id'] = $exchangeRe['id'];
                $isH = $this->exchangeHModel->getExchangeH($exchangeHSet);
                if($isH){
                    $this->exchangeHModel->saveExchangeH($exchangeRe['id'],$remark);
                }else{
                    $exchangeHAdd['exchange_id'] = $exchangeRe['id'];
                    $exchangeHAdd['remark'] = $remark;
                    $this->exchangeHModel->addExchangeH($exchangeHAdd);
                }

                //操作记录
                $logAdd['app'] = $this->_application;
                $logAdd['controller'] = $this->_controller;
                $logAdd['action'] = $this->_action;
                $logAdd['content'] = json_encode(array($payId=>"暂缓"));
                $logAdd['ip'] = get_real_ip();
                $logAdd['operat'] = UNAME;
                $this->operateLogModel->addOpLog($logAdd);
                $setTimeOut = 1;
            }
        }
        $this->assign('setTimeOut', $setTimeOut);
        $this->assign('payId', $payId);
        $this->getViewer()->needLayout(false);
        $this->render('audit_hold');
    }

    public function errorAction()
    {
        $page = (int)$this->reqVar('page',1);

        $auditSet['pay_status'] = 4;
        $auditList = $this->exchangeModel->getExchangeList($auditSet,$page,60);
        if($auditList){
            foreach($auditList as $key=>$val){
                $exchangeHSet['exchange_id'] = $val['id'];
                $isH = $this->exchangeHModel->getExchangeH($exchangeHSet);
                if($isH){
                    $auditList[$key]['ish'] = 1;
                }else{
                    $auditList[$key]['ish'] = 0;
                }
            }
        }
        $auditCount = $this->exchangeModel->getExchangeCount($auditSet);
        $auditPages = pages($auditCount,$page,60,'',$array = array());

        $this->assign('auditList', $auditList);
        $this->assign('auditCount', $auditCount);
        $this->assign("payType", $this->configModel['pay_type']);
        $this->assign("payStatus", $this->configModel['pay_status']);
        $this->assign('auditPages', $auditPages);

        $this->getViewer()->needLayout(false);
        $this->render('audit_error_list');
    }

}
