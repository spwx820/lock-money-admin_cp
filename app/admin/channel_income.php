<?php
/**
 * 渠道收益操作
 *
 * @category   Leb
 * @package    Leb_Action
 * @author     lihui
 * @version    $Id: channel_income.php 2015-04-30 9:58:00 lihui
 * @copyright (c) 2015 dianjoy.com
 * @license
 */
class channel_incomeController extends Application
{
    private $configModel;
    private $userModel;
    private $channelSetModel;
    private $channelIncomeSetModel;
    private $channelIncomeModel;
    private $operateLogModel;

    public function execute($plugins)
    {
        $this->configModel = C('global.php');
        $this->userModel = $this->loadAppModel('User');
        $this->channelSetModel = $this->loadModel('Channel_set');

        $this->userModel = $this->loadAppModel('User');
        $this->channelIncomeSetModel = $this->loadModel('Channel_income_set');
        $this->channelIncomeModel = $this->loadModel('Channel_income');
        $this->operateLogModel = $this->loadModel('Operate_log',array(),'admin');
    }

    public function indexAction()
    {
        $channel = daddslashes($this->reqVar('channel',''));
        $rdate = daddslashes($this->reqVar('rdate',''));
        $page = (int)$this->reqVar('page',1);

        $channelISet = array();
        $pageUrl = "/admin/channel_income/";
        if(!empty($channel)){
            $channelISet['channel'] = $channel;
            $pageUrl .= "?channel=$channel";
        }

        if(!empty($rdate)){
            $channelISet['rdate'] = $rdate;
            $pageUrl .= !empty($channel) ? "&rdate=$rdate" : "?rdate=$rdate";
        }
        $channelISetList  = $this->channelIncomeSetModel->getCICSList($channelISet,$page,100);
        $channelISetCount = $this->channelIncomeSetModel->getCICSC($channelISet);
        $channelISetPages = pages($channelISetCount,$page,100,$pageUrl,$array = array());

        $this->assign('channelISetList', $channelISetList);
        $this->assign('channelISetCount', $channelISetCount);
        $this->assign('channelISetPages', $channelISetPages);
        $this->assign('channel', $channel);
        $this->assign('rdate', $rdate);

        $this->getViewer()->needLayout(false);
        $this->render('channel_income_set_list');
    }

    public function addAction()
    {
        $dateNow = date("Y-m-d",time());
        $dateNowS = date("Y-m-d H:i:s",time());
        $dosubmit = daddslashes($this->postVar('dosubmit',''));
        $channelArr = daddslashes($this->postVar('channel',''));
        $rdate = daddslashes($this->postVar('rdate',''));
        if(!empty($dosubmit)){
            if(empty($channelArr) || !is_array($channelArr)){
                $this->redirect('请选择渠道号!', '', 3);
                die();
            }elseif(empty($rdate)){
                $this->redirect('请选择注册日期!', '', 3);
                die();
            }elseif($dateNow <= $rdate){
                $this->redirect('注册日期不能大于当前日期!', '', 3);
                die();
            }

            $channelBucket = array();
            $isAdd = 0;
            $userSet['start_time'] = $rdate.' 00:00:00';
            $userSet['end_time'] = $rdate.' 23:59:59';
            foreach($channelArr as $key=>$val){
                $channelIncomeSet['channel'] = $val;
                $channelIncomeSet['rdate'] = $rdate;
                $isChannelIncomeSet = $this->channelIncomeSetModel->getCICS($channelIncomeSet);
                if($isChannelIncomeSet)
                    continue;

                $isAdd = 1;
                $userSet['channel'] = trim($val);
                $userCount = $this->userModel->getUserCount($userSet);

                //用户数大于0时添加
                $channelIncomeAdd['rnum']  = (int)$userCount;
                if($channelIncomeAdd['rnum'] >= 50){
                    $channelIncomeAdd['channel'] = $val;
                    $channelIncomeAdd['rdate'] = $rdate;
                    $channelIncomeAdd['creater'] = UNAME;
                    $channelIncomeAdd['ctime'] = $dateNowS;
                    $this->channelIncomeSetModel->addCICS($channelIncomeAdd);

                    $channelBucket[] = $val;
                }
            }
            if($isAdd == 0){
                $this->redirect('该配置已存在!', '', 3);
                die();
            }elseif(empty($channelBucket)){
                $this->redirect('注册用户数小于50!', '', 3);
                die();
            }
            $this->redirect('', '/admin/channel_income/', 0);
        }
        $channelSet = $this->channelSetModel->query("SELECT channel FROM a_channel_set WHERE status=1 ORDER BY channel DESC");

        $this->assign('channelSet', $channelSet);
        $this->getViewer()->needLayout(false);
        $this->render('channel_income_set_add');
    }

    public function detailAction()
    {
        $sid = (int)$this->reqVar('id',0);
        $page = (int)$this->reqVar('page',1);
        if(!empty($sid)){
            $channelISet['ci_id'] = $sid;
            $channelIList  = $this->channelIncomeModel->getCICList($channelISet,$page,100);
            $channelICount = $this->channelIncomeModel->getCICC($channelISet);
            $channelIPages = pages($channelICount,$page,100,'',$array = array());

            $this->assign('channelIList', $channelIList);
            $this->assign('channelICount', $channelICount);
            $this->assign('channelIPages', $channelIPages);
        }

        $this->assign('sid', $sid);
        $this->getViewer()->needLayout(false);
        $this->render('channel_income_detail');
    }

    //删除操作
    public function delAction()
    {
        $cidArr = daddslashes($this->postVar('cid',''));
        if(!empty($cidArr)){
            $delArr = array();
            foreach($cidArr as $key=>$val){
                $re = $this->channelIncomeSetModel->deleteCICS($val);
                if($re){
                    $delArr[] = $val;
                }
            }
            if($delArr){
                $logAdd['app'] = $this->_application;
                $logAdd['controller'] = $this->_controller;
                $logAdd['action'] = $this->_action;
                $logAdd['content'] = json_encode($delArr);
                $logAdd['ip'] = get_real_ip();
                $logAdd['operat'] = UNAME;
                $this->operateLogModel->addOpLog($logAdd);
            }
        }
        $this->redirect('', '/admin/channel_income/', 0);
    }

}