<?php
/**
 * swoole_server push运行程序(暂时只处理iOS公共通知)
 *
 * @category   Leb
 * @package    Leb_Action
 * @author     lihui
 * @version    $Id: push_ios.php 2015-1-21 9:30:00 lihui
 * @copyright (c) 2015 dianjoy.com
 * @license
 */
class PushIosServer
{
    private $serv;

    public function __construct() {
        die("11");
        $this->serv = new swoole_server("0.0.0.0", 9501);
        $this->serv->set(array(
            'worker_num' => 8,
            'max_request' => 10000,
            'dispatch_mode' => 2,
            'package_max_length' => 28192,
            'open_eof_check'=> true,
            'package_eof' => "\r\n",
            'daemonize' => false,
            'log_file' => '/tmp/swoole_push_ios.log',
        ));
        $this->serv->on('Start', array($this, 'onStart'));
        $this->serv->on('Connect', array($this, 'onConnect'));
        $this->serv->on('Receive', array($this, 'onReceive'));
        $this->serv->on('Close', array($this, 'onClose'));
        // bind callback
//        $this->serv->on('Task', array($this, 'onTask'));
//        $this->serv->on('Finish', array($this, 'onFinish'));
        $this->serv->start();
    }

    public function onStart($serv) {
        echo "Start\n";
    }

    public function onConnect($serv, $fd, $from_id) {
        echo "Client {$fd} connect\n";
    }

    public function onReceive($serv, $fd, $from_id, $data) {
//        echo "Get Message From Client {$fd}:{$data}\n";
        $dataList = explode("\r\n", $data);
        foreach($dataList as $jsonData) {
            if(!empty($jsonData)){
                $receData = json_decode($jsonData, true);
                if(empty($receData['id']) || empty($receData['nid']) || empty($receData['token']) || empty($receData['title']) || empty($receData['data'])){
                    $sendData = array('nid'=>$receData['nid'],'back_id'=>$receData['id'],'error_code'=>'100');
                }else{
                    $ctx = stream_context_create();
                    stream_context_set_option($ctx,"ssl","local_cert","../app/_archive/ck.pem");
                    stream_context_set_option($ctx, 'ssl', 'passphrase', '1234');
                    $fp = stream_socket_client("ssl://gateway.sandbox.push.apple.com:2195", $err, $errstr, 60, STREAM_CLIENT_CONNECT, $ctx);
                    if(!$fp){
                        echo STREAM_CLIENT_CONNECT;
                        echo "Failed to connect $err $errstr\n";
                        die("11");
                        $sendData = array('nid'=>$receData['nid'],'back_id'=>$receData['id'],'error_code'=>'101');
                    }else{
                        //推送方式，包含内容和声音
                        $body = array("aps" => array("alert" => $receData['title'],"badge" => 1,"sound"=>'default','data'=>$receData['data']));
                        $payload = json_encode($body);
                        $msg = chr(0) . pack("n",32) . pack("H*", str_replace(' ', '', $receData['token'])) . pack("n",strlen($payload)) . $payload;
                        $pushResult = fwrite($fp, $msg);
                        if(!$pushResult){
                            $sendData = array('nid'=>$receData['nid'],'back_id'=>$receData['id'],'error_code'=>'102');
                        }else{
                            $sendData = array('nid'=>$receData['nid'],'back_id'=>$receData['id'],'error_code'=>'');
                        }
                        fclose($fp);
                    }
                }
                $serv->send($fd, json_encode($sendData));
            }
        }
        echo "Continue Handle Worker\n";
    }

    public function onClose($serv, $fd, $from_id) {
        echo "Client {$fd} close connection\n";
    }

//    public function onTask($serv,$task_id,$from_id, $data) {
//        echo "This Task {$task_id} from Worker {$from_id}\n";
//        return "Task {$task_id}'s result";
//    }

//    public function onFinish($serv,$task_id, $data) {
//        echo "Task {$task_id} finish\n";
//        echo "Result: {$data}\n";
//    }
}

//require_once("../config/init_server.php");
$server = new PushIosServer();