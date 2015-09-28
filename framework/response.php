<?php
/**
 * 响应类
 *
 *
 * @category   Leb
 * @package    Leb_Bootstrap
 * @author     $Id: response.php 37469 2013-03-11 05:48:04Z ziyuan $
 * @copyright
 * @license
 */
class Leb_Response
{

    /**
     * 重定向
     * @param string   $msg     显示消息
     * @param string   $url     to Go
     * @param int      $time    页面停留时间单位秒，0：页面不跳转
     *
     */
    static public function redirect($msg, $url, $time, $type='success')
    {
        $viewer = Leb_View::getInstance();
        $viewer->setTemplate('msg');

        $stay_time = $time * 1000;
        if (0 == $stay_time) {
            $viewer->direct_js = '';
        } else {
            if ('' == $url) {
                $viewer->direct_js = "setTimeout(\"history.go(-1);\",{$stay_time})";;
            } else {
                $viewer->direct_js = "setTimeout(\"window.location.href ='{$url}';\",{$stay_time})";
            }
        }

        $url  = empty($url) ? 'Javascript:window.history.back();' : $url;
        $class= $html = '';
        switch ($type)
        {
        case 'success':
            $class = 'fl zqicon';
            break;
        case 'error':
            $class = 'fl tsicon';
            break;
        }

        if($class)
        {
            $html =<<<EOF
<div class="{$class}"></div>
<div class="fl ml10">
    <p class="f14 col_5">{$msg}</p>
    <p class="f14 col_1 fb mt5"><a href="{$url}">[马上跳转]</a></p>
</div>
EOF;
        }
        $viewer->needLayout(false);
        $viewer->title = '信息提示页';
        $viewer->message = $html;
        $viewer->run();
        die();
    }
}
