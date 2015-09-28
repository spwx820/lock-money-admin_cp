<?php
/**
 * 渠道统计API
 *
 * @category   Leb
 * @package    Leb_Action
 * @author     lihui
 * @version    $Id: channel_api.php 2014-09-03 9:58:00 lihui
 * @copyright (c) 2014 dianjoy.com
 * @license
 */
class channel_apiController extends Application
{

    public function  execute($plugins)
    {

    }

    public function indexAction()
    {
        $dosubmit = daddslashes($this->reqVar('dosubmit',''));
        $channel = daddslashes(trim($this->reqVar('channel','')));
        $startDate = daddslashes($this->reqVar('start_date',''));
        $endDate = daddslashes($this->reqVar('end_date',''));

//        if(empty($startDate)){
//            $startDate = date("Y-m-d",time()-604800);
//        }
//        if(empty($endDate)){
//            $endDate  = date("Y-m-d",time());
//        }

        $outUrl = '';
        if(!empty($dosubmit) && !empty($channel)){
            $passwd = md5("dianjoy".$channel);
            $outUrl = _DOMAIN_."/api/channel/?channel={$channel}&passwd={$passwd}";
        }
        $this->assign('channel', $channel);
        $this->assign('startDate', $startDate);
        $this->assign('endDate', $endDate);
        $this->assign('outUrl', $outUrl);

        $this->getViewer()->needLayout(false);
        $this->render('channel_api');
    }


    public function invite_codeAction()
    {
        $dosubmit = daddslashes($this->reqVar('dosubmit',''));
        $invite_code = daddslashes(trim($this->reqVar('invite_code','')));
        $startDate = daddslashes($this->reqVar('start_date',''));
        $endDate = daddslashes($this->reqVar('end_date',''));

        $out_invite_code = '';
        if(!empty($dosubmit) && !empty($invite_code)){
            $passwd = md5("dianjoy".$invite_code);
            $out_invite_code = _DOMAIN_."/api/invite_code/?invite_code={$invite_code}&passwd={$passwd}";
        }
        $this->assign('invite_code', $invite_code);
        $this->assign('startDate', $startDate);
        $this->assign('endDate', $endDate);
        $this->assign('out_invite_code', $out_invite_code);

        $this->getViewer()->needLayout(false);
        $this->render('channel_api');
    }
}
