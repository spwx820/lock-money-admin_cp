<?php
/**
 * 兑换统计管理
 *
 * @category   Leb
 * @package    Leb_Action
 * @author     lihui
 * @version    $Id: exchange_c.php 2014-09-03 9:58:00 lihui
 * @copyright (c) 2014 dianjoy.com
 * @license
 */
class exchange_cController extends Application
{
    private $exchangeModel;
    private $configModel;

    public function execute($plugins)
    {
        $this->exchangeModel = $this->loadAppModel('Exchange');
        $this->configModel = C('global.php');
    }

    public function indexAction()
    {
        $startTime  = daddslashes($this->reqVar('start_time',''));
        $endTime    = daddslashes($this->reqVar('end_time',''));
        $actionType = (int)$this->reqVar('action_type',0);
        $pages = (int)$this->reqVar('page',1);

        $condition = '';
        $pageUrl = "/admin/exchange_c/";
        if(!empty($startTime)){
            $condition .= " AND update_time >= '$startTime 00:00:00'";
            $pageUrl .= "?start_time=$startTime";
        }else{
            $startTime = date("Y-m-d",time()- 604800);
            $condition .= " AND update_time >= '$startTime 00:00:00'";
            $pageUrl .= "?start_time=$startTime";
        }
        if(!empty($endTime)){
            $condition .= " AND update_time <= '$startTime 23:59:59'";
            if(!empty($startTime)){
                $pageUrl .= "&end_time=$endTime";
            }else{
                $pageUrl .= "?end_time=$endTime";
            }
        }
        if(!empty($actionType)){
            $condition .= " AND ptype = '$actionType'";
            if(!empty($startTime) || !empty($endTime)){
                $pageUrl .= "&action_type=$actionType";
            }else{
                $pageUrl .= "?action_type=$actionType";
            }
        }
        $exchangeList = $this->exchangeModel->query("select left(update_time,10) as date_time,ptype,count(*) as c,sum(pay) as s
                                                     from z_present_exchange
                                                     where pay_status=3 $condition
                                                     group by left(update_time,10),ptype
                                                     order by date_time desc");
        $payCount = $paySum = 0;
        if($exchangeList){
            foreach($exchangeList as $key=>$val){
                $payCount = $payCount + $val['c'];
                $paySum = $paySum + $val['s'];
            }
        }

        $this->assign('exchangeList', $exchangeList);
        $this->assign('startTime', $startTime);
        $this->assign('endTime', $endTime);
        $this->assign('actionType', $actionType);
        $this->assign("payType", $this->configModel['pay_type']);
        $this->assign("payCount", $payCount);
        $this->assign("paySum", $paySum);

        $this->getViewer()->needLayout(false);
        $this->render('exchange_c');
    }

}
