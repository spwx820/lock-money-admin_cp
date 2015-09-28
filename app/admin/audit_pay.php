<?php
/**
 * 审核管理(支付宝使用)
 *
 * @category   Leb
 * @package    Leb_Action
 * @author     lihui
 * @version    $Id: audit_pay.php 2014-09-03 9:58:00 lihui
 * @copyright (c) 2014 dianjoy.com
 * @license
 */
class audit_payController extends Application
{
    private $exchangeModel;
    private $exchangeHModel;
    private $inviteHoldLogModel;
    private $inviteHoldExceptionModel;
    private $operateLogModel;
    private $configModel;

    public function execute($plugins)
    {
        $this->exchangeModel = $this->loadAppModel('Exchange');
        $this->exchangeHModel = $this->loadAppModel('Exchange_hold');
        $this->inviteHoldLogModel = $this->loadModel('Invite_hold_log');
        $this->inviteHoldExceptionModel = $this->loadModel('Invite_hold_exception');
        $this->operateLogModel = $this->loadModel('Operate_log', array(), 'admin');
        $this->configModel = C('global.php');
    }

    public function indexAction()
    {
        $search = daddslashes($this->postVar('search', ''));
        $keyword = daddslashes($this->reqVar('keyword', ''));
        $startTime = daddslashes($this->reqVar('start_time', ''));
        $endTime = daddslashes($this->reqVar('end_time', ''));
        $type = (int)$this->reqVar('type', 0);
        $page = (int)$this->reqVar('page', 1);

        $pageUrl = "/admin/audit_pay/";
        if (!empty($keyword))
        {
            if (1 == $type)
            {
                $auditSet['id'] = $keyword;
                $pageUrl .= "?keyword=$keyword&type=$type";
            } elseif (2 == $type)
            {
                $auditSet['pay_content'] = $keyword;
                $pageUrl .= "?keyword=$keyword&type=$type";
            } elseif (3 == $type)
            {
                $auditSet['uid'] = $keyword;
                $pageUrl .= "?keyword=$keyword&type=$type";
            }
        }

        $whereStr = "1";
        if (!empty($startTime))
        {
            $auditSet['condition'] .= " AND update_time >= '$startTime'";
            if (empty($keyword))
            {
                $pageUrl .= "?start_time=$startTime";
            } else
            {
                $pageUrl .= "&start_time=$startTime";
            }
        }
        if (!empty($endTime))
        {
            $auditSet['condition'] .= " AND update_time <= '$endTime'";
            if (empty($keyword) && empty($startTime))
            {
                $pageUrl .= "?end_time=$endTime";
            } else
            {
                $pageUrl .= "&end_time=$endTime";
            }
        }

        $auditSet['pay_status'] = 2;
        $auditSet['ptype'] = 3;
        $auditSet['orderby'] = " update_time asc";
        $auditList = $this->exchangeModel->getExchangeList($auditSet, $page, 20);
        $auditCount = $this->exchangeModel->getExchangeCount($auditSet);
        $auditPaySum = $this->exchangeModel->getExchangeSum($auditSet);
        $auditPages = pages($auditCount, $page, 20, $pageUrl, $array = array());

        $this->assign('startTime', $startTime);
        $this->assign('endTime', $endTime);
        $this->assign('auditList', $auditList);
        $this->assign('auditCount', $auditCount);
        $this->assign('auditPaySum', $auditPaySum);
        $this->assign("payType", $this->configModel['pay_type']);
        $this->assign('keyword', $keyword);
        $this->assign('type', $type);
        $this->assign('auditPages', $auditPages);
        $this->assign('page', $page);

        $this->getViewer()->needLayout(false);
        $this->render('audit_pay_list');
    }

    public function auditAction()
    {
        $dosubmit = daddslashes($this->postVar('dosubmit', ''));
        $payId = (int)$this->reqVar('pay_id', 0);
        $page = (int)$this->reqVar('page', 1);
        $aidArr = daddslashes($this->postVar('aid', ''));

        if (empty($aidArr) && !empty($payId) && !empty($dosubmit))
        {
            $aidArr[] = $payId;
        }

        if (!empty($aidArr))
        {
            $auditArr = array();
            foreach ($aidArr as $key => $val)
            {
                $exchangeRe = $this->exchangeModel->getExchange(array('id' => $val, 'ptype' => 3));
                if (!empty($exchangeRe['id']) && !empty($exchangeRe['uid']))
                {
                    //支付状态特殊处理
                    $re = $this->exchangeModel->alipayAudit($exchangeRe['id'], UNAME);
                    if ($re)
                    {
                        $auditArr[] = $val;
                        $url = "http://b.yxpopo.com/admin_quest_bytype.do?uid={$exchangeRe['uid']}&quest_type=6";
                        file_get_contents($url);
                    }
                }
            }
            if ($auditArr)
            {
                $logAdd['app'] = $this->_application;
                $logAdd['controller'] = $this->_controller;
                $logAdd['action'] = $this->_action;
                $logAdd['content'] = json_encode($auditArr);
                $logAdd['ip'] = get_real_ip();
                $logAdd['operat'] = UNAME;
                $this->operateLogModel->addOpLog($logAdd);
            }
        }
        $this->redirect('', '/admin/audit_pay/?page=' . $page, 0);
    }

