<?php
/**
 * 渠道统计设置
 *
 * @category   Leb
 * @package    Leb_Action
 * @author     lihui
 * @version    $Id: channel_s_set.php 2014-09-03 9:58:00 lihui
 * @copyright (c) 2014 dianjoy.com
 * @license
 */
class channel_s_setController extends Application
{
    private $configModel;
    private $channelSetModel;

    public function  execute($plugins)
    {
        $this->configModel = C('global.php');
        $this->channelSetModel = $this->loadModel('Channel_s_set');
    }

    public function indexAction()
    {
        $page = (int)$this->reqVar('page',1);

        $channelSet = array();
        $channelList = $this->channelSetModel->getChannelSetList($channelSet,$page,100);
        $channelCount = $this->channelSetModel->getChannelSetCount($channelSet);
        $channelPages = pages($channelCount,$page,100,'',$array = array());

        $this->assign('channelList', $channelList);
        $this->assign('channelCount', $channelCount);
        $this->assign('channelPages', $channelPages);
        $this->assign("channelStatus", $this->configModel['channel_set_status']);

        $this->getViewer()->needLayout(false);
        $this->render('channel_s_set_list');
    }

    public function addAction()
    {
        $dosubmit = daddslashes($this->postVar('dosubmit',''));
        $channelSetAdd['channel'] = daddslashes($this->postVar('channel',''));
        $channelSetAdd['weight']  = (int)$this->postVar('weight',0);
        if(!empty($dosubmit) && !empty($channelSetAdd['channel']) && !empty($channelSetAdd['weight'])){
            $channelSetAdd['operat'] = UNAME;
            $channelSetAdd['status'] = 1;
            $channelSetAdd['ctime'] = $channelSetAdd['operatetime'] = date("Y-m-d H:i:s",time());
            $this->channelSetModel->addChannelSet($channelSetAdd);
            $this->redirect('', '/admin/channel_s_set/', 0);
        }

        $this->getViewer()->needLayout(false);
        $this->render('channel_s_set_add');
    }

    public function editAction()
    {
        $dosubmit = daddslashes($this->postVar('dosubmit',''));
        $sid = (int)$this->reqVar('sid',0);
        $weight = (int)$this->postVar('weight',0);
        if(!empty($sid)){
            $channelSetRe = $this->channelSetModel->getChannelSet(array("id"=>$sid));
            if(!empty($dosubmit) && $channelSetRe){
                $channelSet['weight'] = $weight;
                $channelSet['operat'] = UNAME;
                $channelSet['status'] = 1;
                $channelSet['operatetime'] = date("Y-m-d H:i:s",time());
                $this->channelSetModel->saveChannelSet($sid,$channelSet);
                $this->redirect('', '/admin/channel_s_set/', 0);
            }
            $this->assign("channelStatus", $this->configModel['channel_set_status']);
            $this->assign('channelSetRe', $channelSetRe);
        }
        $this->getViewer()->needLayout(false);
        $this->render('channel_s_set_edit');
    }

    public function ajaxchannelAction()
    {
        $channel = daddslashes($this->getVar('channel',''));
        if(!empty($channel)){
            $channelSetRe = $this->channelSetModel->getChannelSet(array("channel"=>$channel));
            if($channelSetRe){
                exit("0");
            }
        }
        exit("1");
    }

}
