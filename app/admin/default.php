<?php
/**
 * 默认操作
 *
 * @category   Leb
 * @package    Leb_Action
 * @author     lihui
 * @version    $Id: default.php 2014-09-03 9:58:00 lihui
 * @copyright (c) 2014 dianjoy.com
 * @license
 */
class defaultController extends Application
{
    private $exchangeModel;
    private $inviteHoldLogModel;
    private $inviteHoldExceptionModel;
    private $userModel;
    private $configModel;
    private $operateLogModel;
    private $userClient;
    private $transport;


    public function execute($plugins)
    {
        $header = get_page_header();
        $this->assign('header', $header);
        $side_bar_menu = $this->get_side_bar_menu();
        $this->assign('side_bar_menu', $side_bar_menu);


        $this->exchangeModel = $this->loadAppModel('Exchange');
        $this->inviteHoldLogModel = $this->loadModel('Invite_hold_log');
        $this->inviteHoldExceptionModel = $this->loadModel('Invite_hold_exception');
        $this->userModel = $this->loadAppModel('User');
        $this->configModel = C('global.php');
        $this->operateLogModel = $this->loadModel('Operate_log',array(),'admin');

        $GLOBALS['THRIFT_ROOT'] = '../thriftlib';
        require_once( $GLOBALS['THRIFT_ROOT'] . '/Thrift.php' );
        require_once( $GLOBALS['THRIFT_ROOT'] . '/transport/TSocket.php' );
        require_once( $GLOBALS['THRIFT_ROOT'] . '/transport/TBufferedTransport.php' );
        require_once( $GLOBALS['THRIFT_ROOT'] . '/protocol/TBinaryProtocol.php' );
        require_once( $GLOBALS['THRIFT_ROOT'] . '/packages/user_service/UserService.php' );

        //包含thrift客户端库文件
        $socket = new TSocket(_PUSH_ANDROID_TSOCKET_USER, 9091);
        $this->transport = new TBufferedTransport($socket, 1024, 1024);
        $protocol = new TBinaryProtocol($this->transport);
        $this->userClient = new UserServiceClient($protocol);
    }

