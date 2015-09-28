<?php
/**
 * swoole_client push运行程序(暂时只处理iOS公共通知)
 *
 * @category   Leb
 * @package    Leb_Action
 * @author     lihui
 * @version    $Id: push_async.php 2015-1-21 9:30:00 lihui
 * @copyright (c) 2015 dianjoy.com
 * @license
 */
class Push_asyncCommand extends Application
{
    private $pushModel;
    private $nModel;
    private $nPublicModel;
    private $nSendSetModel;
    private $sendNum = 20;    //设置进程数量，该设置修改时需与配置表条数相等才会生效
    private $sendLimit = 2000; //设置推送token数量
    private $sendPublicData = array();
    private $backTag;

    protected function execute($plugin)
    {
        die("暂停使用");
        $this->pushModel = $this->loadModel('Push_ios',array(),'admin');
        $this->nModel = $this->loadModel('Notification',array(),'admin');
        $this->nPublicModel = $this->loadModel('Notification_public',array(),'admin');
        $this->nSendSetModel = $this->loadModel('Notification_send_set',array(),'admin');
    }

    /**
     * 默认action
     */
    public function indexAction()
    {
    }

    public function public_iosAction($num)
    {
        die("暂停使用");

        $dateNow = date("Y-m-d H:i:s",time());
        $num = intval($num);
        if($num > $this->sendNum || $num < 0){
            die($dateNow."num not empty!");
        }
        $nSet['os_type']= 2;
        $nSet['n_type'] = 1;
        $nSet['status'] = 2;
        $nSet['condition'] = " AND start_date<='{$dateNow}'";
        $getNotification  = $this->nModel->getNotification($nSet);
        if(!empty($getNotification['id'])){
            //判断积分墙广告状态
            if(!empty($getNotification['ad_id']) && (empty($getNotification['ad_pack']) || 0 == $getNotification['ad_status'])){
                $this->nSendSetModel->clearSendSet();
                $numStr = $this->publicIosSendCount($getNotification['id']);
                $this->nModel->notificationFail($getNotification['id'],"积分墙广告失效",$numStr);
                die($dateNow."_".$getNotification['id'].":Failed to ad \r\n");
            }

            //判断结束时间
            if($getNotification['end_date'] < $dateNow){
                $this->nSendSetModel->clearSendSet();
                $numStr = $this->publicIosSendCount($getNotification['id']);
                $this->nModel->notificationFail($getNotification['id'],"Failed to end date",$numStr);
                die($dateNow."_".$getNotification['id'].":Failed to end date \r\n");
            }

            //判断限制发送量
            $nSendNumSet['nid'] = $getNotification['id'];
            $nSendNumSet['status'] = 1;
            $nSuccessNum = $this->nPublicModel->getNotificationCount($nSendNumSet);
            if(!empty($nSuccessNum) && !empty($getNotification['limit_num']) && $nSuccessNum >= $getNotification['limit_num']){
                $this->nSendSetModel->clearSendSet();
                $numStr = $this->publicIosSendCount($getNotification['id']);
                $this->nModel->notificationSucceed($getNotification['id'],$numStr);
                die($dateNow."_".$getNotification['id'].":succ to limit \r\n");
            }

            //判断是否发送完成
            $sendEndSet['status'] = 1;
            $sendEndCount = $this->nSendSetModel->getSendSetCount($getNotification['id'],$sendEndSet);
            if($this->sendNum == $sendEndCount){
                $this->nSendSetModel->clearSendSet();
                $numStr = $this->publicIosSendCount($getNotification['id']);
                $this->nModel->notificationSucceed($getNotification['id'],$numStr);
                die($dateNow."_".$getNotification['id'].":succ \r\n");
            }

            //第一次发放分配分配配置
            $this->publicIosSendFirstSetSave($getNotification['id']);

            //发送通知
            if(!empty($getNotification['title'])){
                $dataSet['protocol'] = !empty($getNotification['protocol']) ? $getNotification['protocol'] : '';
                $dataSet['action'] = !empty($getNotification['action']) ? $getNotification['action'] : '';
                $dataSet['ad_id'] = !empty($getNotification['ad_id']) ? $getNotification['ad_id'] : '';
                $dataSet['ad_pack'] = !empty($getNotification['ad_pack']) ? $getNotification['ad_pack'] : '';
                $dataSet['message_id'] = !empty($getNotification['protocol']) ? $getNotification['message_id'] : '';

                $sendDataSet['data'] = json_encode($dataSet);;
                $sendDataSet['title'] = $getNotification['title'];

                //执行发送
                $this->backTag = $dateNow."_".$getNotification['id'];
                $this->publicIosRun($getNotification['id'],$num,$sendDataSet);
            }
        }
    }

