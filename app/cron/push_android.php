<?php
/**
 * push运行程序
 *
 * @category   Leb
 * @package    Leb_Action
 * @author     lihui
 * @version    $Id: push_android.php 2015-2-09 9:30:00 lihui
 * @copyright (c) 2015 dianjoy.com
 * @license
 */
class push_androidController extends Application
{
    private $notificationModel;
    private $notificationPivateModel;
    private $transport;
    private $client;

    public function execute($plugins)
    {
        $this->notificationModel = $this->loadModel('Notification',array(),'admin');
        $this->notificationPivateModel = $this->loadModel('Notification_private',array(),'admin');

        $GLOBALS['THRIFT_ROOT'] = '../thriftlib';
        require_once( $GLOBALS['THRIFT_ROOT'] . '/Thrift.php' );
        require_once( $GLOBALS['THRIFT_ROOT'] . '/transport/TSocket.php' );
        require_once( $GLOBALS['THRIFT_ROOT'] . '/transport/TBufferedTransport.php' );
        require_once( $GLOBALS['THRIFT_ROOT'] . '/protocol/TBinaryProtocol.php' );
        require_once( $GLOBALS['THRIFT_ROOT'] . '/packages/admin_api/SendMsg.php' );

        //包含thrift客户端库文件
        $socket = new TSocket(_PUSH_ANDROID_TSOCKET, 9090);
        $this->transport = new TBufferedTransport($socket, 1024, 1024);
        $protocol = new TBinaryProtocol($this->transport);
        $this->client = new SendMsgClient($protocol);
    }

    public function indexAction()
    {
    }

    //发送公共通知
    public function public_sendAction()
    {
        $dateNow = date("Y-m-d H:i:s",time());
        $notificationSet['os_type']= 1;
        $notificationSet['n_type'] = 1;
        $notificationSet['status'] = 2;
        $notificationSet['condition'] = " AND start_date<='{$dateNow}'";
        $getNotification  = $this->notificationModel->getNotification($notificationSet);
        if(!$getNotification || empty($getNotification['id'])){
            die();
        }

        if(!empty($getNotification['ad_id']) && (empty($getNotification['ad_pack']) || 0 == $getNotification['ad_status'])){
            $this->notificationModel->notificationFail($getNotification['id'],"积分墙广告失效",'0/1');
            die("Failed to ad ");
        }

        //结束时间判断
        if($getNotification['end_date'] < $dateNow){
            $this->notificationModel->notificationFail($getNotification['id'],"Failed to end date",'0/1');
            die("Failed to end date");
        }

        //Thrift连接
        $this->transport->open();
        $tp = $this->transport->isOpen();
        if(!$tp){
            die("Failed to connect".$getNotification['id']."-".$dateNow."-");
        }

        //推送内容
        $pubMsgObj = new pubMsgObj(array(
            "msgid"=> $getNotification['id'],
            "title"=> $getNotification['title'],
            "content"=> $getNotification['subtitle'],
            "starttime"=> time(),
            "endtime"=> strtotime($getNotification['end_date']),
            "actionstr"=> $getNotification['protocol'],
            "actiontype"=> $getNotification['action'],
            "notify"=> $getNotification['is_popup'],
            "icon"=> $getNotification['url_images'],
            "url"=> $getNotification['click_url'],
            "ad_id"=> $getNotification['ad_id'],
            "pack_name"=> $getNotification['ad_pack'],
            "info_id"=> $getNotification['message_id'],
            "tips"=> '',
        ));
        $sendRe = $this->client->sendPubMsg($pubMsgObj);
        if($sendRe){
            $this->notificationModel->notificationSucceed($getNotification['id'],'1/0');
        }else{
            $this->notificationModel->notificationFail($getNotification['id'],"Failed to send",'0/1');
        }
        $this->transport->close();
        die("send succ");
    }

    //发送私有通知
    public function private_sendAction()
    {
        $page = (int)$this->reqVar('page',0);
        if(empty($page)){
            for($i=1;$i<=15;$i++){
                $this->private_push();
                sleep(2);
            }
        }else{
            die("end");
            $this->private_push();
            if(16 == $page){
                die("end");
            }
            $page = $page + 1;
            sleep(2);
            $this->redirect('', '/cron/pushnotification/private_ios?page='.$page,0);
        }
    }

