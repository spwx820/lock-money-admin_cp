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
class channel_setController extends Application
{
    private $configModel;
    private $channelSetModel;
    private $operateLogModel;

    public function execute($plugins)
    {
        $this->configModel = C('global.php');
        $this->channelSetModel = $this->loadModel('Channel_set');
        $this->operateLogModel = $this->loadModel('Operate_log');
    }

    public function indexAction()
    {
        $search = daddslashes($this->postVar('search', ''));
        $keyword = daddslashes($this->reqVar('keyword', ''));
        $page = (int)$this->reqVar('page', 1);

        $channelSet = array();
        $pageUrl = "/admin/channel_set/";
        if (!empty($keyword))
        {
            $channelSet['channel'] = $keyword;
            $pageUrl .= "?keyword=$keyword";
        }
        $channelList = $this->channelSetModel->getChannelSetList($channelSet, $page, 100);
        if ($channelList)
        {
            foreach ($channelList as $key => $val)
            {
                if (empty($val['parent_id']))
                {
                    $channelList[$key]['parent_channel'] = '一级渠道';
                    continue;
                }
                $channelParent = $this->channelSetModel->getChannelSet(array('id' => $val['parent_id']));
                if ($channelParent)
                {
                    $channelList[$key]['parent_channel'] = $channelParent['channel'];
                } else
                {
                    $channelList[$key]['parent_channel'] = '';
                }
            }
        }
        $channelSetCount = $this->channelSetModel->getChannelSetCount($channelSet);
        $channelPages = pages($channelSetCount, $page, 100, $pageUrl, $array = array());

        $this->assign('channelList', $channelList);
        $this->assign('channelSetCount', $channelSetCount);
        $this->assign("channelStatus", $this->configModel['public_status']);
        $this->assign('channelPages', $channelPages);
        $this->assign('page', $page);
        $this->assign('keyword', $keyword);

        $this->getViewer()->needLayout(false);
        $this->render('channel_set_list');
    }

    public function addAction()
    {
        $dosubmit = daddslashes($this->postVar('dosubmit', ''));
        $channelAdd['channel'] = daddslashes(trim($this->postVar('channel', '')));
        $channelAdd['parent_id'] = (int)$this->postVar('parent_id', 0);
        $channelAdd['remark'] = daddslashes(trim($this->postVar('remark', '')));
        if (in_array(UID, array(1, 2, 3, 4, 5)))
        {
            $channelAdd['weight'] = (int)$this->postVar('weight', 0);
        }
        if (!empty($dosubmit))
        {
            if (empty($channelAdd['channel']))
            {
                $this->redirect('请填写渠道号!', '', 3);
                die();
            } elseif (empty($channelAdd['remark']))
            {
                $this->redirect('请填写备注!', '', 3);
                die();
            } else
            {
                preg_match('/^[a-zA-Z0-9_]{1,20}$/', $channelAdd['channel'], $result);
                if (empty($result))
                {
                    $this->redirect('渠道号不能使用特殊符号及长度不能超过20!', '', 3);
                }
            }
            $channelSet['channel'] = $channelAdd['channel'];
            if ($this->channelSetModel->getChannelSet($channelSet))
            {
                $this->redirect('渠道号已存在', '', 3);
                die();
            }

            $channelAdd['operat'] = UNAME;
            $channelAdd['status'] = 1;
            $this->channelSetModel->addChannelSet($channelAdd);
            $this->redirect('', '/admin/channel_set/', 0);
        }

        $parentChannelSet['parent_id'] = 0;
        $parentChannelSelect = $this->channelSetModel->getChannelSetList($parentChannelSet, 1, 100);
        $this->assign('parentChannelSelect', $parentChannelSelect);
        $this->assign('adminId', UID);

        $this->getViewer()->needLayout(false);
        $this->render('channel_set_add');
    }

    public function editAction()
    {
        $page = (int)$this->reqVar('page', 1);
        $dosubmit = daddslashes($this->postVar('dosubmit', ''));

        $channelId = (int)$this->reqVar('channel_id', 0);
        $channelSave['parent_id'] = (int)$this->postVar('parent_id', 0);
        $channelSave['remark'] = daddslashes($this->postVar('remark', ''));
        if (in_array(UID, array(1, 2, 3, 4, 5)))
        {
            $channelSave['weight'] = (int)$this->postVar('weight', 0);
        }
        $getChannelSet = $this->channelSetModel->getChannelSet(array("id" => $channelId));
        if (empty($getChannelSet))
        {
            $this->redirect('参数不能为空', '', 0);
            die();
        }
        if (!empty($dosubmit))
        {
            if (empty($channelSave['remark']))
            {
                $this->redirect('请填写备注!', '', 3);
                die();
            }
//          $channelSave['operat'] = UNAME;
            $channelSave['operatetime'] = date("Y-m-d H:i:s", time());
            $this->channelSetModel->saveChannelSet($channelId, $channelSave);
            $this->redirect('', '/admin/channel_set/?page=' . $page, 0);
        }
        $parentChannelSet['parent_id'] = 0;
        $parentChannelSelect = $this->channelSetModel->getChannelSetList($parentChannelSet, 1, 100);

        $this->assign('channelId', $channelId);
        $this->assign('getChannelSet', $getChannelSet);
        $this->assign('parentChannelSelect', $parentChannelSelect);
        $this->assign('page', $page);
        $this->assign('adminId', UID);

        $this->getViewer()->needLayout(false);
        $this->render('channel_set_edit');
    }

