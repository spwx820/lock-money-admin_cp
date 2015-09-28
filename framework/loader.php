<?php
/**
 * 自动加载程序
 *
 * 根据规则加载相关的类
 *
 * @category   Leb
 * @package    Leb_Plugin
 * @author     liuxp
 * @version    $Id: loader.php 39514 2013-03-19 05:59:24Z ziyuan $
 * @copyright
 * @license
 */

defined('__FRM_BEGIN__') or define('__FRM_BEGIN__', microtime(true));
defined('APP_TRACE') or define('APP_TRACE', false);
require_once(dirname(__FILE__).DIRECTORY_SEPARATOR.'global.php');
require_once(dirname(__FILE__).DIRECTORY_SEPARATOR.'exception.php');

class Appbase
{
    protected $_core = array(
        'Leb_User' => '/web/user.php',
        'Leb_Dao_Memcache' => '/dao/memcache.php',
    );

    private $_hinstance = array();
    private $_coreObj = array(
        'cache'=> 'Leb_Dao_Memcache',
    );

    public function __get($name)
    {
        if($this->hasComponent($name))
        {
            return $this->getComponent($name);
        }
        else
        {
            return null;
        }
    }

    private function hasComponent($name)
    {
        return isset($this->_coreObj[$name]);
    }

    private function getComponent($name)
    {
        if(!isset($this->_hinstance[$name]))
        {
            require_once(dirname(__FILE__).$this->_core[$this->_coreObj[$name]]);
            $class = $this->_coreObj[$name];
            $obj = null;
            if(method_exists($class, 'getInstance'))
            {
                //fix for php-5.2
                $obj = call_user_method('getInstance', $class);
            }
            else
            {
                $obj = new $class();
            }
            $this->_hinstance[$name] = $obj;
        }

        return $this->_hinstance[$name];
    }

    public function getVer()
    {
        return '1.0.0';
    }
}

class CmdApp extends Appbase
{

}

class WebApp extends Appbase
{
    private $_coreObj = array(
        'user' => 'Leb_User',
    );

    private $_hinstance = array();

    public function __get($name)
    {
        if($this->hasComponent($name))
        {
            return $this->getComponent($name);
        }
        else
        {
            return parent::__get($name);
        }
    }

    private function hasComponent($name)
    {
        return isset($this->_coreObj[$name]);
    }

    private function getComponent($name)
    {
        if(!isset($this->_hinstance[$name]))
        {
            $obj = new $this->_coreObj[$name];
            $this->_hinstance[$name] = $obj;
        }

        return $this->_hinstance[$name];
    }
}

class Leb_Loader
{
    private static $_app = null;

    /**
     * 自动加载类
     *
     * 这个函数不能抛出异常，否则无法充分利用SPL对autoload_functions()中的自动加载函数表的遍历功能
     *
     * @param string $class      - The full class name of a Leb component.
     * @return boolean true 表示类成功加载，false 表示类没加载成功，需要继续其他的autoload函数
     * @throws no throws
     */
    public static function loadClass($class)
    {
        if (class_exists($class, false) || interface_exists($class, false)) {
            return;
        }

        $file = explode('_', $class);

        if ('Leb' == $file[0] ) {
            array_shift($file);
        }
        foreach ($file as &$f) {
            $f = strtolower($f);
        }
        $file = implode(_DIR_SEPARATOR_, $file) . '.php';

        // 有可能这个自动加载函数找不到，但下一个自动加载函数能找到, 不能停止，所以不用这个函数
        // require_once($file); 
        @include_once($file);

        if (!class_exists($class, false) && !interface_exists($class, false)) {
            if (_DEBUG_) {
                // throw new Leb_Exception("文件 \"$file\" 不存在或类 \"$class\" 没有找到");
                // trigger_error("文件 \"$file\" 不存在或类 \"$class\" 没有找到", E_USER_WARNING);
            }
            return false;
        } else {
            return true;
        }
    }


    /**
     * 自动加载类
     *
     * @param string $class
     * @return string|false Class name on success; false on failure
     */
    public static function autoload($class)
    {
        try {
            self::loadClass($class);
            return $class;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * 自动注册 {@link autoload()} 用 spl_autoload() 方法自动实现
     *
     * @param boolean $enable 根据配置选项打开或关闭自动加载
     * @return void
     * @throws Leb_Exception 如果spl_autoload()不存在，抛出本异常
     */
    public static function setAutoLoad($enable=true)
    {
        if (!$enable) {
            return ;
        }
        if (!function_exists('spl_autoload_register')) {
            throw new Leb_Exception('spl_autoload 不存在，可能是SPL库没有安装');
        }
        spl_autoload_register(array(__CLASS__, 'autoload'));
    }

    /**
     * 注册结束脚本回调函数
     */
    public static function shutdown()
    {
        if(XHPROF_ANALYSIS)
        {
            $xhprof_data = xhprof_disable();
            $obj = FirePHP::getInstance(true)->info($xhprof_data);
            //format
            //[ct] => 2        # number of calls to bar() from foo()
            //[wt] => 37       # time in bar() when called from foo()
            //[cpu]=> 0        # cpu time in bar() when called from foo()
            //[mu] => 2208     # change in PHP memory usage in bar() when called from foo()
            //[pmu]=> 0        # change in PHP peak memory usage in bar() when called from foo()
        }

        if(!defined('__FRM_ACTION_END__') && defined('__FRM_ACTION_END__'))
        {
            define('__FRM_ACTION_END__', microtime(true));
            $request = Leb_Request::getInstance();
            $obj = FirePHP::getInstance(true);
            $obj->info('Response:'.(__FRM_ACTION_END__ - __FRM_ACTION_BEGIN__));
            $obj->info('Framework:'.(__FRM_ACTION_BEGIN__ - __FRM_BEGIN__));
        }
    }

    public static function app()
    {
        if(!self::$_app)
        {
            self::$_app = _CLI_ ? new CmdApp() : new WebApp();
        }

        return self::$_app;
    }
}

if(APP_TRACE)
{
    ob_start();
    register_shutdown_function('Leb_Loader::shutdown');
}

function app()
{
    return Leb_Loader::app();
}
