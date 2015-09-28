<?php
/**
 * 渠道设置
 *
 * @category   Leb
 * @package    Leb_Action
 * @author     lihui
 * @version    $Id: channel_set.php 2014-09-03 9:58:00 lihui
 * @copyright (c) 2014 dianjoy.com
 * @license
 */
class invite_code_channel_setController extends Application
{
    private $configModel;
    private $channelSetModel;
    private $operateLogModel;

    public function execute($plugins)
    {
        $this->configModel = C('global.php');
        $this->channelSetModel = $this->loadModel('invite_code_channel_set');
        $this->operateLogModel = $this->loadModel('Operate_log');

    }

    public function indexAction()
    {
        $search = daddslashes($this->postVar('search', ''));
        $keyword = daddslashes($this->reqVar('keyword', ''));
        $page = (int)$this->reqVar('page', 1);

        $channelSet = array();
        $pageUrl = "/admin/invite_code_channel_set/";
        if (!empty($keyword))
        {
            $channelSet['channel'] = $keyword;
            $pageUrl .= "?keyword=$keyword";
        }
        $channelList = $this->channelSetModel->getChannelSetList($channelSet, $page, 100);

        $channelSetCount = $this->channelSetModel->getChannelSetCount($channelSet);
        $channelPages = pages($channelSetCount, $page, 100, $pageUrl, $array = array());

        $this->assign('channelList', $channelList);
        $this->assign('channelSetCount', $channelSetCount);
        $this->assign("channelStatus", $this->configModel['public_status']);
        $this->assign('channelPages', $channelPages);
        $this->assign('page', $page);
        $this->assign('keyword', $keyword);
        $this->assign('adminId', UID);

        $this->getViewer()->needLayout(false);
        $this->render('invite_code_channel_set_list');
    }