    public function openAction()
    {
        $mId = (int)$this->reqVar('id', 0);
        if ($mId > 0)
        {
            $this->channelSetModel->channelValidate($mId);
        }
        $this->redirect('', '/admin/channel_set/', 0);
    }

    public function shutAction()
    {
        $mId = (int)$this->reqVar('id', 0);
        if ($mId > 0)
        {
            $this->channelSetModel->channelDisable($mId);
        }
        $this->redirect('', '/admin/channel_set/', 0);
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

    //appmarket下的渠道更新到小黑屋渠道配置
    public function appmarketAction()
    {
        $backUrl = "/admin/channel_set/";
        $xiaoHeiSet = array();
        $channelWhere = " parent_id=4";
        $channelRe = $this->channelSetModel->query("SELECT id,channel FROM a_channel_set WHERE $channelWhere");
        if ($channelRe)
        {
            foreach ($channelRe as $key => $val)
            {
                $xiaoHeiSet[] = trim($val['channel']);
            }
        }
        if (empty($xiaoHeiSet))
        {
            $this->redirect('小黑屋渠道配置获取失败', $backUrl, 0);
            die();
        }

        $sysConfigModel = $this->loadAppModel('Sys_config');

        $xiaoHeiArr = array();
        $xiaoHeiWuRe = $sysConfigModel->where(" sys_key='FORBID_XIAOHEIWU_CHANNEL'")->find(array());
        if (!empty($xiaoHeiWuRe['sys_value']))
        {
            $xiaoHeiArr = explode(",", trim($xiaoHeiWuRe['sys_value'], ","));
        }

        $xiaoHeiMergeArr = array_merge($xiaoHeiSet, $xiaoHeiArr);
        $xiaoHeiFilterArr = array_filter($xiaoHeiMergeArr);
        $xiaoHeiUniqueArr = array_unique($xiaoHeiFilterArr); //去重
        $xiaoHeiStr = implode(",", $xiaoHeiUniqueArr);

        if ($xiaoHeiStr)
        {
            $xiaoHeiStr = "," . $xiaoHeiStr . ",";
            $isUp = $sysConfigModel->where(" sys_key='FORBID_XIAOHEIWU_CHANNEL'")->save(array("sys_value" => $xiaoHeiStr));
            if ($isUp)
            {
                $this->redirect('配置更新成功', $backUrl, 0);
                die();
            }
        }
        $this->redirect('更新失败未知错误,请联系管理员', $backUrl, 0);
    }


    public function ad_leagueAction()
    {
        $channelList = $this->channelSetModel->query("SELECT * FROM z_sys_conf WHERE sys_key in ('android_dmad', 'ios_dmad', 'ios_myad', 'ios_wpad', 'ios_ymad')");

        foreach ($channelList as &$val)
        {
            $val['discription'] = explode(" ", $val['discription'])[0];
        }
        $this->assign('channelList', $channelList);
        $this->assign('adminId', UID);

        $this->getViewer()->needLayout(false);
        $this->render('channel_ad_league');
    }


    public function ad_league_shutAction()
    {
        $sys_key = $this->reqVar('sys_key', 0);

        if (!empty($sys_key))
        {
            $this->channelSetModel->execute("UPDATE z_sys_conf SET sys_value = 0 WHERE sys_key = '$sys_key'");
        }
        $this->oplog("shut $sys_key");

        $this->redirect('', '/admin/channel_set/ad_league', 0);
    }

    public function ad_league_openAction()
    {
        $sys_key = $this->reqVar('sys_key', 0);
        if (!empty($sys_key))
        {
            $this->channelSetModel->execute("UPDATE z_sys_conf SET sys_value = 1 WHERE sys_key = '$sys_key'");
        }
        $this->oplog("open $sys_key");

        $this->redirect('', '/admin/channel_set/ad_league', 0);
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

}
