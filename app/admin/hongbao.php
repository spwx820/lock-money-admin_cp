<?php
/**
 * 后台群发红包
 *
 * @category   Leb
 * @package    Leb_Action
 * @author     lihui
 * @version    $Id: hongbao.php 2014-09-15 9:58:00 lihui
 * @copyright (c) 2014 dianjoy.com
 * @license
 */
class hongbaoController extends Application
{
    private $configModel;
    private $hongbaoSendModel;
    private $hongbaoModel;
    private $userModel;
    private $userClient;
    private $transport;

    public function execute($plugins)
    {
        $this->configModel = C('global.php');
        $this->hongbaoSendModel = $this->loadAppModel('Apisend_hongbao');
        $this->hongbaoModel = $this->loadModel('Hongbao');
        $this->userModel = $this->loadAppModel('User');

        $GLOBALS['THRIFT_ROOT'] = '../thriftlib';
        require_once($GLOBALS['THRIFT_ROOT'] . '/Thrift.php');
        require_once($GLOBALS['THRIFT_ROOT'] . '/transport/TSocket.php');
        require_once($GLOBALS['THRIFT_ROOT'] . '/transport/TBufferedTransport.php');
        require_once($GLOBALS['THRIFT_ROOT'] . '/protocol/TBinaryProtocol.php');
        require_once($GLOBALS['THRIFT_ROOT'] . '/packages/user_service/UserService.php');
        require_once($GLOBALS['THRIFT_ROOT'] . '/packages/user_service/user_service_types.php');

        //包含thrift客户端库文件
        $socket = new TSocket(_PUSH_ANDROID_TSOCKET_USER, 9091);
        $this->transport = new TBufferedTransport($socket, 1024, 1024);
        $protocol = new TBinaryProtocol($this->transport);
        $this->userClient = new UserServiceClient($protocol);
    }

    public function indexAction()
    {
        $keyword = daddslashes($this->reqVar('keyword', ''));

        $startTime = daddslashes($this->reqVar('start_time', ''));
        $endTime = daddslashes($this->reqVar('end_time', ''));
        $endTime = date("Y-m-d", strtotime($endTime . " +1 days"));

        $page = (int)$this->reqVar('page', 1);

        $pageUrl = "/admin/hongbao/";
        $hongbaoSet = array();
        if (!empty($keyword))
        {
            $hongbaoSet['uid'] = $keyword;
        }

        if (!empty($startTime))
        {
            $hongbaoSet['condition'] = " AND createtime >='$startTime 00:00:00'";
            $pageUrl .= "?start_time=$startTime";
        }
        if (!empty($endTime))
        {
            $hongbaoSet['condition'] .= " AND createtime <='$endTime 23:59:59'";
            $pageUrl .= !empty($startTime) ? '&' : '?';
            $pageUrl .= "end_time=$endTime";
        }
        $limit_start = ($page - 1) * 60;
        if (!empty($keyword))
        {
            $hongbaoList = $this->hongbaoModel->query("SELECT * FROM a_hongbao_send WHERE createtime > '$startTime' AND createtime < '$endTime' AND uid = $keyword ORDER by id desc limit $limit_start , 20");
        } else
        {
            $hongbaoList = $this->hongbaoModel->query("SELECT * FROM a_hongbao_send WHERE createtime > '$startTime' AND createtime < '$endTime'  ORDER by id desc limit $limit_start , 20");

        }
        $id_end = $hongbaoList[0]["id"];
        $id_start = $hongbaoList[count($hongbaoList) - 1]["id"];

        $sum_succ = $this->hongbaoModel->query("SELECT SUM(score) as s FROM a_hongbao_send WHERE id >= '$id_start' AND id <= '$id_end' AND createtime > '$startTime' AND createtime < '$endTime' AND status = 2 ")[0]['s'];
        $sum_fail = $this->hongbaoModel->query("SELECT SUM(score) as s FROM a_hongbao_send WHERE id >= '$id_start' AND id <= '$id_end' AND createtime > '$startTime' AND createtime < '$endTime' AND (status = 3 OR status = 1) ")[0]['s'];

        $sum_cur = "成功 : " . $sum_succ . " (分), 失败 : " . $sum_fail . " (分)";

        $sum_succ_ = $this->hongbaoModel->query("SELECT SUM(score) as s FROM a_hongbao_send WHERE createtime > '$startTime' AND createtime < '$endTime' AND status = 2")[0]['s'];
        $sum_fail_ = $this->hongbaoModel->query("SELECT SUM(score) as s FROM a_hongbao_send WHERE createtime > '$startTime' AND createtime < '$endTime' AND (status = 3 OR status = 1)")[0]['s'];

        $sum_all = "成功 : " . $sum_succ_ . " (分), 失败 : " . $sum_fail_ . " (分)";

        $this->assign('sum_cur', $sum_cur);
        $this->assign('sum_all', $sum_all);

        $hongbaoCount = $this->hongbaoModel->getHongbaoCount($hongbaoSet);
        $hongbaoPages = pages($hongbaoCount, $page, 20, $pageUrl, array());

        $this->assign('keyword', $keyword);
        $this->assign('startTime', $startTime);
        $this->assign('endTime', $endTime);
        $this->assign('hongbaoList', $hongbaoList);
        $this->assign("publicRadio", $this->configModel['public_radio']);
        $this->assign("honbaoStatus", $this->configModel['hongbao_status']);
        $this->assign('hongbaoPages', $hongbaoPages);
        $this->assign('adminId', UID);

        $this->getViewer()->needLayout(false);
        $this->render('hongbao_list');
    }

