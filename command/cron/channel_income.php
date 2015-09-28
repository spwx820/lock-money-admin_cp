<?php
/**
 * 渠道收益统计（统计前15天）
 *
 * @category   Leb
 * @package    Leb_Action
 * @author     lihui
 * @version    $Id: channel_income.php 2015-04-28 10:30:00 lihui
 * @copyright (c) 2015 dianjoy.com
 * @license
 */
class Channel_incomeCommand extends Application
{
    private $userModel;
    private $channelIncomeSetModel;
    private $channelIncomeModel;

    public function execute($plugins)
    {
        $this->userModel = $this->loadAppModel('User');
        $this->channelIncomeSetModel = $this->loadModel('Channel_income_set',array(),'admin');
        $this->channelIncomeModel = $this->loadModel('Channel_income',array(),'admin');
    }

    public function indexAction($num)
    {
        $dateNow = date("Y-m-d",time());
        $dateNowS = date("Y-m-d H:i:s",time());
        $num = intval($num);
        if($num > 10 || $num < 0){
            $num = 1;
        }
        $channelISet['status'] = 1;
        $channelISetList  = $this->channelIncomeSetModel->getCICSList($channelISet,$num,20);
        if($channelISetList){
            foreach($channelISetList as $key=>$val){
                $channelISet['channel'] = trim($val['channel']);
                $channelISet['rdate'] = $val['rdate'];
                $channelISet['cdate'] = $dateNow;
                $isCIC = $this->channelIncomeModel->getCIC($channelISet);
                if($isCIC)
                    continue;

                $dataRe = $this->channeluser($channelISet['channel'],$val['rdate']);
                if(!empty($dataRe)){
                    $channelIAdd['ci_id'] = $val['id'];
                    $channelIAdd['channel'] = $channelISet['channel'];
                    $channelIAdd['rdate'] = $val['rdate'];
                    $channelIAdd['rnum']  = $val['rnum'];
                    $channelIAdd['cdate'] = $dateNow;
                    $channelIAdd['score_ad'] = !empty($dataRe['score_ad']) ? $dataRe['score_ad'] : 0;
                    $channelIAdd['score_register'] = !empty($dataRe['score_register']) ? $dataRe['score_register'] : 0;
                    $channelIAdd['i_score_ad'] = !empty($dataRe['i_score_ad']) ? $dataRe['i_score_ad'] : 0;
                    $channelIAdd['i_score_register'] = !empty($dataRe['i_score_register']) ? $dataRe['i_score_register'] : 0;
                    $channelIAdd['ctime'] = $dateNowS;
                    $this->channelIncomeModel->addCIC($channelIAdd);
                }
            }
        }
        die($dateNowS.'ok');
    }

    private function channeluser($channel,$rDate)
    {
        $reData = array('score_ad'=>0,'score_register'=>0,'i_score_ad'=>0,'i_score_register'=>0);
        if(empty($channel) || empty($rDate)){
            return $reData;
        }
        $sql = "SELECT uid FROM z_user WHERE channel='{$channel}' AND ctime>='{$rDate} 00:00:00' AND ctime <='{$rDate} 23:59:59'";
        $channelUserRe = $this->userModel->query($sql);
        if(!$channelUserRe){
            return $reData;
        }

        $scoreAd = $scoreRegister = $iScoreAd = $iScoreRegister = 0;
        foreach($channelUserRe as $key=>$val){
            $userRe = $this->userModel->getUser(array('uid'=>$val['uid']));
            if(!empty($userRe['score_ad'])){
                $scoreAd = $scoreAd + $userRe['score_ad'];
            }
            if(!empty($userRe['score_register'])){
                $scoreRegister = $scoreRegister + $userRe['score_register'];
            }

            $inviteRe = $this->userModel->query("SELECT SUM(score_ad) AS a_rmb, SUM(score_register) AS r_rmb
                                                 FROM z_user
                                                 WHERE invite_code='{$val['uid']}' LIMIT 1");
            if(!empty($inviteRe[0]['a_rmb'])){
                $iScoreAd = $iScoreAd + $inviteRe[0]['a_rmb'];
            }
            if(!empty($inviteRe[0]['r_rmb'])){
                $iScoreRegister = $iScoreRegister + $inviteRe[0]['r_rmb'];
            }
            //echo $val['uid']."--".$scoreAd."--".$scoreRegister."--".$iScoreAd."--".$iScoreRegister."\n\t";
        }
        $reData = array(
            'score_ad'=>$scoreAd,
            'score_register'=>$scoreRegister,
            'i_score_ad'=>$iScoreAd,
            'i_score_register'=>$iScoreRegister
        );
        return $reData;
    }

}