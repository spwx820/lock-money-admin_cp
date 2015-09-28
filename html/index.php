<?php
/**
 * 程序入口
 *
 * @category   Leb
 * @package    Leb_Bootstrap
 * @copyright
 * @license
 */
require_once('../config/init.php');
require_once(_FRAMEWORK_.'/loader.php');
Leb_Loader::setAutoLoad();

// 中心控制 请求->路由->过滤->分发->响应
$controller = Leb_Controller::getInstance();
$controller->run();
