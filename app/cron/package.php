<?php
/**
 * 批量打Android包运行程序（升级包后使用）
 *
 * @category   Leb
 * @package    Leb_Action
 * @author     lihui
 * @version    $Id: channel.php 2014-10-5 10:30:00 lihui
 * @copyright (c) 2014 dianjoy.com
 * @license
 */
class packageController extends Application
{
    private $packageModel;

    public function execute($plugins)
    {
        //已修改为多进程打包
        die("暂停使用");
        ini_set('max_execution_time', '1800');
        ini_set('memory_limit','256M');
        $this->packageModel = $this->loadModel('Package',array(),'admin');
    }

    public function indexAction()
    {
    }

    public function cronAction()
    {
        $pid = (int)$this->reqVar('pid',0);
        if(empty($pid)){
            die("参数错误");
        }
        $packageSet['pk_os'] = 1;
        $packageSet['condition'] = " AND id >=$pid";
        $packageSet['orderby'] = "id asc";
        $packageList = $this->packageModel->getPackageList($packageSet,1,15);
        if($packageList){
            foreach($packageList as $key=>$val){
                //判断是否隐藏邀请码
                if(1 == $val['is_hidden_invite'] && !empty($val['uid'])){
                    $val['invite'] = 'c_'.$val['uid'];
                }elseif(1 == $val['is_hidden_invite']){
                    $val['invite'] = 'c';
                }elseif(!empty($val['uid'])){
                    $val['invite']= $val['uid'];
                }else{
                    $val['invite'] = '';
                }
                $pkurl = _API_URL_."/admin_invite_pk.do?ispak=1&uid={$val['invite']}&channel={$val['channel']}";
                $re = $this->curl_get($pkurl);
                if(empty($re['error'])){
                    echo $val['id']."<br/>";
                }else{
                    echo $val['id']."---fail<br/>";
                }
                sleep(2);
            }
        }
        echo "ok";
    }

    private function curl_get($url, $timeout = 5, $port = 80)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_POST, 0);

        $result = array();
        $result['result'] = curl_exec($ch);
        if(0 != curl_errno($ch)){
            $result['error'] = "Error:\n" . curl_error($ch);
        }
        curl_close($ch);
        return $result;
    }

}

