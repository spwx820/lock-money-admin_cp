<?php
/**
 * 收益统计
 *
 * @category   Leb
 * @package    Leb_Model
 * @author 	lihui
 * @version   $Id: income.php 1 2014-11-27 16:42 $
 * @copyright
 * @license
 */
class incomeController extends Application
{
    private $incomeModel;
    private $incomeDayModel;
    private $actionType;

    public function execute($plugins)
    {
        $this->incomeModel = $this->loadModel('Income');
        $this->incomeDayModel = $this->loadModel('Income_day');

        $configModel = C('global.php');
        $this->actionType = $configModel['hb_action_type'];
    }

    public function indexAction()
    {
        $startTime = daddslashes($this->reqVar('start_time', ''));
        $endTime = daddslashes($this->reqVar('end_time', ''));
        $page = (int)$this->reqVar('page', 1);

        $pageUrl = "/admin/income/";
        $incomeSet = array();
        if (!empty($startTime))
        {
            $incomeSet['condition'] .= " AND cdate >='$startTime'";
            $pageUrl .= "?start_time=$startTime";
        }

        if (!empty($endTime))
        {
            $incomeSet['condition'] .= " AND cdate <='$endTime'";
            $pageUrl .= !empty($startTime) ? "&" : "?";
            $pageUrl .= "end_time=$endTime";
        }
        $incomeList = $this->incomeDayModel->getIncomeDayList($incomeSet, $page, 60);
        if ($incomeList)
        {
            foreach ($incomeList as $key => $val)
            {
                $score = $val['score_ad'] + $val['score_right_catch'] + $val['score_register'] + $val['score_other'] + $val['score_task'] + $val['score_share'] + $val['score_exchange'] + $val['score_refund'];
                $incomeList[$key]['score'] = $score;
            }
        }
        $incomeCount = $this->incomeDayModel->getIncomeDayCount($incomeSet);
        $incomePages = pages($incomeCount, $page, 60, $pageUrl, $array = array());

        $this->assign('startTime', $startTime);
        $this->assign('endTime', $endTime);
        $this->assign('incomeList', $incomeList);
        $this->assign('incomeCount', $incomeCount);
        $this->assign('incomePages', $incomePages);

        $this->getViewer()->needLayout(false);
        $this->render('income');
    }

    public function typeAction()
    {
        $selectType = daddslashes($this->reqVar('select_type', ''));
        $startTime = daddslashes($this->reqVar('start_time', ''));
        $endTime = daddslashes($this->reqVar('end_time', ''));
        $cdate = daddslashes($this->reqVar('cdate', ''));
        $page = (int)$this->reqVar('page', 1);

        $pageUrl = "/admin/income/type";
        $incomeSet = array();
        if (is_numeric($selectType))
        {
            $incomeSet['action_type'] .= $selectType;
            $pageUrl .= "?select_type=$selectType";
        }

        if (!empty($cdate))
        {
            $startTime = $endTime = $cdate;
        } else
        {
            if (empty($startTime))
                $startTime = date("Y-m-d", strtotime('-7 day'));

            if (empty($endTime))
                $endTime = date("Y-m-d", time());
        }

        $incomeSet['condition'] .= " AND cdate >='$startTime'";
        if (is_numeric($selectType))
        {
            $pageUrl .= "&start_time=$startTime";
        } else
        {
            $pageUrl .= "?start_time=$startTime";
        }

        $incomeSet['condition'] .= " AND cdate <='$endTime'";
        $pageUrl .= "&end_time=$endTime";

        $incomeList = $this->incomeModel->getIncomeList($incomeSet, $page, 60);
        $incomeCount = $this->incomeModel->getIncomeCount($incomeSet);
        $incomeScoreSum = $this->incomeModel->getIncomeScoreSum($incomeSet);
        $incomePages = pages($incomeCount, $page, 60, $pageUrl, $array = array());

        $this->assign('actionType', $this->actionType);
        $this->assign('startTime', $startTime);
        $this->assign('endTime', $endTime);
        $this->assign('incomeList', $incomeList);
        $this->assign('incomeCount', $incomeCount);
        $this->assign('incomeScoreSum', $incomeScoreSum);
        $this->assign('incomePages', $incomePages);

        $this->getViewer()->needLayout(false);
        $this->render('income_type');
    }

}