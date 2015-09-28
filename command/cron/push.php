<?php
/**
 * 邀请统计运行程序（打包管理使用）（存储昨天数据）
 *
 * @category   Leb
 * @package    Leb_Action
 * @author     lihui
 * @version    $Id: invite.php 2014-10-08 10:30:00 lihui
 * @copyright (c) 2014 dianjoy.com
 * @license
 */
class pushCommand extends Application
{

    
    private $NotifEndNid ;
    private $NotifSendStartNid ;
    private $NotifSendIndicator ;
    private $databaseReadLock ;

    private $operateLogModel;
    private $pushModel;
    private $nModel;
    private $nPublicModelMaster;
    private $nPublicModelSlave;
    private $sendNum = 20;      //设置进程数量，该设置修改时需与配置表条数相等才会生效
    private $sendLimit = 2000;  //设置每段每次推送token数量

    private $processId ;

    public function  execute($plugins)
    {
        $this->userModel = $this->loadAppModel('User');
        $this->inviteCountModel = $this->loadAppModel('Invite_count');
        $this->packageModel = $this->loadModel('Package',array(),'admin');



        $this->pushModel = $this->loadModel('Push_ios',array(),'admin');
        $this->nModel = $this->loadModel('Notification',array(),'admin');

        $this->nPublicModelMaster = $this->loadModel('Notification_public_master',array(),'admin');
        $this->nPublicModelSlave = $this->loadModel('Notification_public',array(),'admin');

        $this->notificationPivateModel = $this->loadModel('Notification_private',array(),'admin');
        $this->operateLogModel = $this->loadModel('Operate_log',array(),'admin');
    }


    public function writeInstalledUserList($pack_name,$ad_id) // 获取已安装$pac_name的用户列表，输入pac_name，将用户列表写入文件，供推送时使用。
    {

        if(empty($pack_name)||empty($ad_id)){ // pack_name未设置
            return ;
        }

        $url = "http://192.168.99.233:8080/inner_service/stat/v1/user_info.php?pack_name=" . $pack_name . "&output=json";

//       $url = "http://192.168.199.107:8000/testdata.php"; // 测试用url debug

        $handle = fopen($url, "rb");
        $contents = stream_get_contents($handle);
        fclose($handle);


        if(empty($contents))
        {
            return;
            die("无法获取用户列表，请联系管理员");
        }

        $redis = Leb_Dao_Redis::getInstance();
        $redis->setex($ad_id,60*60*10,$contents); //

        return ;
    }


