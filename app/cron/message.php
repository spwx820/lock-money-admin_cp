<?php
/**
 * 消息运行程序(每两分钟执行一次)
 *
 * @category   Leb
 * @package    Leb_Action
 * @author     lihui
 * @version    $Id: message.php 2015-2-15 17:30:00 lihui
 * @copyright (c) 2015 dianjoy.com
 * @license
 */
class messageController extends Application
{
    private $messageModel;
    private $messagePrivateModel;
    private $userModel;
    private $retry = 3;

    private $transport;
    private $userClient;

    public function execute($plugins)
    {
        $this->messageModel = $this->loadModel('Message',array(),'admin');
        $this->messagePrivateModel = $this->loadModel('Message_private',array(),'admin');
        $this->userModel = $this->loadAppModel('User');



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

    public function sendAction()
    {
        $s = (int)$this->reqVar('s',0);
        $dateNow = date("Y-m-d H:i:s",time());

        //公共消息
        $publicMessageSet['message_type'] = 1;
        $publicMessageSet['status'] = 4;
        $publicMessageSet['condition'] = " AND start_date<='{$dateNow}'";
        $getPublicMessage = $this->messageModel->getMessage($publicMessageSet);


        if($getPublicMessage){
            $publicRe = $this->publicMessage($getPublicMessage);
            if(!empty($publicRe['status']) && $publicRe['status'] < 0){
                echo $dateNow."_".$getPublicMessage['id']."_".$publicRe['data']."--";
            }elseif(1 == $s){
                echo $dateNow."_".$getPublicMessage['id']."_".$publicRe['data']."--";
            }
        }elseif(1 == $s){
            echo "public data is empty--";
        }

        //私有消息
        $privateMessageSet['message_type'] = 0;
        $privateMessageSet['status'] = 4;
        $privateMessageSet['condition'] = " AND start_date<='{$dateNow}'";
        $getPrivateMessage = $this->messageModel->getMessage($privateMessageSet);
        if($getPrivateMessage){
            $privateRe = $this->privateMessage($getPrivateMessage);
            if(!empty($privateRe['status']) && $privateRe['status'] < 0){
                echo $dateNow."_".$getPrivateMessage['id']."_".$privateRe['data']."--";
            }elseif(1 == $s){
                echo $dateNow."_".$getPrivateMessage['id']."_".$privateRe['data']."--";
            }
        }elseif(1 == $s){
            echo "private data is empty--";
        }
    }

    private function publicMessage($getMessage)
    {
        if($getMessage['message_type'] != 1 || empty($getMessage['id'])){
            return array("status"=>-1,"data"=>'id is empty');
        }
        if(empty($getMessage['info_title']) || empty($getMessage['content'])){
            return array("status"=>-2,"data"=>'content is empty');
        }

        //判断结束时间
        $dateNow = date("Y-m-d H:i:s",time());
        if($getMessage['end_time'] < $dateNow){
            $this->messageModel->messageFail($getMessage['id'],"data expired");
            return array("status"=>-3,"data"=>'data expired');
        }

        $sendData['info_title'] = $getMessage['info_title'];
        $sendData['content']   = $getMessage['content'];
        $sendData['share_msg'] = $getMessage['share_msg'];
        $sendData['msg_img'] = $getMessage['url_images'];
        $sendData['info_notify'] = $getMessage['info_notify'];
        $sendData['end_time'] = $getMessage['end_time'];
        $sendData['os_type'] = $getMessage['os_type'];
        $sendData['click_url'] = $getMessage['click_url'];
        $sendData['button_text'] = $getMessage['button_text'];
        $sendData['rate'] = $getMessage['rate'];


        $apiData = json_encode($sendData);
        $apiData = urlencode($apiData);

        $apiSendJsonRe = file_get_contents(_API_URL_."/admin_public_send_msg.do?data={$apiData}");

        var_dump($apiSendJsonRe);

        $apiSendRe = json_decode($apiSendJsonRe,true);
        if(!$apiSendJsonRe || !isset($apiSendRe['errcode']) || $apiSendRe['errcode'] !==0){
            return array("status"=>-4,"data"=>'callback is empty');
        }

        if(is_numeric($apiSendRe['data']['rs']) && $apiSendRe['data']['rs']>0){
            $this->messageModel->messageSucceed($getMessage['id'],$apiSendRe['data']['rs'],"1/0");
        }else{
            $wrongMsg = '';
            if(!empty($apiSendRe['data']))
                $wrongMsg = json_encode($apiSendRe['data']);

            $this->messageModel->messageFail($getMessage['id'],$wrongMsg,"0/1");
        }
        return array("status"=>1,"data"=>'succ');
    }

    private function privateMessage($getMessage)
    {
        if($getMessage['message_type'] != 0 || empty($getMessage['id'])){
            return array("status"=>-1,"data"=>'id is empty');
        }
        if(empty($getMessage['info_title']) || empty($getMessage['content'])){
            return array("status"=>-2,"data"=>'content is empty');
        }

        //判断结束时间
        $dateNow = date("Y-m-d H:i:s",time());
        if($getMessage['end_time'] < $dateNow){
            $this->messageModel->messageFail($getMessage['id'],"data expired");
            return array("status"=>-3,"data"=>'data expired');
        }

        $messageSet['mid'] = $getMessage['id'];
        $messageSet['status'] = 0;
        $messageRe = $this->messagePrivateModel->getMessageList($messageSet,1,200);
        if($messageRe){
            $uidIds = array();
            foreach($messageRe as $key=>$val){
                if(!is_numeric($val['uid']))
                    continue;

                $userSet['uid'] = $val['uid'];
                $userSet['status'] = 1;

                $userRe = $this->userModel->getUser($userSet);
                if($userRe)
                    $uidIds[$val['uid']] = $val['id'];
            }

            if(empty($uidIds)){
                return array("status"=>-4,"data"=>'uid is empty');
            }

            //发送消息
            $sendData['uids_orderids'] = $uidIds;
            $sendData['info_title'] = $getMessage['info_title'];
            $sendData['content'] = $getMessage['content'];
            $sendData['share_msg'] = $getMessage['share_msg'];
            $sendData['info_notify'] = $getMessage['info_notify'];
            $sendData['end_time'] = $getMessage['end_time'];
            $sendData['os_type'] = $getMessage['os_type'];

            $sendData['click_url'] = $getMessage['click_url'];
            $sendData['button_text'] = $getMessage['button_text'];

            $apiData = json_encode($sendData);
            $apiData = urlencode($apiData);

            $apiSendJsonRe = file_get_contents(_API_URL_."/admin_user_send_msg.do?data={$apiData}");

            $apiSendRe = json_decode($apiSendJsonRe,true);
            if(!$apiSendJsonRe || !isset($apiSendRe['errcode']) || $apiSendRe['errcode'] !==0){
                return array("status"=>-5,"data"=>'callback is empty');
            }

            if(!empty($apiSendRe['data']['complete_orderids'])){
                foreach($apiSendRe['data']['complete_orderids'] as $ckey => $cval){
                    $this->messagePrivateModel->messageSucceed($cval);
                }
            }

            if(!empty($apiSendRe['data']['wrong_orderids'])){
                foreach($apiSendRe['data']['wrong_orderids'] as $wkey=>$wval){
                    $wValKey = key($wval);
                    $wValInfo = $wval[$wValKey];
                    if(!empty($wValKey) && isset($wValInfo)){
                        $this->messagePrivateModel->messageFail($wValKey,$wValInfo);
                    }
                }
                $wrongMsg = '';
                if(!empty($apiSendRe['msg']))
                    $wrongMsg =$apiSendRe['msg'];

                //保存错误信息
                $this->messageModel->saveMessage($getMessage['id'],array("callback_info"=>$wrongMsg));
            }
        }

        //判断消息执行完成更新状态
        $isMessageSet['mid'] = $getMessage['id'];
        $isMessageSet['status'] = 0;
        $isMessageRe = $this->messagePrivateModel->getMessage($isMessageSet);
        if(empty($isMessageRe)){
            //判断是否发送完成
            $messageSuccessSet['mid'] = $getMessage['id'];
            $messageSuccessSet['status'] = 1;
            $messageSuccessNum = $this->messagePrivateModel->getMessageCount($messageSuccessSet);

            $messageFailSet['mid'] = $getMessage['id'];
            $messageFailSet['status'] = 2;
            $messageFailNum = $this->messagePrivateModel->getMessageCount($messageFailSet);
            $numStr = (int)$messageSuccessNum.'/'.(int)$messageFailNum;
            if($messageSuccessNum>0){
                $this->messageModel->messageSucceed($getMessage['id'],'',$numStr);
            }else{
                $this->messageModel->messageFail($getMessage['id'],'',$numStr);
            }
        }
        return array("status"=>1,"data"=>'succ');
    }

}