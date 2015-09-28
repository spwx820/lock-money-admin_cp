<?php
return array(
    'hb_action_type'=>array(
        0=>'应用下载',
        1=>'划屏加分',
        2=>'邀请好友',
        3=>'其他收益',
        4=>'任务收益',
        5=>'好友分享',
        6=>'兑换',
        7=>'退款'
    ),
    'pk_os' => array(
        '1' => 'android',
        '2' => 'ios',
    ),
    'admin_status' => array(
        '0' => '正常',
        '1' => '停用',
        '2' => '<span class="red">锁定</span>'
    ),
    'user_status' => array(
        '0' => '非正常',
        '1' => '有效',
        '2' => '失效'
    ),
    'pay_status' => array(
        '-1' => '作弊',
        '0'  => '非法请求',
        '1' => '待审核',
        '2'  => '审核通过',
        '3'  => '处理完成',
        '4'  => '处理出错',
        '5'  => '充值中',
        '6'  => '已退款',
        '7'  => '待退款',
        '8'  => '暂不处理'
    ),
    'pay_type' => array(
        '1' => '充值卡',
        '2'  => 'Q币',
        '3'  => '支付宝提现',
        '4'  => 'wifi上网券',
        '5'  => 'Q币小额兑换',

        '10'  => '兑吧',
    ),
    'smspw_status' => array(
        '0' => '失败',
        '1' => '成功'
    ),
    'ad_type' => array(
        'detail' => array('name'=>'积分墙','action'=>'detail','protocol'=>'http'),
        'preferential_detail' => array('name'=>'积分墙特惠','action'=>'preferential_detail','protocol'=>'http'),
        'lockdetail' => array('name'=>'应用打开','action'=>'lockdetail','protocol'=>'http'),
        'systemdetail' => array('name'=>'浏览器打开','action'=>'systemdetail','protocol'=>'http'),
        'list' => array('name'=>'壁纸','action'=>'list','protocol'=>'intent'),
        'share' => array('name'=>'分享','action'=>'share','protocol'=>'intent'),
        'main' => array('name'=>'主页','action'=>'main','protocol'=>'intent'),
        'task' => array('name'=>'我的任务页','action'=>'task','protocol'=>'intent'),
        'deeplist' => array('name'=>'深度试玩页','action'=>'deeplist','protocol'=>'intent'),
        'exchange' => array('name'=>'兑换页','action'=>'exchange','protocol'=>'intent'),
        'my' => array('name'=>'我的页','action'=>'my','protocol'=>'intent'),
        'myinfo' => array('name'=>'我的消息页','action'=>'myinfo','protocol'=>'intent'),
        'detailinfo' => array('name'=>'最新消息页','action'=>'detailinfo','protocol'=>'intent'),
        'external' => array('name'=>'外部应用','action'=>'external','protocol'=>'intent'),
        'uninstall' => array('name'=>'卸载应用','action'=>'uninstall','protocol'=>'intent'),
        'web' => array('name'=>'WEB页','action'=>'web','protocol'=>'intent'),
        'cooperatelist' => array('name'=>'实惠','action'=>'cooperatelist','protocol'=>'intent')
    ),
    'ad_detail' => array(
        'detail' => array('name'=>'积分墙','action'=>'detail','protocol'=>'http'),
        'preferential_detail' => array('name'=>'积分墙特惠','action'=>'preferential_detail','protocol'=>'http'),
    ),
    'ad_click_http' => array(
        'lockdetail' => array('name'=>'应用打开','action'=>'lockdetail','protocol'=>'http'),
        'systemdetail' => array('name'=>'浏览器打开','action'=>'systemdetail','protocol'=>'http')
    ),
    'ad_intent' => array(
        'list' => array('name'=>'壁纸','action'=>'list','protocol'=>'intent'),
        'share' => array('name'=>'分享','action'=>'share','protocol'=>'intent'),
        'main' => array('name'=>'主页','action'=>'main','protocol'=>'intent'),
        'task' => array('name'=>'我的任务页','action'=>'task','protocol'=>'intent'),
        'deeplist' => array('name'=>'深度试玩页','action'=>'deeplist','protocol'=>'intent'),
        'exchange' => array('name'=>'兑换页','action'=>'exchange','protocol'=>'intent'),
        'my' => array('name'=>'我的页','action'=>'my','protocol'=>'intent'),
        'myinfo' => array('name'=>'我的消息页','action'=>'myinfo','protocol'=>'intent'),
        'detailinfo' => array('name'=>'最新消息页','action'=>'detailinfo','protocol'=>'intent'),
        'external' => array('name'=>'外部应用','action'=>'external','protocol'=>'intent'),
        'uninstall' => array('name'=>'卸载应用','action'=>'uninstall','protocol'=>'intent'),
        'web' => array('name'=>'WEB页','action'=>'web','protocol'=>'web'),
        'cooperatelist' => array('name'=>'实惠','action'=>'cooperatelist','protocol'=>'intent')
    ),
    'ad_widget_type' => array(
        'share' => array('name'=>'分享','action'=>'share','protocol'=>'intent'),
        'index' => array('name'=>'首页','action'=>'index','protocol'=>'intent'),
        'integral' => array('name'=>'应用列表页','action'=>'integral','protocol'=>'intent'),
        'integral_detail' => array('name'=>'应用详情页','action'=>'integral_detail','protocol'=>'intent'),
        'integral_deep' => array('name'=>'深度试玩页','action'=>'integral_deep','protocol'=>'intent'),
        'task' => array('name'=>'我的任务页','action'=>'task','protocol'=>'intent'),
        'exchange' => array('name'=>'兑换页','action'=>'exchange','protocol'=>'intent'),
        'my' => array('name'=>'我的页','action'=>'my','protocol'=>'intent'),
        'my_message' => array('name'=>'我的消息页','action'=>'my_message','protocol'=>'intent'),
        'message_detail' => array('name'=>'消息详情页','action'=>'message_detail','protocol'=>'intent'),
        'external' => array('name'=>'外部应用','action'=>'external','protocol'=>'intent'),
        'external_url' => array('name'=>'外部URL','action'=>'external_url','protocol'=>'intent')
    ),
    'ad_top' => array(
        '0' => '否',
        '1' => '<span class="red">是</span>'
    ),
    'ad_status' => array(
        '0' => '无效',
        '1' => '<span class="green">有效</span>'
    ),
    'ad_z_status' => array(
        '0' => '未处理',
        '1' => '已上架',
        '2' => '已下架'
    ),
    'hongbao_status' => array(
        '0' => '未发送',
        '1' => '<span class="red">发送失败</span>',
        '2' => '<span class="green">成功</span>',
        '3' => '<span class="red">失败</span>'
    ),
    'channel_set_status' => array(
        '0' => '失效',
        '1' => '<span class="green">有效</span>'
    ),
    'message_os' => array(
        '0' => 'android/ios',
        '1' => 'android',
        '2' => 'iOS',
    ),
    'message_status' => array(
        '0' => '待审核',
        '1' => '已审核',
        '2' => '<span class="green">发送成功</span>',
        '3' => '<span class="red">发送失败</span>',
        '4' => '处理成功'
    ),
    'message_pri_status' => array(
        '-1' => '<span class="red">已删除</span>',
        '0' => '未发送',
        '1' => '<span class="green">发送成功</span>',
        '2' => '<span class="red">发送失败</span>'
    ),
    'message_notify' => array(
        '0' => '否',
        '1' => '<span class="green">是</span>'
    ),
    'message_type' => array(
        '0' => '私有',
        '1' => '公共'
    ),
    'message_detail_status' => array(
        '0' => '未发送',
        '1' => '<span class="green">发送成功</span>',
        '2' => '<span class="red">发送失败</span>'
    ),

    'device_modify_status' => array(
        '0' => '失败',
        '1' => '<span class="green">成功</span>'
    ),
    'menu_status' => array(
        '0' => '失效',
        '1' => '<span class="green">有效</span>'
    ),
    'menu_conceal' => array(
        '0' => '否',
        '1' => '是'
    ),
    'public_status' => array(
        '0' => '失效',
        '1' => '<span class="green">有效</span>'
    ),
    'public_radio_force' => array(
        '0' => '否',
        '1' => '<span class="red">是</span>'
    ),
    'public_radio' => array(
        '0' => '否',
        '1' => '<span class="green">是</span>'
    ),
    'public_os' => array(
        '0' => 'android/iOS',
        '1' => 'android',
        '2' => 'iOS',
    ),
    'public_type' => array(
        '0' => '私有',
        '1' => '公共'
    ),
    'notification_type' => array(
        'share' => array('name'=>'分享','action'=>'share','protocol'=>'intent'),
        'index' => array('name'=>'首页','action'=>'index','protocol'=>'intent'),
        'integral' => array('name'=>'应用列表页','action'=>'integral','protocol'=>'intent'),
        'integral_detail' => array('name'=>'应用详情页','action'=>'integral_detail','protocol'=>'intent'),
        'integral_deep' => array('name'=>'深度试玩页','action'=>'integral_deep','protocol'=>'intent'),
        'task' => array('name'=>'我的任务页','action'=>'task','protocol'=>'intent'),
        'exchange' => array('name'=>'兑换页','action'=>'exchange','protocol'=>'intent'),
        'my' => array('name'=>'我的页','action'=>'my','protocol'=>'intent'),
        'my_message' => array('name'=>'我的消息页','action'=>'my_message','protocol'=>'intent'),
        'message_detail' => array('name'=>'消息详情页','action'=>'message_detail','protocol'=>'intent'),
        'external' => array('name'=>'外部应用','action'=>'external','protocol'=>'intent'),
        'external_url' => array('name'=>'外部URL','action'=>'external_url','protocol'=>'intent'),
        'cooperatelist' => array('name'=>'实惠','action'=>'cooperatelist','protocol'=>'intent'),
        'uninstall' => array('name'=>'卸载应用','action'=>'uninstall','protocol'=>'intent')
    ),
    'notification_status' => array(
        '0' => '待审核',
        '1' => '已审核',
        '2' => '处理成功',
        '3' => '发送成功',
        '4' => '发送失败'
    ),
    'exchange_select' => array(
        '1' => '用户ID',
        '2' => '兑换ID',
        '3' => '手机号',
        '4' => '物品ID',
        '5' => '设备ID',
        '6' => '交易IP',
        '7' => '操作人',
    ),
    'is_images' => array(
        '0' => '否',
        '1' => '是'
    ),
    'pk_status' => array(
        '0' => '待审核',
        '1' => '<span class="green">已审核</span>'
    ),
    'pk_scale' => array(
        '1' => '10%',
        '2' => '20%',
        '3' => '30%',
        '4' => '40%',
        '5' => '50%',
        '6' => '60%',
        '7' => '70%',
        '8' => '80%',
        '9' => '90%',
        '10' => '100%'
    ),
    'version_status' => array(
        '0' => '待审核',
        '1' => '<span class="green">已审核</span>'
),
);