    public function testAction()
    {
        $this->assign('adminId', UID);
        $this->getViewer()->needLayout(false);
        $this->render('index');
    }

    public function addAction()
    {
        $dosubmit = daddslashes($this->postVar('dosubmit', ''));
        $uidBatch = daddslashes($this->postVar('uid_batch', ''));
        $content = daddslashes($this->postVar('content', ''));
        $uidScore = (int)$this->postVar('uid_score', 0);
        $shareMsg = daddslashes($this->postVar('share_msg', ''));
        $allowShareMsg = (int)$this->postVar('allow_share_msg', 1);
        $isActivity = (int)$this->postVar('is_activity', 0);
        if ($dosubmit)
        {
            if (empty($uidBatch))
            {
                $this->redirect('请填写用户ID!', '', 5);
                die();
            } elseif (empty($content))
            {
                $this->redirect('请填写内容!', '', 5);
                die();
            } elseif (empty($uidScore))
            {
                $this->redirect('请填写金额!', '', 5);
                die();
            }
            $uidBatch = str_replace(array("\n", "\r", "\t", '，', ' '), array(',', ',', ',', ',', ''), $uidBatch);
            //$content  =  str_replace(array(' ') ,array('') ,$content);

            $uidArr = array_filter(explode(",", trim($uidBatch, ",")));
            $uidArr = array_unique($uidArr); //去重
            if (empty($uidArr) || 200 < count($uidArr))
            {
                $this->redirect('用户ID不存在或数量超出范围!', '', 5);
                die();
            }

            //Thrift连接
            $this->transport->open();
            $tp = $this->transport->isOpen();
            if (!$tp)
            {
                $this->redirect('获取用户信息服务无法连接!', '', 5);
            }

            //发红包参数对象初始化
            $scoreAddObj = new scoreAddObj();
            $scoreTypeObj = new Scoretype();

            $uidIds = array();
            $hongbaoAdd['score'] = $uidScore;
            $hongbaoAdd['content'] = $content;
            $hongbaoAdd['share_msg'] = $shareMsg;
            $hongbaoAdd['allow_share_msg'] = $allowShareMsg;
            $hongbaoAdd['is_activity'] = $isActivity;
            foreach ($uidArr as $val)
            {
                if (!is_numeric($val))
                    continue;

                $hongbaoAdd['uid'] = $val;
                $hongbaoAdd['creater'] = UNAME;
                $backId = $this->hongbaoModel->addHongbao($hongbaoAdd);
                if (empty($backId))
                    continue;

                //获取用户信息
                $userRe = $this->userClient->getUserInfoByUid($val, '', '', 0);
                if (empty($userRe) || empty($userRe->device_id))
                {
                    $this->hongbaoModel->hongbaoFail($backId, "发红包验证失败");
                    continue;
                }

                $this->hongbaoModel->hongbaoSendSucceed($backId);

                //发送红包
                $scoreAddObj->uid = $val;
                $scoreAddObj->device_id = $userRe->device_id;
//                if(1 == $hongbaoAdd['is_activity']){
//                    $scoreAddObj->action_type =$scoreTypeObj::_ACTION_TYPE_ACTIVE;
//                }else{
//                    $scoreAddObj->action_type = $scoreTypeObj::_ACTION_TYPE_OTHER;
//                }
                $scoreAddObj->action_type = $scoreTypeObj::_ACTION_TYPE_OTHER;
                $scoreAddObj->trade_type = 5;
                $scoreAddObj->currency = $uidScore;
                $scoreAddObj->pack_name = 'admin';
                $scoreAddObj->ad_name = '特殊红包:' . UID;
                $scoreAddObj->order_id = $backId;
                $scoreAddObj->time_stamp = 120;
                $scoreAddObj->client_ip = '218.247.145.70';
                $scoreAddObj->app_id = 0;
                $isScoreAdd = $this->userClient->addScore($scoreAddObj);

                if ($isScoreAdd)
                {
                    $uidIds[$val] = "score_" . $backId;
                    $this->hongbaoModel->hongbaoSucceed($backId);
                } else
                {
                    $this->hongbaoModel->hongbaoFail($backId, "发红包失败");
                }
                usleep(100);
            }

            //发送消息时间间隔60秒
            $this->sendMessage($uidIds, $uidScore, $content, $shareMsg, $allowShareMsg);
            $this->redirect('', '/admin/hongbao/', 0);
        }

        $this->getViewer()->needLayout(false);
        $this->render('hongbao_add');
    }

    private function sendMessage($uidIds, $uidScore, $content, $shareMsg, $allowShareMsg)
    {
        if (empty($uidIds) || empty($uidScore) || empty($content))
            return false;

        if (empty($allowShareMsg))
        {
            $shareMsg = "参加红包锁屏的活动，竟然中了" . $uidScore / 100.00 . "元！";
        }

        //发送消息
        $sendData['uids_orderids'] = $uidIds;
        $sendData['info_title'] = '中奖通知';
        $sendData['content'] = $content;
        $sendData['share_msg'] = $shareMsg;
        $sendData['info_notify'] = 1;
        $sendData['end_time'] = '';
        $apiData = urlencode(json_encode($sendData));

//        file_get_contents(_API_URL_."/admin_user_send_msg.do?data={$apiData}");
        curl_get(_API_URL_ . "/admin_user_send_msg.do?data={$apiData}");

        return true;
    }


}