    public function addAction()
    {
        $dosubmit = daddslashes($this->postVar('dosubmit', ''));
        $channelAdd['code_channel'] = daddslashes(trim($this->postVar('channel', '')));
        $channelAdd['remark'] = daddslashes(trim($this->postVar('remark', '')));
        $channelAdd['weight'] = $this->postVar('weight', 0);
        $channelAdd['currency'] = $this->postVar('currency', 0);


        if (!empty($dosubmit))
        {

            if(!is_numeric($channelAdd['weight']) or intval($channelAdd['weight']) <= 0 or intval($channelAdd['weight']) > 100 )
            {
                $this->redirect('请填写正确权重值(1~100)!', '', 3);
            }
            if(!is_numeric($channelAdd['currency']) or intval($channelAdd['currency']) <=0)
            {
                $this->redirect('请填写正确的金额!', '', 3);
            }

            if (empty($channelAdd['code_channel']))
            {
                $this->redirect('请填写邀请码号!', '', 3);
                die();
            } elseif (empty($channelAdd['remark']))
            {
                $this->redirect('请填写备注!', '', 3);
                die();
            } else
            {
                preg_match('/^[0-9]{8}$/', $channelAdd['code_channel'], $result);
                if (empty($result))
                {
                    $this->redirect('邀请码号不正确!', '', 3);
                }
            }
            $channelSet['code_channel'] = $channelAdd['code_channel'];

            if ($this->channelSetModel->getChannelSet($channelSet))
            {
                $this->redirect('邀请码号已存在', '', 3);
                die();
            }

            $channelAdd['operator'] = UNAME;
            $channelAdd['status'] = 1;
//            $this->channelSetModel->addChannelSet($channelAdd);

            $channelAdd['operate_time'] = date("Y-m-d H:i:s",time());
            $this->channelSetModel->query("insert into a_invite_code_channel_set (code_channel, weight, status, operator, operate_time, ctime, remark, invite_num, currency)
                                          values({$channelAdd['code_channel']}, {$channelAdd['weight']}, 0, '{$channelAdd['operator']}', '{$channelAdd['operate_time']}', '{$channelAdd['operate_time']}', '{$channelAdd['remark']}', 0, {$channelAdd['currency']})");

            $channelId = $this->channelSetModel->query('SELECT code_channel FROM a_invite_code_channel_set ORDER BY id DESC LIMIT 1')[0]['code_channel'];
            $channel_op['op'] = "add : $channelId";
            $this->oplog($channel_op);

            $this->redirect('', '/admin/invite_code_channel_set/', 0);
        }

        $this->assign('adminId', UID);

        $this->getViewer()->needLayout(false);
        $this->render('invite_code_channel_set_add');
    }

    public function editAction()
    {

        $page = (int)$this->reqVar('page', 1);
        $dosubmit = daddslashes($this->postVar('dosubmit', ''));
        $channelId = (int)$this->reqVar('channel_id', 0);

        $getChannelSet = $this->channelSetModel->getChannelSet(array("id" => $channelId));
        if (empty($getChannelSet))
        {
            $this->redirect('参数不能为空', '', 0);
            die();
        }
        $channelAdd['code_channel'] = daddslashes(trim($this->postVar('channel', '')));
        $channelAdd['remark'] = daddslashes(trim($this->postVar('remark', '')));
        $channelAdd['weight'] = $this->postVar('weight', 0);
        $channelAdd['currency'] = $this->postVar('currency', 0);

        if (!empty($dosubmit))
        {
            if(!is_numeric($channelAdd['weight']) or intval($channelAdd['weight']) <= 0 or intval($channelAdd['weight']) > 100 )
            {
                $this->redirect('请填写正确权重值(1~100)!', '', 3);
            }
            if(!is_numeric($channelAdd['currency']) or intval($channelAdd['currency']) <=0)
            {
                $this->redirect('请填写正确的金额!', '', 3);
            }

            if (empty($channelAdd['code_channel']))
            {
                $this->redirect('请填写邀请码号!', '', 3);
                die();
            } elseif (empty($channelAdd['remark']))
            {
                $this->redirect('请填写备注!', '', 3);
                die();
            } else
            {
                preg_match('/^[0-9]{8}$/', $channelAdd['code_channel'], $result);
                if (empty($result))
                {
                    $this->redirect('邀请码号不正确!', '', 3);
                }
            }
            $channelSet['code_channel'] = $channelAdd['code_channel'];
            $res = $this->channelSetModel->getChannelSet($channelSet);
            if ($res and "{$res['id']}" != "$channelId")
            {
                $this->redirect('邀请码号已存在', '', 3);
                die();
            }

            $channelSet['code_channel'] = $channelAdd['code_channel'];

            $channelAdd['operator'] = UNAME;
            $channelAdd['status'] = 1;

            $channelAdd['operate_time'] = date("Y-m-d H:i:s",time());

            $this->channelSetModel->query("update a_invite_code_channel_set set code_channel = {$channelAdd['code_channel']}, weight = {$channelAdd['weight']}, remark = '{$channelAdd['remark']}', currency = {$channelAdd['currency']} WHERE id = $channelId;");

            $channel_op['op'] = "edit : {$channelAdd['code_channel']}";
            $this->oplog($channel_op);

            $this->redirect('', '/admin/invite_code_channel_set/', 0);
        }
        $parentChannelSet['parent_id'] = 0;
        $parentChannelSelect = $this->channelSetModel->getChannelSetList($parentChannelSet, 1, 100);

        $this->assign('channelId', $channelId);
        $this->assign('getChannelSet', $getChannelSet);
        $this->assign('parentChannelSelect', $parentChannelSelect);
        $this->assign('page', $page);
        $this->assign('adminId', UID);

        $this->getViewer()->needLayout(false);
        $this->render('invite_code_channel_set_edit');
    }

    public function openAction()
    {
        $mId = (int)$this->reqVar('id', 0);
        if ($mId > 0)
        {
            $this->channelSetModel->channelValidate($mId);
            $code = $this->channelSetModel->query("SELECT code_channel FROM a_invite_code_channel_set WHERE id = $mId;")[0]['code_channel'];
            $this->clearRedis($code);
            $channel_op['op'] = "open : $mId";
            $this->oplog($channel_op);

        }
        $this->redirect('', '/admin/invite_code_channel_set/', 0);
    }

    public function shutAction()
    {
        $mId = (int)$this->reqVar('id', 0);
        if ($mId > 0)
        {
            $this->channelSetModel->channelDisable($mId);
            $channel_op['op'] = "close : $mId";
            $this->oplog($channel_op);
        }
        $this->redirect('', '/admin/invite_code_channel_set/', 0);
    }

    public function ajaxchannelAction()
    {
        $channel = daddslashes($this->getVar('channel', ''));
        if (!empty($channel))
        {
            $channelSetRe = $this->channelSetModel->getChannelSet(array("channel" => $channel));
            if ($channelSetRe)
            {
                exit("0");
            }
        }
        exit("1");
    }


    public function clearCacheAction()
    {
        $this->clearRedis();
        $this->redirect('清除成功', '/admin/invite_code_channel_set/', 0);

    }


    private function oplog($addContent)
    {
        if(empty($addContent)){
            return false;
        }

        //操作日志记录
        $logAdd['app'] = $this->_application;
        $logAdd['controller'] = $this->_controller;
        $logAdd['action'] = $this->_action;
        $logAdd['content'] = json_encode($addContent);
        $logAdd['ip'] = get_real_ip();
        $logAdd['operat'] = UNAME;
        $this->operateLogModel->addOpLog($logAdd);
    }
    private function clearRedis($user = '')
    {
        if(empty($user))
        {
            $key = "ZHUBO_INVITE_CODE_RATE_SET_KEY";
            $redis = Leb_Dao_Redis::getInstance();
            $res = $redis->get($key);
            if ($res)
                $redis->del($key);
        }
        else
        {
            $key = "ZHUBO_INVITE_CODE_KEY_FIRST$user";
            $redis = Leb_Dao_Redis::getInstance();
            $res = $redis->get($key);
            if ($res)
                $redis->del($key);
//            var_dump($redis->get($key));die();
        }
    }

}
