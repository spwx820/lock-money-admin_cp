<?php
/**
 * 邀请白名单
 *
 * @category   Leb
 * @package    Leb_Action
 * @author     lihui
 * @version    $Id: invite_hold.php 2014-12-08 9:58:00 lihui
 * @copyright (c) 2014 dianjoy.com
 * @license
 */
class invite_holdController extends Application
{
    private $inviteHoldExceptionModel;
    private $operateLogModel;
    private $userModel;
    private $configModel;

    public function  execute($plugins)
    {
        $this->inviteHoldExceptionModel = $this->loadModel('Invite_hold_exception');
        $this->operateLogModel = $this->loadModel('Operate_log', array(), 'admin');
        $this->userModel = $this->loadAppModel('User');
        $this->configModel = C('global.php');
    }

    public function indexAction()
    {
    }

    //校园灰名单列表
    public function exceptionAction()
    {
        $keyword = daddslashes($this->reqVar('keyword', ''));
        $startTime = daddslashes($this->reqVar('start_time', ''));
        $endTime = daddslashes($this->reqVar('end_time', ''));
        $page = (int)$this->reqVar('page', 1);

        $pageUrl = "/admin/invite_hold/exception";
        $holdSet = array();
        if (!empty($keyword))
        {
            $holdSet['uid'] = $keyword;
            $pageUrl .= "?keyword=$keyword";
        }

        if (!empty($startTime))
        {
            $holdSet['condition'] = " AND ctime >='$startTime 00:00:00'";
            $pageUrl .= !empty($keyword) ? '&' : '?';
            $pageUrl .= "start_time=$startTime";
        }
        if (!empty($endTime))
        {
            $holdSet['condition'] .= " AND ctime <='$endTime 23:59:59'";
            if (empty($keyword) && empty($startTime))
            {
                $pageUrl .= "?end_time=$endTime";
            } else
            {
                $pageUrl .= "&end_time=$endTime";
            }
        }

        $holdList = $this->inviteHoldExceptionModel->getInviteHoldExceptionList($holdSet, $page, 20);
        $holdCount = $this->inviteHoldExceptionModel->getInviteHoldExceptionCount($holdSet);
        $holdPages = pages($holdCount, $page, 20, $pageUrl, $array = array());

        $this->assign('holdList', $holdList);
        $this->assign('holdPages', $holdPages);
        $this->assign('keyword', $keyword);
        $this->assign('startTime', $startTime);
        $this->assign('endTime', $endTime);
        $this->getViewer()->needLayout(false);
        $this->render('invite_hold_exception');
    }

    //校园灰名单添加
    public function  exception_addAction()
    {
        $dosubmit = daddslashes($this->postVar('dosubmit', ''));
        $uidBatch = daddslashes($this->postVar('uid_batch', ''));
        $uidBatch = str_replace(array("\n", "\r", "\t", '，', ' '), array(',', ',', ',', ',', ''), $uidBatch);

        if (!empty($dosubmit))
        {
            if (empty($uidBatch))
            {
                $this->redirect('用户ID不能为空!', '', 1);
                die();
            }
            $uidArr = array_filter(explode(",", trim($uidBatch, ",")));
            $uidArr = array_unique($uidArr); //去重

            //过滤uid
            $userInfo = $holdInfo = array();
            foreach ($uidArr as $val)
            {
                if (!is_numeric($val))
                    continue;

                //判断用户是否存在
                $userSet['uid'] = $val;
                $userSet['status'] = 1;
                $userRe = $this->userModel->getUser($userSet);

                //判断是否已导入
                $holdRe = $this->inviteHoldExceptionModel->getInviteHoldException($val);
                if ($userRe && empty($holdRe))
                {
                    $userInfo[] = $val;
                } elseif (!empty($holdRe))
                {
                    $holdInfo[] = $val;
                }
            }
            if (empty($userInfo) || 500 < count($userInfo))
            {
                $this->redirect('用户ID不存在或数量超出限制!', '', 1);
                die();
            }

            if (!empty($userInfo))
            {
                foreach ($userInfo as $uval)
                {
                    $holdAdd['uid'] = $uval;
                    $this->inviteHoldExceptionModel->addInviteHoldException($holdAdd);
                }

                //操作记录
                $logAdd['app'] = $this->_application;
                $logAdd['controller'] = $this->_controller;
                $logAdd['action'] = $this->_action;
                $logAdd['content'] = json_encode($userInfo);
                $logAdd['ip'] = get_real_ip();
                $logAdd['operat'] = UNAME;
                $this->operateLogModel->addOpLog($logAdd);
            }
            $successCount = count($userInfo);
            $holdCount = count($holdInfo);
            $failCount = count($uidArr) - $successCount - $holdCount;
            $backStr = "成功：$successCount,失败：$failCount,过滤：$holdCount";
            $this->redirect($backStr, '/admin/invite_hold/exception', 0);
        }

        $this->assign('uidBatch', $uidBatch);
        $this->getViewer()->needLayout(false);
        $this->render('invite_hold_exception_add');
    }

    //校园灰名单删除
    public function  exception_delAction()
    {
        $uidArr = daddslashes($this->postVar('uid', ''));
        if (!empty($uidArr))
        {
            $delArr = array();
            foreach ($uidArr as $key => $val)
            {
                $re = $this->inviteHoldExceptionModel->deleteInviteHoldException($val);
                if ($re)
                {
                    $delArr[] = $val;
                }
            }
            if ($delArr)
            {
                $logAdd['app'] = $this->_application;
                $logAdd['controller'] = $this->_controller;
                $logAdd['action'] = $this->_action;
                $logAdd['content'] = json_encode($delArr);
                $logAdd['ip'] = get_real_ip();
                $logAdd['operat'] = UNAME;
                $this->operateLogModel->addOpLog($logAdd);
            }
        }
        $this->redirect('', '/admin/invite_hold/exception', 0);
    }

}