    public function public_iosv1Action()
    {

        $this->processId = substr(strval(time()), -4, 4);


//                die("暂停");

        
        for ($i = 1; $i <= $this->sendNum; $i++)   // init redis key for segment push
        {
            $this->NotifEndNid[$i] = "NotifEndNid" . strval($i);
            $this->NotifSendStartNid[$i] = "NotifSendStartNid" . strval($i);
            $this->NotifSendIndicator[$i] = "NotifSendIndicator" . strval($i);
            $this->databaseReadLock[$i] = "databaseReadLock" . strval($i);

        }

        $dateNowPlusHour = date("Y-m-d H:i:s", time() + 3600);

        $dateNow = date("Y-m-d H:i:s", time());

        $getNotification = $this->nModel->query("SELECT * FROM a_notification WHERE
                                                  start_date = (SELECT MAX(start_date) FROM a_notification WHERE start_date<='$dateNowPlusHour')
                                                  AND os_type = 2 AND n_type = 1 AND status = 2")[0];

        $redis = Leb_Dao_Redis::getInstance();

        if (!empty($getNotification["ad_id"]) && (strtotime($getNotification['start_date']) - time()) / 3600.0 < 1.0 && !$redis->exists($getNotification["ad_id"]))// 提前一小时生成InstalledUserList
        {
            self::writeInstalledUserList($getNotification['ad_pack'], $getNotification['ad_id']); // 把该通知对应的 已安装用户列表写入redis
        }

        $getNotification = $this->nModel->query("SELECT * FROM a_notification WHERE
                                                  start_date = (SELECT MAX(start_date) FROM a_notification WHERE start_date<='$dateNow')
                                                  AND os_type = 2 AND n_type = 1 AND status = 2")[0];


        if (!empty($getNotification['id']))
        {
            //判断积分墙广告状态
            if (!empty($getNotification['ad_id']) && (empty($getNotification['ad_pack']) || 0 == $getNotification['ad_status']))
            {
                $this->clearSendSet();

                $this->pushLogTransfer();

                $numStr = $this->publicIosSendCount($getNotification['id'], 0);

                $nid = $getNotification['id']; // 有时候积分墙状态会暂时失效,然后又变成有效,因此这里不改变状态,值统计发送数量
                $this->nModel->execute("UPDATE a_notification SET send_num = '$numStr' WHERE id = $nid");

                die($dateNow . "_" . $getNotification['id'] . ":Failed to ad \r\n");
            }

            //判断结束时间
            if ($getNotification['end_date'] < $dateNow)
            {
                $this->clearSendSet();

                $this->pushLogTransfer();

                $numStr = $this->publicIosSendCount($getNotification['id'], 0);
                $this->nModel->notificationFail($getNotification['id'], "Failed to end date", $numStr);// 到达下线时间

                die($dateNow . "_" . $getNotification['id'] . ":Failed to end date \r\n");
            }


            //判断是否发送完成

            $sendEndCount = 0;

            for ($i = 1; $i <= $this->sendNum; $i++)
            {
                if (intval($redis->get($this->NotifSendIndicator[$i])) == 1)
                    $sendEndCount++;
            }

            if ($this->sendNum == $sendEndCount) // 所有的段都已经发送成功
            {
                $this->clearSendSet();

                $this->pushLogTransfer();

                $numStr = $this->publicIosSendCount($getNotification['id'], 0);
                $this->nModel->notificationSucceed($getNotification['id'], $numStr);

                die($dateNow . "_" . $getNotification['id'] . ":succ \r\n");
            }
        } else
        {
            die("没有合适的通知供推送");
        }

        for ($i = 1; $i <= $this->sendNum; $i++)
        {
            $this->pushPublicIos($i, $getNotification);
        }

        die();
    }


    public function pushPublicIos($num, $getNotification)
    {
        $redis = Leb_Dao_Redis::getInstance();

        $dateNow = date("Y-m-d H:i:s", time());

        $nSendNumSet['nid'] = $getNotification['id'];
        $nSendNumSet['status'] = 1;
        $nSuccessNum = intval($redis->get($this->NotifSendStartNid[1]) / 2000 - 1) * 20000 + $num * 2000;
        //判断限制发送量
        if (!empty($nSuccessNum) && !empty($getNotification['limit_num']) && $nSuccessNum >= $getNotification['limit_num'])
        {

            $this->clearSendSet();

            $this->pushLogTransfer();


            $numStr = $this->publicIosSendCount($getNotification['id'], 0);
            $this->nModel->notificationSucceed($getNotification['id'], $numStr);


            die($dateNow . "_" . $getNotification['id'] . ":succ to limit \r\n");
        }

//        $this->clearSendSet();die();

        //第一次发放分配分配配置
        $this->publicIosFirstSetSave($getNotification['id']);

        //发送通知
        if (!empty($getNotification['title']))
        {
            $dataSet['protocol'] = !empty($getNotification['protocol']) ? $getNotification['protocol'] : '';
            $dataSet['action'] = !empty($getNotification['action']) ? $getNotification['action'] : '';
            $dataSet['ad_id'] = !empty($getNotification['ad_id']) ? $getNotification['ad_id'] : '';
            $dataSet['ad_pack'] = !empty($getNotification['ad_pack']) ? $getNotification['ad_pack'] : '';
            $dataSet['message_id'] = !empty($getNotification['protocol']) ? $getNotification['message_id'] : '';

            $sendDataSet['data'] = json_encode($dataSet);
            $sendDataSet['title'] = $getNotification['title'];

            //执行发送

            if (!$this->publicIosRun($getNotification['id'], $num, $sendDataSet, $getNotification['ad_id']))
                return;

            $numStr = $this->publicIosSendCount($getNotification['id'], 1);
            $nid = $getNotification['id'];
            $this->nModel->execute("UPDATE a_notification SET send_num = '$numStr' WHERE id = $nid");
        }

    }




    private function publicIosRun($nid,$num,$sendDataSet,$adId)
    {
        $dateNow = date("Y-m-d H:i:s",time());
        if(empty($nid) || empty($num) || empty($sendDataSet)){
            die($dateNow."_".$nid."_".$num.":empty \r\n");
        }


        $redis = Leb_Dao_Redis::getInstance();

        //判断分段通知是否为执行状态

        $sendSetNumRe['send_star'] = $redis->get($this->NotifSendStartNid[$num]);
        $sendSetNumRe['end_num'] = $redis->get($this->NotifEndNid[$num]);
        $sendSetNumRe['isValid'] = $redis->get($this->NotifSendIndicator[$num]);

        if(empty($sendSetNumRe['send_star']) || empty($sendSetNumRe['end_num']) ||  $sendSetNumRe['isValid'] == 1){
            var_dump($sendSetNumRe);
            echo("第{$num}段已经发完!<br>");
            return false;
        }


        while( $redis->get($this->databaseReadLock[$num]) != 0 ) // 获取每次用户列表的起始位置，保证不同进程的代码不会读取同一段用户列表
        {
            sleep(1);// 等待redis资源不被占用
            oplog("$num is waiting for redis");
        }

        {

            $redis->setex($this->databaseReadLock[$num], 60*60, 1); // redis操作加锁

            $segStart = $redis->get($this->NotifSendStartNid[$num]); // 本轮发送起点
            $endNum = $redis->get($this->NotifEndNid[$num]);         // 分段的终点


            if( ($segStart + $this->sendLimit) > $endNum ) // 判断本轮发送终点,是否超过本段终点
            {
                $segEnd = $endNum;  // 本段发送起点

                $redis->setex($this->NotifSendIndicator[$num], 60*60*24, 1); // 本段发送完成,下次跳过此段发送
            }
            else
            {
                $segEnd = $segStart + $this->sendLimit ;  // 本轮发送终点

                $redis->setex($this->NotifSendIndicator[$num], 60*60*24, 0); // 未完成,下轮继续发送
            }

            $redis->setex($this->NotifSendStartNid[$num], 60*60*24, $segEnd +1 ); // 设置下轮发送的起点

            $redis->setex($this->databaseReadLock[$num], 60*60, 0);// 释放锁
        }


        //连接push服务器
        $ctx = stream_context_create();
        stream_context_set_option($ctx,"ssl","local_cert",_ROOT_."app/_archive/ck.pem");
        stream_context_set_option($ctx, 'ssl', 'passphrase', _PUSH_IOS_PASS_);

        //debug zw
         $fp = stream_socket_client(_PUSH_IOS_SSL_, $err, $errstr, 60, STREAM_CLIENT_CONNECT, $ctx); // 有可能超时
//       $fp = 1;

        $this->oplog("apple connect status: ? $fp");

        if(!$fp){
            echo $err, $errstr;
//            die($dateNow."_".$nid."_".$num.":Failed to connect\r\n");
            return false;
        }else{
            //发送通知

            $this->oplog(["process"=>$this->processId ,"num" => $num, "start" => $segStart, "end"=> $segEnd ]);// 每次开始,结束值 log

            $sendData = $this->pushModel->query("SELECT device_token, uid , id FROM z_push_ios WHERE id <= $segEnd AND  id >= $segStart");

            $this->oplog(["process"=>$this->processId ,"num" => $num, "sendData[0]" => $sendData[0] ]);//

            $sendData = $this->publicIosUnique($sendData,$adId);//排重操作


            if( empty( $sendData[0] ))
            {
                $this->oplog(["process"=>$this->processId ,"query" => "SELECT device_token, uid , id FROM z_push_ios WHERE id <= $segEnd AND  id >= $segStart" ]);//
                $this->pushModel = $this->loadModel('Push_ios',array(),'admin');

                $sendData = $this->pushModel->query("SELECT device_token, uid , id FROM z_push_ios WHERE id <= $segEnd AND  id >= $segStart");

                $this->oplog(["process"=>$this->processId ,"num" => $num, "sendData[0]" => $sendData[0] ]);//

                $sendData = $this->publicIosUnique($sendData,$adId);//排重操作

            }

//            stream_set_timeout($fp, 1); // 设置发送超时时间

            if($sendData && !empty($sendDataSet['title']) && !empty($sendDataSet['data']))
            {
                $body = array("aps" => array("alert" => $sendDataSet['title'], "badge" => 1, "sound" => 'default', 'data' => $sendDataSet['data']));     //推送方式，包含内容和声音

                $payload = json_encode($body);

                $pushFailQuery = "VALUES";
                $pushSuccQuery = "VALUES";

                $this->oplog(["process" => $this->processId, "num" => $num, " " => "push start"]);// 每次开始,结束值 log

                foreach ($sendData as $key => $val)
                {
                    if (empty($val['device_token']) || empty($val['uid']))
                    {
                        continue;
                    }
                    $msg = chr(0) . pack("n", 32) . pack("H*", str_replace(' ', '', $val['device_token'])) . pack("n", strlen($payload)) . $payload;

                    $pushResult = fwrite($fp, $msg); //debug


                    //                   $pushResult=0; // debug
                    //                   usleep(1000);

                    if (!$pushResult)
                    {

                        //debug
                        $fp = stream_socket_client(_PUSH_IOS_SSL_, $err, $errstr, 60, STREAM_CLIENT_CONNECT, $ctx);

                        $pushFailQuery .= "(";
                        $pushFailQuery .= strval($nid) . ",";
                        $pushFailQuery .= strval($val['id']) . ",";
                        $pushFailQuery .= strval($val['uid']) . ", 2, '推送失败' , '$dateNow'), ";

                    } else
                    {

                        $pushSuccQuery .= "(";
                        $pushSuccQuery .= strval($nid) . ",";
                        $pushSuccQuery .= strval($val['id']) . ",";
                        $pushSuccQuery .= strval($val['uid']) . ", 1, '','$dateNow'), ";

                    }

                    usleep(1000);
                }

                $this->oplog(["process" => $this->processId, "num" => $num, " " => "push end"]);// 每次开始,结束值 log


                $pushSuccQuery .= "(0,0,0,0,'','')";
                $pushFailQuery .= "(0,0,0,0,'','')";

                $this->nPublicModelMaster->execute("INSERT INTO a_notification_public_master (nid,token_id,uid,status,wrong_msg,createtime) $pushFailQuery"); //
                $this->nPublicModelMaster->execute("INSERT INTO a_notification_public_master (nid,token_id,uid,status,wrong_msg,createtime) $pushSuccQuery"); //


                fclose($fp);

            }
        }
        usleep(500000);

        return true;
    }


    private function publicIosUnique($sendData,$adId)
    {
        if(empty($adId)){
            return $sendData;
        }

        $redis = Leb_Dao_Redis::getInstance();
        $contents = $redis->get($adId); //

        $contents = json_decode($contents);

        foreach($contents as $uid)
        {
            if($uid>0  &&  !empty($sendData[intval($uid)])) {
                unset($sendData[$uid]);
            }
        }

        return $sendData;
    }

    private function clearSendSet() // 清除各个标志位缓存
    {
        $redis = Leb_Dao_Redis::getInstance();

        for ($i = 1; $i <= $this->sendNum; $i++) {

            $redis->del($this->NotifSendStartNid[$i]); //
            $redis->del($this->NotifEndNid[$i]); //
            $redis->del($this->NotifSendIndicator[$i]); //

        }
    }



    //通知分段发送首次运行更新配置
    private function publicIosFirstSetSave($nid)
    {
        if(empty($nid))
            return false;

        //判断是否是第一次发放分配配置

        $redis = Leb_Dao_Redis::getInstance();
        $sendSet = $redis->get($this->NotifEndNid[1]);


        if( !empty($sendSet))
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
            $sendSetSaveSet['star_num'] = $sendSetSaveSet['send_star'] = $endNum + 1;

            if($i ==  $this->sendNum || $sendSetSaveSet['star_num'] == $pushTokenMaxId){
                $sendSetSaveSet['end_num'] = $pushTokenMaxId;
            }
            else{
                $sendSetSaveSet['end_num'] = $endNum + $pushTokenAverage;
                if($sendSetSaveSet['end_num'] > $pushTokenMaxId){
                    $sendSetSaveSet['end_num'] = $pushTokenMaxId;
                }
            }
            $endNum = $sendSetSaveSet['end_num'];
            $sendSetSaveSet['futime'] = date("Y-m-d H:i:s",time());

            //redis缓存code
            $redis = Leb_Dao_Redis::getInstance();

            $redis->setex($this->NotifSendStartNid[$i],60*60*24,$sendSetSaveSet['send_star']); //
            $redis->setex($this->NotifEndNid[$i],60*60*24,$sendSetSaveSet['end_num']); //
            $redis->setex($this->NotifSendIndicator[$i],60*60*24, 0); //
            $redis->setex($this->databaseReadLock[$i],60*60,0);

        }

    }


    //计算到达数/失败数
    private function publicIosSendCount($nid,$flagCount)
    {
        //计算到达数/失败数
        $nSuccessSet['nid'] = $nid;
        $nSuccessSet['status'] = 1;
        if( $flagCount == 1 )
            $nSuccessNum = $this->nPublicModelMaster->getNotificationCount($nSuccessSet);
        else
            $nSuccessNum = $this->nPublicModelSlave->getNotificationCount($nSuccessSet); //


        $nFailSet['nid'] = $nid;
        $nFailSet['status'] = 2;
        if( $flagCount == 1 )
            $nFailNum = $this->nPublicModelMaster->getNotificationCount($nFailSet);
        else
            $nFailNum = $this->nPublicModelSlave->getNotificationCount($nFailSet);


        $numStr = (int)$nSuccessNum.'/'.(int)$nFailNum;
        return $numStr;
    }





    private function oplog($logdata)
    {
        if(empty($logdata)){
            return false;
        }

        //操作日志记录
        $logAdd['app'] = $this->_application;
        $logAdd['controller'] = $this->_controller;
        $logAdd['action'] = $this->_action;
        $logAdd['content'] = json_encode($logdata);
        $this->operateLogModel->addOpLog($logAdd);
    }


    private function pushLogTransfer()
    {
        $this->nPublicModelMaster->query("INSERT INTO a_notification_public (nid,token_id,uid,status,wrong_msg,createtime)
                                        SELECT nid,token_id,uid,status,wrong_msg,createtime FROM a_notification_public_master
                                        ");// 数据复制到从库
        $this->nPublicModelMaster->query("truncate a_notification_public_master");

    }




}