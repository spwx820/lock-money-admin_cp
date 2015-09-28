<?php
/**
 * 帖子抽奖
 *
 * @category   Leb
 * @package    Leb_Action
 * @author     lihui
 * @version    $Id: thread_raffle.php 2015-03-24 9:58:00 lihui
 * @copyright (c) 2015 dianjoy.com
 * @license
 */
class thread_raffleController extends Application
{
    private $raffleModel;
    private $winnersModel;
    private $siteThreadModel;
    private $isImagesStatus;
    private $siteUrl;

    public function execute($plugins)
    {
        $this->raffleModel = $this->loadModel('Thread_raffle');
        $this->winnersModel = $this->loadModel('Thread_raffle_winners');

        $dataDb['dbms'] = 'pdomysql';
        $dataDb['host'] = $_SERVER['ZHUAN_SITE_DB_HOST'];
        $dataDb['port'] = $_SERVER['ZHUAN_SITE_DB_PORT'];
        $dataDb['dbname'] = $_SERVER['ZHUAN_SITE_DB_NAME'];
        $dataDb['username'] = $_SERVER['ZHUAN_SITE_DB_USER'];
        $dataDb['password'] = $_SERVER['ZHUAN_SITE_DB_PASS'];
        $dataDb['charset'] = 'utf8';
        $dataDb['persist'] = '0';

        //连接官网库表
        $this->siteThreadModel = $this->loadAppModel('Site_thread', $dataDb);

        $globalConfig = C('global.php');
        $this->isImagesStatus = $globalConfig['is_images'];

        //官网URL
        $this->siteUrl = "http://www.hongbaosuoping.com/";
    }

    public function indexAction()
    {
        $keyword = daddslashes($this->reqVar('keyword', ''));
        $startTime = daddslashes($this->reqVar('start_time', ''));
        $endTime = daddslashes($this->reqVar('end_time', ''));
        $page = (int)$this->reqVar('page', 1);

        $pageUrl = "/admin/thread_raffle/";
        $raffleSet = array();
        if (!empty($keyword))
        {
            $raffleSet['tid'] = $keyword;
            $pageUrl .= "?keyword=$keyword";
        }

        if (!empty($startTime))
        {
            $raffleSet['start_time'] = $startTime . " 00:00:00";
            $pageUrl .= !empty($keyword) ? "&" : "?";
            $pageUrl .= "start_time=$startTime";
        }

        if (!empty($endTime))
        {
            $raffleSet['end_time'] = $startTime . " 23:59:59";
            $pageUrl .= (!empty($keyword) || !empty($startTime)) ? "&" : "?";
            $pageUrl .= "end_time=$endTime";
        }
        $raffleList = $this->raffleModel->getRaffleList($raffleSet);
        $raffleCount = $this->raffleModel->getRaffleCount($raffleSet);
        $rafflePages = pages($raffleCount, $page, 20, $pageUrl, array());

        $this->assign('raffleList', $raffleList);
        $this->assign('rafflePages', $rafflePages);
        $this->assign('isImagesStatus', $this->isImagesStatus);
        $this->assign('keyword', $keyword);
        $this->assign('page', $page);
        $this->assign('siteUrl', $this->siteUrl);

        $this->getViewer()->needLayout(false);
        $this->render('thread_raffle_list');
    }

    public function raffleAction()
    {
        $ajax = daddslashes($this->reqVar('ajax', ''));
        if ($ajax == 'tid')
        {
            $tid = (int)$this->reqVar('tid', 0);
            if ($tid > 0)
            {
                $isThread = $this->siteThreadModel->query("SELECT 'X' FROM zs_forum_post WHERE tid='$tid' LIMIT 1");
                if ($isThread)
                {
                    exit("1");
                }
            }
            exit("0");
        } else
        {
            $dosubmit = daddslashes($this->postVar('dosubmit', ''));
            $raffleAdd['tid'] = (int)$this->postVar('tid', 0);
            $raffleAdd['num'] = (int)$this->postVar('num', 0);
            $raffleAdd['is_images'] = (int)$this->postVar('is_images', 0);
            $raffleAdd['creater'] = UNAME;
            if (!empty($dosubmit))
            {
                if (empty($raffleAdd['tid']))
                {
                    $this->redirect('请填写帖子ID!', '', 3);
                    die();
                }
                if (empty($raffleAdd['num']))
                {
                    $this->redirect('请填写中奖人数!', '', 3);
                    die();
                }
                $whereStr = "";
                if ($raffleAdd['is_images'])
                {
                    $whereStr = " AND attachment>0";
                }
                $sqlCountStr = "SELECT count(distinct(authorid)) as num FROM zs_forum_post WHERE tid='{$raffleAdd['tid']}' AND first=0 $whereStr LIMIT 1";
                $fPostCountRe = $this->siteThreadModel->query($sqlCountStr);
                if (empty($fPostCountRe[0]['num']) || $fPostCountRe[0]['num'] <= $raffleAdd['num'])
                {
                    $this->redirect('中奖人数超出回帖数了!', '', 3);
                    die();
                }
                $rid = $this->raffleModel->addRaffle($raffleAdd);
                if ($rid > 0)
                {
                    $sqlStr = "SELECT pid FROM zs_forum_post WHERE tid='{$raffleAdd['tid']}' AND first=0 $whereStr group by authorid LIMIT 5000";
                    $fPostRe = $this->siteThreadModel->query($sqlStr);
                    if (!empty($fPostRe[0]['pid']))
                    {
                        //抽奖操作
                        $winnersSet = $raffleAdd;
                        $winnersSet['rid'] = $rid;
                        $winnersSet['post'] = $fPostRe;
                        $this->raffle($winnersSet);

                        $this->redirect('抽奖成功!', '/admin/thread_raffle/winners/?rid=' . $rid, 3);
                        die();
                    }
                }
            }
            $this->getViewer()->needLayout(false);
            $this->render('thread_raffle');
        }
    }

