<?php
/**
 * 发送短信密码
 *
 * @category   Leb
 * @package    Leb_Model
 * @author 	lihui
 * @version    $Id: sms.php 1 2014-09-03 12:42 $
 * @copyright
 * @license
 */
class Smssend_passwd
{
    protected $_sign = '【红包锁屏】';
    protected $_send_url = 'http://a.dianjoy.com/dev/api/sms/send_sms_msg.php';
    private $sleep_time = 3;
    private $retry = 3;

    public function send($data)
    {
        $rt = array('re' => 0, 'msg' => '');
        if(!$this->_send_url || !$this->_sign){
            $rt['msg'] = '缺少必要参数！';
            return $rt;
        }
        if(empty($data['mobile']) || empty($data['password']) || empty($data['client_ip'])){
            $rt['msg'] = '参数错误！';
            return $rt;
        }
        $http = new Plugin_Http();

        $param['sign'] = $this->_sign;
        $param['channel'] = 2;
        $param['ip']  = $data['client_ip'];
        $param['mno'] = $data['mobile'];
        $param['msg'] = '密码重置为：'.$data['password'].'。为了您的帐号安全，请谨慎保管密码';

        $http->setURL($this->_send_url .'?'.http_build_query($param) );
        $isconn = false;
        for($i = 0; $i < $this->retry; $i++){
            if($http->get()){
                $isconn = true;
                break;
            }
            sleep($this->sleep_time);
        }
        if(!$isconn){
            $rt['msg'] =  '服务获取失败';
            return $rt;
        }
        $result = $http->getContent();
        $result = json_decode($result,true);
        if($result && $result['res'] == 1){
            $rt = array('re' => $result['res'], 'msg' => '成功');
        }else{
            $rt = array('re' => $result['res'], 'msg' => $result['msg']);
        }
        return $rt;
    }
}