    //通知分段发送首次运行更新配置
    private function publicIosSendFirstSetSave($nid)
    {
        if(empty($nid))
            return false;

        //判断是否是第一次发放分配分配配置
        $isSendFirstSet['nid'] = $nid;
        $isSendFirst = $this->nPublicModel->getNotification($isSendFirstSet);
        $sendSet = $this->nSendSetModel->getSendSet($nid);
        if(!empty($isSendFirst) || !empty($sendSet))
            return false;

        $pushTokenRe = $this->pushModel->query("SELECT id FROM z_push_ios ORDER BY id DESC LIMIT 1");
        $pushTokenMaxId = !empty($pushTokenRe[0]['id']) ? $pushTokenRe[0]['id'] : 0;
        if(empty($pushTokenMaxId) || !is_numeric($pushTokenMaxId))
            return false;

        $pushTokenAverage = ceil($pushTokenMaxId/$this->sendNum);
        $endNum = 0;
        for($i =1;$i<= $this->sendNum;$i++){
            $sendSetSaveSet['nid'] = $nid;
            if($endNum >= $pushTokenMaxId){
                continue;
            }
            $sendSetSaveSet['star_num'] = $endNum + 1;
            if($i ==  $this->sendNum || $sendSetSaveSet['star_num'] == $pushTokenMaxId){
                $sendSetSaveSet['end_num'] = $pushTokenMaxId;
            }else{
                $sendSetSaveSet['end_num'] = $endNum + $pushTokenAverage;
                if($sendSetSaveSet['end_num'] > $pushTokenMaxId){
                    $sendSetSaveSet['end_num'] = $pushTokenMaxId;
                }
            }
            $endNum = $sendSetSaveSet['end_num'];
            $sendSetSaveSet['futime'] = date("Y-m-d H:i:s",time());
            $this->nSendSetModel->saveSendSet($i,$sendSetSaveSet);
        }
    }

    //计算到达数/失败数
    private function publicIosSendCount($nid)
    {
        //计算到达数/失败数
        $nSuccessSet['nid'] = $nid;
        $nSuccessSet['status'] = 1;
        $nSuccessNum = $this->nPublicModel->getNotificationCount($nSuccessSet);

        $nFailSet['nid'] = $nid;
        $nFailSet['status'] = 2;
        $nFailNum = $this->nPublicModel->getNotificationCount($nFailSet);

        $numStr = (int)$nSuccessNum.'/'.(int)$nFailNum;
        return $numStr;
    }