    //私有通知
    private function private_push()
    {
        $dateNow = date("Y-m-d H:i:s",time());
        $notificationSet['os_type']= 1;
        $notificationSet['n_type'] = 0;
        $notificationSet['status'] = 2;
        $notificationSet['condition'] = " AND start_date<='{$dateNow}'";
        $getNotification  = $this->notificationModel->getNotification($notificationSet);
        if(!$getNotification || empty($getNotification['id'])){
            die();
        }

        if(!empty($getNotification['ad_id']) && (empty($getNotification['ad_pack']) || 0 == $getNotification['ad_status'])){
            $numStr = $this->privateSendCount($getNotification['id']);
            $this->notificationModel->notificationFail($getNotification['id'],"积分墙广告失效",$numStr);
            die("Failed to ad");
        }

        //结束时间判断
        if($getNotification['end_date'] < $dateNow){
            $numStr = $this->privateSendCount($getNotification['id']);
            $this->notificationModel->notificationFail($getNotification['id'],"Failed to end date",$numStr);
            die("Failed to end date");
        }

        //判断限制发送量
        $limitEnd = 0;
        $nSendNumSet['nid'] = $getNotification['id'];
        $nSendNumSet['status'] = 3;
        $nSuccessNum = $this->notificationPivateModel->getNotificationCount($nSendNumSet);
        if(!empty($nSuccessNum) && !empty($getNotification['limit_num']) && $nSuccessNum >= $getNotification['limit_num']){
            $limitEnd = 1;
        }

        $privateSet['nid'] = $getNotification['id'];
        $privateSet['status'] = 2;
        $notificationList = $this->notificationPivateModel->getNotificationList($privateSet,1,300);
        if(!$notificationList || 1 == $limitEnd){
            $numStr = $this->privateSendCount($getNotification['id']);
            $this->notificationModel->notificationSucceed($getNotification['id'],$numStr);
            die("send succ");
        }

        //Thrift连接
        $this->transport->open();
        $tp = $this->transport->isOpen();
        if(!$tp){
            die("Failed to connect".$getNotification['id']."-".$dateNow."-");
        }

        //推送内容
        foreach($notificationList as $key=>$val){
            $msgobj = new msgObj(array(
                "msgid"=> $getNotification['id'],
                "title"=> $getNotification['title'],
                "content"=> $getNotification['subtitle'],
                "uid"=> $val['uid'],
                "starttime"=> time(),
                "endtime"=> strtotime($getNotification['end_date']),
                "actionstr"=> $getNotification['protocol'],
                "actiontype"=> $getNotification['action'],
                "notify"=> $getNotification['is_popup'],
                "icon"=> $getNotification['url_images'],
                "url"=> $getNotification['click_url'],
                "ad_id"=> $getNotification['ad_id'],
                "pack_name"=> $getNotification['ad_pack'],
                "info_id"=> $getNotification['message_id'],
                "tips"=> '',
            ));

            $sendRe = $this->client->sendUserMsg($msgobj);
            if($sendRe){
                $this->notificationPivateModel->notificationSucceedByUid($getNotification['id'],$val['uid']);
            }else{
                $this->notificationPivateModel->notificationFailByUid($getNotification['id'],$val['uid'],"推送失败");
            }
        }
        $this->transport->close();
    }

    //计算到达数/失败数
    private function privateSendCount($nid)
    {
        //计算到达数/失败数
        $nSuccessSet['nid'] = $nid;
        $nSuccessSet['status'] = 3;
        $nSuccessNum = $this->notificationPivateModel->getNotificationCount($nSuccessSet);

        $nFailSet['nid'] = $nid;
        $nFailSet['status'] = 4;
        $nFailNum = $this->notificationPivateModel->getNotificationCount($nFailSet);
        $numStr = (int)$nSuccessNum.'/'.(int)$nFailNum;
        return $numStr;
    }

}