    public function indexAction()
    {
        $search = daddslashes($this->postVar('search', ''));
        $keyword = daddslashes($this->postVar('keyword', ''));
        $date_range = daddslashes($this->postVar('date_range', ''));
        $actionPayType = (int)$this->postVar('action_pay_type', 0);
        $actionPayStatus = daddslashes($this->postVar('action_pay_status', ''));
        $actionType = (int)$this->postVar('action_type', 0);

        $page = (int)$this->reqVar('page', 1);

        $startTime = substr($date_range, 0, 10);
        $endTime = substr($date_range, 11);



        $pageUrl = "/admin/default/";
        if (!empty($keyword))
        {
            if (1 == $actionType)
            {
                $exchangeSet['uid'] = $keyword;
            } elseif (2 == $actionType)
            {
                $exchangeSet['id'] = $keyword;
            } elseif (3 == $actionType)
            {
                //Thrift连接
                $this->transport->open();
                $tp = $this->transport->isOpen();
                if (!$tp)
                {
                    $this->redirect('获取用户信息服务无法连接!', '', 5);
                }
                //获取用户信息
                $userRe = $this->userClient->getUserInfoAllUser($keyword, '', '', 0);
                if (!empty($userRe->uid))
                {
                    $exchangeSet['uid'] = $userRe->uid;
                }
            } elseif (4 == $actionType)
            {
                $exchangeSet['present_id'] = $keyword;
            } elseif (5 == $actionType)
            {
                $exchangeSet['device_id'] = $keyword;
            } elseif (6 == $actionType)
            {
                $exchangeSet['ip'] = $keyword;
            } elseif (7 == $actionType)
            {
                $exchangeSet['admin'] = $keyword;
            }
            $pageUrl .= "?action_type=$actionType&keyword=$keyword";
        }

        if (!empty($startTime))
        {
            $exchangeSet['start_time'] = $startTime;
            if (!empty($keyword))
            {
                $pageUrl .= "&start_time=$startTime";
            } else
            {
                $pageUrl .= "?start_time=$startTime";
            }
        }
        if (!empty($endTime))
        {
            $exchangeSet['end_time'] = $endTime;
            if (!empty($keyword) || !empty($startTime))
            {
                $pageUrl .= "&end_time=$endTime";
            } else
            {
                $pageUrl .= "?end_time=$endTime";
            }
        }
        if (!empty($actionPayType))
        {
            $exchangeSet['ptype'] = $actionPayType;
            if (!empty($keyword) || !empty($startTime) || !empty($endTime) || !empty($actionPayType))
            {
                $pageUrl .= "&action_pay_type=$actionPayType";
            } else
            {
                $pageUrl .= "?action_pay_type=$actionPayType";
            }
        }
        if (!empty($actionPayStatus))
        {
            $exchangeSet['pay_status'] = $actionPayStatus;
            if (!empty($keyword) || !empty($startTime) || !empty($endTime) || !empty($actionPayType))
            {
                $pageUrl .= "&action_pay_status=$actionPayStatus";
            } else
            {
                $pageUrl .= "?action_pay_status=$actionPayStatus";
            }
        }

        $lastHour = date("Y-m-d H:i:s", time() - 3600);
        $exchangeSet['condition'] = " AND ctime <= '$lastHour' ";
        $exchangeList = $this->exchangeModel->getExchangeList($exchangeSet, $page, 20);
        $exchangeCount = $this->exchangeModel->getExchangeCount($exchangeSet);



        $m = C('global.php');


        $date_range = get_date_range_picker("date_range", $date_range);
        $this->assign('date_range', $date_range);


        $action_pay_type_select = get_select_form_group("action_pay_type", "兑换类型", $m['pay_type'], $actionType);
        $this->assign('action_pay_type_select', $action_pay_type_select);

        $action_pay_status_select = get_select_form_group("action_pay_status", "兑换状态", $m['pay_status'], $actionPayStatus);
        $this->assign('action_pay_status_select', $action_pay_status_select);

        $action_type_select = get_select_form_group("action_type", "类型", $m['exchange_select'], $actionType,  100);
        $this->assign('action_type_select', $action_type_select);

        $keyword_box = get_input_box("keyword", $value = "");
        $this->assign('keyword_box', $keyword_box);



        $table_header = array("id" => "兑换ID", "present_id" => "物品ID", "uid" => "用户ID", "device_id" => "设备号", "pay_content" => "充值信息", "paychannel" => "充值通道",
            "pay" => "实际支付金额", "ptype" => "兑换类型", "admin" => "操作人", "update_time" => "更新时间", "ctime" => "充值时间", "option" => "操作");

        define(UID, 1);
        foreach($exchangeList as &$var)
        {
            if (in_array(UID, array(1,2,3,4,5,6,30)))
            {
                if (3 == $var["pay_status"] or 2 == $var["pay_status"])
                {
                    $var["option"] = '<button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#refund_Modal" id="' . $var["id"] . '"  onmouseover="set_action(this)">退款</button>';
                }
            }

            $var["option"] .= '<a href="/admin/default/detail?id=' . $var["id"] . '&listpage='. $page .'&pay_type='. $actionPayType .'&pay_status='. $actionPayStatus .'" title="$item.remark">[详细]</a>';

//          set color : <span style="color: #ccc">test</span>

        }

        $table = get_table($table_header, $exchangeList);
        $this->assign('table', $table);

        $exchangePages = pages_new($exchangeCount, $page, 20, $pageUrl);
        $this->assign('exchangePages', $exchangePages);

        $this->getViewer()->needLayout(false);
        $this->render('default');

    }

    public function detailAction()
    {

        $this->getViewer()->needLayout(false);
        $this->render('default_layout');
    }

    public function addAction()
    {
        $m = C('global.php');
        $action_type_select = get_select_("action_type", "类型", $m['exchange_select']);
        $this->assign('action_type_select', $action_type_select);

        $start_time = get_date_time_raw("start_time");
        $this->assign('start_time', $start_time);


        $end_time = get_date_time_raw("end_time");
        $this->assign('end_time', $end_time);

        $this->getViewer()->needLayout(false);
        $this->render('default_form');
    }


    public function refundAction()
    {
        define(UID, 1);

        if (!in_array(UID, array(1, 2, 3, 4, 5, 6, 29, 30)))
        {
            $this->alert("无该操作权限", "/admin/default/");

        }
        $payId = (int)$this->reqVar('pay_id', 0);
        $dosubmit = daddslashes($this->postVar('dosubmit', ''));
        $remark = daddslashes($this->postVar('remark', ''));


        $setTimeOut = 0;

        //支付宝退款
        $exchangeRe = $this->exchangeModel->getExchange(array('id' => $payId));
        if (!empty($exchangeRe['id']) && !empty($exchangeRe['uid']))
        {
            if (!empty($dosubmit))
            {
                if (empty($remark) or strlen($remark) <= 15)
                {
                    $this->alert("说明不能少于15个字", "/admin/default/");

                    die();
                }
                //退款
                $this->exchangeModel->artificialRefund($exchangeRe['id'], $remark, UNAME);
                $setTimeOut = 1;

                //操作记录
                $opSet['pay_id'] = $payId;
                $this->oplog($opSet);

                $this->alert("退款成功", "/admin/default/");

            }
        }
        $this->getViewer()->needLayout(false);
        $this->render('default');
    }


    public function expireAction()
    {
        die("已过期,请重新登录!");
    }

}