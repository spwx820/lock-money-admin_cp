<?php
/**
 * 发送短信密码
 *
 * @category   Leb
 * @package    Leb_Model
 * @author 	lihui
 * @version    $Id: apisend_hongbao.php 1 2014-09-03 12:42 $
 * @copyright
 * @license
 */
class Apisend_hongbao
{
    protected $_send_url = 'http://192.168.199.9:8889/admin_user_score_add.do';
    private $sleep_time = 3;
    private $retry = 3;

    public function send($data)
    {
        die("暂停使用");
        $rt = array('re' => 0, 'msg' => '');
        if(!$this->_send_url || empty($data['uids_orderids']) || empty($data['currency'])){
            $rt['msg'] = '缺少必要参数！';
            return $rt;
        }
        if(empty($data['msg']) || empty($data['ad_name']) || empty($data['ad_type']) ||  empty($data['action_type'])){
            $rt['msg'] = '参数错误！';
            return $rt;
        }
        $http = new Plugin_Http();
        $http->setURL($this->_send_url .'?'.http_build_query($data));
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
        return $result;
    }

}