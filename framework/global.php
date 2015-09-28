<?php
/**
 * 框架全局配置文件
 *
 * @category   Leb
 * @package
 * @version    $Id: global.php 4501 2012-12-10 08:33:19Z ziyuan $
 * @copyright
 * @license
 */

//XHProf性能分析，默认关闭
//注意开启前请先安装xhprof扩展
defined('XHPROF_ANALYSIS')  or define('XHPROF_ANALYSIS', false);

//开启PHP代码性能分析
if(XHPROF_ANALYSIS)
{
    xhprof_enable(
        XHPROF_FLAGS_CPU            //记录CPU时间
        + XHPROF_FLAGS_MEMORY       //记录内存使用
        + XHPROF_FLAGS_NO_BUILTINS  //忽略内建函数，如：strlen,strpos
        ,
        array('ignored_functions'=>array('')
        )
    );
}

defined('_DS_') or define('_DS_', DIRECTORY_SEPARATOR);
defined('_PS_') or define('_PS_', PATH_SEPARATOR);
defined('_DIR_SEPARATOR_') or define('_DIR_SEPARATOR_', _DS_);

//输出log到http头信息中，默认关闭
defined('APP_TRACE')    or define('APP_TRACE',  false);

//是否运行cli模式
defined('_CLI_')        or define('_CLI_',      empty($_SERVER['REMOTE_ADDR']));

//是否打开调试模式，默认关闭
defined('_DEBUG_')      or define('_DEBUG_',    _CLI_ );
defined('_DEV_')        or define('_DEV_',      _CLI_ );

//保存异常日志文件，默认不保存
defined('_RUNTIME_')    or define('_RUNTIME_',  '');

defined('_ROOT_')       or define('_ROOT_',     dirname(dirname(__FILE__))._DS_);
defined('_WWW_')        or define('_WWW_' ,     _ROOT_ );
defined('_FRAMEWORK_')  or define('_FRAMEWORK_',dirname(__FILE__)._DS_);
defined('_CONSOLE_')    or define('_CONSOLE_',  _FRAMEWORK_.'console'._DS_);
defined('_APP_')        or define('_APP_',      _ROOT_.'app'._DS_);
defined('_PLUGIN_')     or define('_PLUGIN_',   _ROOT_.'plugin'._DS_);
defined('_CONFIG_')     or define('_CONFIG_',   _ROOT_.'config'._DS_);
defined('_CMD_')        or define('_CMD_',      _ROOT_.'command'._DS_);
defined('_WEB_')        or define('_WEB_',      _FRAMEWORK_.'web'._DS_);
defined('_TOKEN_ON_')   or define('_TOKEN_ON_', false);
defined('_SESSION_AUTO_START_') or define('_SESSION_AUTO_START_', false);

defined('_DEF_APP_')        or define('_DEF_APP_',          'default');
defined('_DEF_CONTROLLER_') or define('_DEF_CONTROLLER_',   'default');
defined('_DEF_ACTION_')     or define('_DEF_ACTION_',       'index');

//Ajax请求常量标记
defined('_IS_AJAX_')        or define('_IS_AJAX_', array_key_exists('HTTP_X_REQUESTED_WITH', $_SERVER));

//快表和缓存默认使用md5值作为主键
defined('DATA_KEY_MD5') or define('DATA_KEY_MD5', true);

//快表数据存储格式，默认用JSON
defined('DATA_VALFMT_JSON') or define('DATA_VALFMT_JSON', true);

//加载框架函数
include_once(dirname(__FILE__)._DS_.'functions.php');

if(_CLI_)
{
    set_include_path(
        _FRAMEWORK_ . _PS_
        . _ROOT_ . _PS_
        . _APP_ . _PS_
        . _PLUGIN_ . _PS_
        . _CONSOLE_ . _PS_
        . _CMD_ . _PS_
        . get_include_path());
}
else
{
    set_include_path(
        _FRAMEWORK_ . _PS_
        . _ROOT_ . _PS_
        . _APP_ . _PS_
        . _PLUGIN_ . _PS_
        . _WEB_ . _PS_
        . get_include_path());
}

if(_DEV_)
{
    @!ini_get('display_errors') && @ini_set('display_errors', 1); 
    @ini_set('error_reporting', E_ALL | E_STRICT);
}
else
{
    @ini_set('display_errors', Off);
    @ini_set('error_reporting', 0);
}

if(!_CLI_ && (_SESSION_AUTO_START_ || _TOKEN_ON_))
{
    if(isset($_POST['PHPSESSID']))
    {
        session_id($_POST['PHPSESSID']);
    }
    @session_start();
}

if(function_exists('date_default_timezone_set'))
{
    @date_default_timezone_set('Asia/Shanghai');
}

if (!_CLI_) {
    ob_start();
    header('Content-Type:text/html;charset=utf-8');
}

if(PHP_VERSION < '6.0')
{
    @ini_set('magic_quotes_runtime', 0); 
    defined('_MAGIC_QUOTES_GPC_') or define('_MAGIC_QUOTES_GPC_', get_magic_quotes_gpc()?true:false);
}