    private function publicIosRun($nid,$num,$sendDataSet)
    {
        $dateNow = date("Y-m-d H:i:s",time());
        if(empty($nid) || empty($num) || empty($sendDataSet)){
            die($dateNow."_".$nid."_".$num.":empty \r\n");
        }

        //判断分段通知是否为执行状态
        $sendSetNumSet['status'] = 0;
        $sendSetNumSet['condition'] = " AND id = $num";
        $sendSetNumRe = $this->nSendSetModel->getSendSet($nid,$sendSetNumSet);
        if(empty($sendSetNumRe['star_num']) || empty($sendSetNumRe['end_num'])){
            die();
        }

        //编辑发送内容
        $this->sendPublicData['nid'] = $nid;
        $this->sendPublicData['title'] = !empty($sendDataSet['title']) ? $sendDataSet['title'] : '';
        $this->sendPublicData['data']  = !empty($sendDataSet['data']) ? $sendDataSet['data'] : '';
        $this->sendPublicData['token'] = $this->publicIosSend($nid,$num,$sendSetNumRe['star_num'],$sendSetNumRe['end_num']);

        //发送通知
        if(!empty($this->sendPublicData['token']) && !empty($this->sendPublicData['title'])){
            $client = new swoole_client(SWOOLE_SOCK_TCP, SWOOLE_SOCK_ASYNC);

            //设置事件回调函数
            $client->on("connect", function($cli) {
                $sendData['nid']   = $this->sendPublicData['nid'];
                $sendData['title'] = $this->sendPublicData['title'];
                $sendData['data']  = $this->sendPublicData['data'];
                foreach($this->sendPublicData['token'] as $sKey=>$sVal){
                    $sendData['id'] = $sKey;
                    $sendData['token'] = $sVal;
                    $cli->send(json_encode($sendData)."\r\n");
                }
            });
            $client->on("receive", function($cli, $data){
                echo "Received: ".$data."\n";
                $this->publicIosReceive($data);
            });
            $client->on("error", function($cli){
                echo $this->backTag."Connect failed\n";
            });
            $client->on("close", function($cli){
                echo $this->backTag."Connection close\n";
            });

            //发起网络连接
            //      $swooleServerSet = array(1=>'127.0.0.1',2=>'198.168.199.9');
            $swooleServerSet = array(1=>'127.0.0.1');
            if($swooleServerSet){
                foreach($swooleServerSet as $key=>$val){
                    echo $key.'-'.$val;
                    $client->connect($val, 9501, 0.5);
                }
            }
        }
        die();
    }

    private function publicIosSend($nid,$num,$starNum,$endNum)
    {
        //编辑发送内容
        $sendData = array();
        $pushSet['condition'] = " AND id <= '{$endNum}' AND id >= '{$starNum}'";
        $pushSet['orderby'] = " id asc";
        $pushList = $this->pushModel->getPushList($pushSet,1,$this->sendLimit);
        if($pushList){
            $lastStarNum = 0;
            $i = 1;
            foreach($pushList as $key=>$val){
                $publicLogAdd['nid'] = $nid;
                $publicLogAdd['token_id'] = $val['id'];
                $publicLogAdd['uid'] = $val['uid'];
                $logId = $this->nPublicModel->addNotification($publicLogAdd);
                if(!$logId){
                    continue;
                }

                //发送的token数组
                $sendData[$logId] = $val['device_token'];
                $lastStarNum = $val['id'];
                $i++;
            }

            //判断分段通知是否更新或结束
            if($lastStarNum > 0){
                $nextLimit = $lastStarNum + $this->sendLimit;
                if($nextLimit < $endNum){
                    //记录下次执行ID
                    $sendSaveSetSet['star_num'] = $lastStarNum + 1;
                    $sendSaveSetSet['condition'] = " AND nid={$nid}";
                    $this->nSendSetModel->saveSendSet($num,$sendSaveSetSet);
                }else{
                    //该分段通知更新为结束状态
                    $sendSaveSetSet['status'] = 1;
                    $sendSaveSetSet['condition'] = " AND nid={$nid}";
                    $this->nSendSetModel->saveSendSet($num,$sendSaveSetSet);
                }
            }
            return $sendData;
        }
    }

    private function publicIosReceive($data)
    {
        $receData = json_decode($data , true);
        if(empty($receData['nid']) || empty($receData['back_id'])){
            return false;
        }

        if(empty($receData['error_code'])){
            $isSucceedSet['condition'] = " AND id='{$receData['back_id']}'";
            $isSucceed = $this->nPublicModel->getNotification($isSucceedSet);
            if($isSucceed){
                $succeedSet['token_id'] = $receData['id'];
                $this->nPublicModel->notificationSucceed($receData['nid'],$succeedSet);
            }
        }else{
            $isFailSet['condition'] = " AND id='{$receData['back_id']}'";
            $isFail = $this->nPublicModel->getNotification($isFailSet);
            if($isFail){
                $failSet['token_id'] = $receData['back_id'];
                if(100 == $receData['error_code']){
                    $errorStr = "ios server param error";
                }elseif(101 == $receData['error_code']){
                    $errorStr = "ios push connect fail";
                }elseif(102 == $receData['error_code']){
                    $errorStr = "ios push send fail";
                }else{
                    $errorStr = $receData['error_code'];
                }
                $this->nPublicModel->notificationFail($receData['nid'],$failSet,$errorStr);
            }
        }
    }

}