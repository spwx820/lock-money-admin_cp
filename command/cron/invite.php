<?php
/**
 * 邀请统计运行程序（打包管理使用）（存储昨天数据）
 *
 * @category   Leb
 * @package    Leb_Action
 * @author     lihui
 * @version    $Id: invite.php 2014-10-08 10:30:00 lihui
 * @copyright (c) 2014 dianjoy.com
 * @license
 */
class inviteCommand extends Application
{
    private $inviteCountModel;
    private $userModel;
    private $packageModel;

    public function  execute($plugins)
    {
        $this->userModel = $this->loadAppModel('User');
        $this->inviteCountModel = $this->loadAppModel('Invite_count');
        $this->packageModel = $this->loadModel('Package',array(),'admin');
    }

    public function indexAction()
    {
        $yesterday = date("Y-m-d",strtotime('-1 day'));
        $inviteWhere = "cdate='$yesterday'";
        $isCount = $this->inviteCountModel->query("SELECT 'X' FROM t_invite_count WHERE $inviteWhere LIMIT 1");
        if(!$isCount){
            $re = $this->packageModel->query("SELECT id,uid FROM a_package_log WHERE uid>0 ORDER BY createtime ASC");
            if($re){
                $userUnique = array();
                foreach($re as $key=>$val){
                    //去重操作
                    if(!empty($userUnique[$val['uid']])){
                        continue;
                    }
                    $userUnique[$val['uid']] = $val['id'];

                    //添加统计数据
                    $inviteAdd['uid'] = $val['uid'];
                    $inviteAdd['cdate'] = $yesterday;
                    $inviteAdd['status'] = 1;
                    $inviteAdd['ctime'] = date("Y-m-d H:i:s",time());
                    $inviteAdd['share_num'] = 0;
                    $inviteAdd['num'] = 0;
                    if(!empty($val['uid'])){
                        $userSet['invite_code'] = $val['uid'];
                        $userSet['condition'] = " AND left(ctime,10)='$yesterday'";
                        $inviteAdd['num'] = $this->userModel->getUserCount($userSet);
                        $inviteAdd['share_num'] = $this->countShareNum($val['uid'],$yesterday);
                    }
                    $this->inviteCountModel->addInviteCount($inviteAdd);
                    usleep(500);
                }
                echo $yesterday."succeed\r\n";
            }
        }
        die();
    }

    private function countShareNum($uid,$yesterday)
    {
        if(empty($uid) || empty($yesterday))
            return 0;

        //读取上次share_num>0数
        $maxShareNum = 0;
        $inviteWhere = "uid='$uid' AND share_num>0";
        $inviteSql = "SELECT SUM(share_num) as s_num FROM t_invite_count WHERE $inviteWhere ORDER BY id DESC LIMIT 1";
        $maxShareNumRe = $this->inviteCountModel->query($inviteSql);
        if(!empty($maxShareNumRe[0]['s_num'])){
            $maxShareNum = $maxShareNumRe[0]['s_num'];
        }

        //统计二次分享数总和
        $userShareWhere = "score_register>0 AND ctime<='$yesterday 23:59:59' AND invite_code='{$uid}'";
        $shareScoreRe = $this->userModel->query("SELECT SUM(score_register) as share_score FROM z_user WHERE $userShareWhere LIMIT 1");
        $shareNumSum = ceil($shareScoreRe[0]['share_score']/200);

        //按天统计二次分享数
        $shareNum = $shareNumSum - $maxShareNum;

        return $shareNum;
    }
}