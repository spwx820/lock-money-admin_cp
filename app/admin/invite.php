<?php
/**
 * 邀请统计
 *
 * @category   Leb
 * @package    Leb_Action
 * @author     lihui
 * @version    $Id: invite.php 2014-09-03 9:58:00 lihui
 * @copyright (c) 2014 dianjoy.com
 * @license
 */
class inviteController extends Application
{
    private $userModel;
    private $configModel;

    public function  execute($plugins)
    {
        $this->userModel = $this->loadAppModel('User');
        $this->configModel = C('global.php');
    }

    public function indexAction()
    {
        $searchTime = daddslashes($this->postVar('search_time', ''));

        if (!empty($searchTime))
        {
            $searchDate = $searchTime;
        } else
        {
            $searchDate = date("Y-m-d", time());
        }

        $startTime = $searchDate . ' 00:00:00';
        $endTime = $searchDate . ' 23:59:59';

        $whereStr = " ctime>='$startTime' AND ctime<='$endTime'";
        $inviteList = $this->userModel->query("SELECT invite_code,COUNT(*) as user_num,LEFT(ctime,10) as cdate,pnum,score
                                               FROM z_user
                                               WHERE $whereStr AND score>500 AND invite_code>0 AND status > 0
                                               GROUP BY invite_code ORDER BY user_num desc LIMIT 100");

        if ($inviteList)
        {
            $i = 1;
            foreach ($inviteList as $key => $val)
            {
                $inviteList[$key] = $val;
                $inviteList[$key]['num'] = $i;
                $i++;
            }
        }
        $this->assign('inviteList', $inviteList);

        $this->getViewer()->needLayout(false);
        $this->render('invite');
    }

    public function detailAction()
    {
        $inviteCode = daddslashes($this->reqVar('code', ''));
        $inviteDate = daddslashes($this->reqVar('date', ''));
        $page = (int)$this->reqVar('page', 1);

        if (!empty($inviteCode) && !empty($inviteDate))
        {
            $startTime = $inviteDate . ' 00:00:00';
            $endTime = $inviteDate . ' 23:59:59';
            $inviteSet['invite_code'] = $inviteCode;
            $inviteSet['start_time'] = $startTime;
            $inviteSet['end_time'] = $endTime;
            $inviteSet['condition'] = " AND score>500";

            $inviteList = $this->userModel->getUserList($inviteSet, $page, 100);
            $inviteCount = $this->userModel->getUserCount($inviteSet);
            $invitePages = pages($inviteCount, $page, 100, '', $array = array());

            $this->assign("userStatus", $this->configModel['user_status']);
            $this->assign('inviteList', $inviteList);
            $this->assign('inviteCount', $inviteCount);
            $this->assign('invitePages', $invitePages);
        }
        $this->getViewer()->needLayout(false);
        $this->render('invite_detail');
    }

}
