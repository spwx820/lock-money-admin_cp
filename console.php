<?php
/**
 * 程序入口(本地请求)，后台定时任务使用
 *
 * @category   Leb
 * @package    Leb_Bootstrap
 * @copyright
 * @license
 * php console.php index default admin
 */
require_once("config/init_console.php");
require_once('framework/loader.php');

Leb_Loader::setAutoload();
$controller = Leb_Controller::getInstance(true);
$controller->run();
