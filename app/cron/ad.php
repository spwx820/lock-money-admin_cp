<?php
/**
 * 广告点击统计及上架、下架操作
 *
 * @category   Leb
 * @package    Leb_Action
 * @author     lihui
 * @version    $Id: ad.php 2014-11-18 19:30:00 lihui
 * @copyright (c) 2014 dianjoy.com
 * @license
 */
class adController extends Application
{
    private $adModel;
    private $operateLogModel;

    public function execute($plugins)
    {
        $this->adModel = $this->loadModel('Ad_operate',array(),'admin');
        $this->operateLogModel = $this->loadModel('Operate_log',array(),'admin');
    }

    public function indexAction()
    {
    }

    //自动上架广告
    public function cronAction()
    {
        $this->open();
        $this->shut();
    }

    private function open()
    {
        $isSucceed = 0;
        $dateNow = date("Y-m-d H:i:s",time());
        $re = $this->adModel->query("SELECT id,start_date,end_date FROM z_ad WHERE start_date!='' AND z_status in (0,2) ORDER BY ctime ASC");
        if($re){
            foreach($re as $key=>$val){
                if(!empty( $val['end_date']) && $val['end_date']." 23:59:59" < $dateNow)
                    continue;

                if($val['start_date']." 00:00:00" <= $dateNow){
                    $this->adModel->openAd($val['id']);

                    $logAdd['id'] = $val['id'];
                    $logAdd['cz'] = 'open';
                    $this->oplog($logAdd);
                    $isSucceed = 1;
                }
            }
        }
        if(1==$isSucceed){
            echo $dateNow."open-succeed\r\n";
        }else{
            echo $dateNow."open-fail:empty data\r\n";
        }
    }

    private function shut()
    {
        $isSucceed = 0;
        $dateNow = date("Y-m-d H:i:s",time());
        $re = $this->adModel->query("SELECT id,end_date,click_num,click_count FROM z_ad WHERE z_status=1 AND end_date!='' ORDER BY ctime ASC");
        if($re){
            foreach($re as $key=>$val){
                if($val['end_date']." 23:59:59" < $dateNow){
                    $this->adModel->shutAd($val['id']);

                    $logAdd['id'] = $val['id'];
                    $logAdd['cz'] = 'shut';
                    $this->oplog($logAdd);
                    $isSucceed = 1;
                }
            }
        }

        if(1==$isSucceed){
            echo $dateNow."shut-succeed\r\n";
        }else{
            echo $dateNow."shut-fail:empty data\r\n";
        }
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
        $logAdd['operat'] = 'system';
        $this->operateLogModel->addOpLog($logAdd);
    }

}