    public function historyAction()
    {
        $search = daddslashes($this->postVar('search', ''));
        $startTime = daddslashes($this->reqVar('start_time', ''));
        $endTime = daddslashes($this->reqVar('end_time', ''));
        $page = (int)$this->reqVar('page', 1);

        $pageUrl = "/admin/audit_pay/history";

        $auditSet['pay_status'] = 3;
        $auditSet['ptype'] = 3;

        if (empty($startTime))
            $startTime = date("Y-m-d", time());

        if (empty($endTime))
            $endTime = date("Y-m-d", time());

        $startTimeWhere = date("Y-m-d 00:00:00", strtotime($startTime));
        $auditSet['condition'] .= " AND update_time >='$startTimeWhere'";
        $pageUrl .= "?start_time=$startTime";

        $endTimeWhere = date("Y-m-d 23:59:59", strtotime($endTime));
        $auditSet['condition'] .= " AND update_time <='$endTimeWhere'";
        $pageUrl .= "&end_time=$endTime";

        $auditSet['orderby'] = " update_time desc";
        $auditList = $this->exchangeModel->getExchangeList($auditSet, $page, 20);
        $auditCount = $this->exchangeModel->getExchangeCount($auditSet);
        $auditPaySum = $this->exchangeModel->getExchangeSum($auditSet);
        $auditPages = pages($auditCount, $page, 20, $pageUrl, $array = array());

        $this->assign('startTime', $startTime);
        $this->assign('endTime', $endTime);
        $this->assign('auditList', $auditList);
        $this->assign('auditCount', $auditCount);
        $this->assign('auditPaySum', $auditPaySum);
        $this->assign("payType", $this->configModel['pay_type']);
        $this->assign("payStatus", $this->configModel['pay_status']);
        $this->assign('auditPages', $auditPages);
        $this->assign('page', $page);

        $this->getViewer()->needLayout(false);
        $this->render('audit_pay_history');
    }

    public function refundAction()
    {
        $payId = (int)$this->reqVar('pay_id', 0);
        $dosubmit = daddslashes($this->postVar('dosubmit', ''));
        $remark = daddslashes($this->postVar('remark', ''));

        $setTimeOut = 0;
        $exchangeRe = $this->exchangeModel->getExchange(array('id' => $payId));
        if (!empty($exchangeRe['id']) && !empty($exchangeRe['uid']))
        {
            if (!empty($dosubmit))
            {
                if (empty($remark))
                {
                    $this->redirect('说明不能为空!', '', 5);
                    die();
                }
                //退款
                $this->exchangeModel->alipayRefund($exchangeRe['id'], $remark, UNAME);
                $setTimeOut = 1;
            }
        }
        $this->assign('setTimeOut', $setTimeOut);
        $this->assign('payId', $payId);
        $this->getViewer()->needLayout(false);
        $this->render('audit_pay_refund');
    }

    public function excelAction()
    {
        $startTime = daddslashes($this->getVar('start_time', ''));
        $endTime = daddslashes($this->getVar('end_time', ''));
        $keyword = daddslashes($this->reqVar('keyword', ''));
        $type = (int)$this->reqVar('type', 0);
        if (empty($startTime) || empty($endTime))
        {
            $this->redirect('导出失败,请选择时间!', '/admin/audit_pay/', 1);
            die();
        }

        $excelContent = $this->payExcelTemplate($startTime, $endTime, $keyword, $type);
        if (empty($excelContent))
        {
            $this->redirect('导出失败,无法获取内容!', '', 1);
            die();
        }
        $excelData = iconv('utf-8', 'gbk', $excelContent);
        header('Content-type:application/vnd.ms-excel;charset=gbk');
        header("Content-Disposition:filename=alipay_unpay.csv");
        echo $excelData;
        // header("Content-Disposition:filename=" . iconv('utf-8','gbk',"支付宝待付款记录".date("YmdHi",time())) . ".csv");
    }

    private function payExcelTemplate($startTime, $endTime, $keyword, $type)
    {
        $whereListStr = " pay_status=2 AND ptype=3";
        if (!empty($startTime))
        {
            $whereListStr .= " AND update_time >= '$startTime'";
        }
        if (!empty($endTime))
        {
            $whereListStr .= " AND update_time <= '$endTime'";
        }
        if (!empty($keyword))
        {
            if (1 == $type)
            {
                $whereListStr .= " AND id='$keyword'";
            } elseif (2 == $type)
            {
                $whereListStr .= " AND pay_content='$keyword'";
            } elseif (3 == $type)
            {
                $whereListStr .= " AND uid='$keyword'";
            }
        }
        $exchange1List = $this->exchangeModel->query("SELECT * FROM z_present_exchange WHERE $whereListStr ORDER BY id DESC");
        if (!$exchange1List) return;

        $replaceArr = array("・", "&nbsp;", " ", "•");
        $excelContent = "记录ID, 用户ID, 实际支付金额(单位:元), 支付账户, 支付姓名, 身份证号, 备注\r\n";
        foreach ($exchange1List as $key => $val)
        {
            $pay = $val['pay'] / 100;
            $payContent = str_replace($replaceArr, "", $val['pay_content']);
            $payUserName = str_replace($replaceArr, "", $val['pay_user_name']);
            $remark = $val['id'] . "红包锁屏";
            $pay_idcard = str_replace($replaceArr, "", $val['pay_idcard']);

            $excelContent .= $val['id'] . ',' . $val['uid'] . ',' . $pay . ',' . $payContent . ',' . $payUserName . ',' . $pay_idcard . ',' . $remark . "\r\n";
        }
        return $excelContent;
    }

}