    public function winnersAction()
    {
        $rid = (int)$this->reqVar('rid', 0);
        $page = (int)$this->reqVar('page', 1);
        if (1 > $rid)
        {
            $this->redirect('无法获取抽奖ID!', '', 0);
        }
        $whereStr = " AND rid = $rid ";
        $tableStr = "a_thread_raffle_winners";
        $winnersList = $this->winnersModel->query("SELECT * FROM $tableStr WHERE 1 $whereStr");

        $this->assign('winnersList', $winnersList);
        $this->assign('rid', $rid);
        $this->assign('page', $page);
        $this->assign('siteUrl', $this->siteUrl);

        $this->getViewer()->needLayout(false);
        $this->render('thread_raffle_winners');
    }

    private function raffle($winnersSet)
    {
        if (empty($winnersSet['num']) || empty($winnersSet['post']) || empty($winnersSet['rid']))
            return false;

        $winnersRe = array_rand($winnersSet['post'], $winnersSet['num']);
        if (!$winnersRe)
            return false;

        foreach ($winnersRe as $key => $val)
        {
            if (!empty($winnersSet['post'][$val]['pid']))
            {
                $winnersAdd['pid'] = $winnersSet['post'][$val]['pid'];

                //回帖内容
                if ($winnersAdd['pid'])
                {
                    $sqlPStr = "SELECT tid,message,authorid,author FROM zs_forum_post WHERE pid='{$winnersAdd['pid']}' LIMIT 1";
                    $fPostRe = $this->siteThreadModel->query($sqlPStr);
                    if (!empty($fPostRe[0]['tid']))
                    {
                        $winnersAdd['tid'] = $fPostRe[0]['tid'];
                    }
                    if (!empty($fPostRe[0]['message']))
                    {
                        $winnersAdd['message'] = $fPostRe[0]['message'];
                    }
                    if (!empty($fPostRe[0]['authorid']))
                    {
                        $winnersAdd['authorid'] = $fPostRe[0]['authorid'];
                        $winnersAdd['author'] = $fPostRe[0]['author'];
                    }
                    if (!empty($fPostRe[0]['author']))
                    {
                        $winnersAdd['author'] = $fPostRe[0]['author'];
                    }
                }

                //获取邀请码、手机号
                if ($winnersAdd['authorid'])
                {
                    $sqlMStr = "SELECT mobile,invite_code FROM zs_ucenter_members WHERE uid='{$winnersAdd['authorid']}' LIMIT 1";
                    $membersRe = $this->siteThreadModel->query($sqlMStr);
                    if (!empty($membersRe[0]['mobile']))
                    {
                        $winnersAdd['pnum'] = $membersRe[0]['mobile'];
                    }
                    if (!empty($membersRe[0]['invite_code']))
                    {
                        $winnersAdd['uid'] = $membersRe[0]['invite_code'];
                    }
                }
                $this->winnersModel->addWinners($winnersSet['rid'], $winnersAdd);
            }
        }
        return true;
    }

    public function excelAction()
    {
        $rid = (int)$this->getVar('rid', 0);
        if (1 > $rid)
        {
            $this->redirect('导出失败,无法获取抽奖信息!', '', 1);
            die();
        }

        $excelContent = '';
        $whereStr = " AND rid = $rid ";
        $tableStr = "a_thread_raffle_winners";
        $winnersList = $this->winnersModel->query("SELECT * FROM $tableStr WHERE 1 $whereStr");
        if ($winnersList)
        {
            $excelContent = "帖子内容,帖子ID,回帖ID,官网UID,官网昵称,邀请码,手机号\r\n";
            foreach ($winnersList as $key => $val)
            {
                $excelContent .= $val['message'] . ',' . $val['tid'] . ',' . $val['pid'] . ',' . $val['authorid'] . ',' . $val['author'] . ',' . $val['uid'] . ',' . $val['pnum'] . "\r\n";
            }
        }
        if (empty($excelContent))
        {
            $this->redirect('导出失败,无法获取内容!', '', 1);
            die();
        }
        $excelData = iconv('utf-8', 'gbk', $excelContent);
        header('Content-type:application/vnd.ms-excel;charset=gbk');
        header("Content-Disposition:filename=" . iconv('utf-8', 'gbk', "抽奖" . $rid . "中奖名单") . ".csv");
        echo $excelData;
    }

}