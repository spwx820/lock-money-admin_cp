<?php
/**
 * 插件抽象类
 *
 * 插件抽象类实现了Leb_Plugin_Interface的几个函数定义，并且有自己的方法
 * 本类完整地实现了：前置插件(_preLaunch) -> 本类执行(execute) ->  后置插件(_afterLaunch) 几个过程
 * 前置插件实现了前一个插件的过滤及属性变更，本类执行实现了本类的主要功能，
 * 后置插件是把本类实现的结果或方法通过后续过滤以提供下一个插件使用。
 *
 * 自定义插件类：
 * 所有从本类继承的类至少要重载execute()方法才有意义
 *
 * 环境变量：
 * 插件方式可以在plugins.sample.xml里按示例添加相关插件运行环境
 * 也可以新建一个文件在config目录下，以类名(小写).php为文件名
 *
 *
 * @category   Leb
 * @package    Leb_Plugin
 * @author     liuxp
 * @version    $Id: abstract.php 37459 2013-03-11 04:45:08Z ziyuan $
 * @copyright
 * @license
 */

require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'interface.php');
class Leb_Plugin_Abstract implements Leb_Plugin_Interface
{
    /**
     * 参数对象
     *
     * @var Leb_Storage_Property
     */
    private $_params = null;

    /**
     * 获得插件名称
     *
     * @var string
     */
    private $_alias = '';

    /**
     * 插件搜索路径
     *
     * @var array
     */
    static private $_configSearchPath = array();

    /**
     * 构造函数
     * 加载一个参数对象
     *
     * @param Leb_Config
     */
    protected function __construct($params=array())
    {
        $params = (array) $params;
        foreach ($params as $key=>$value) {
            $this->setParam($value, $key);
        }
        $this->setAlias();

        // 插件搜索路径
        if (false === array_search(_CONFIG_, self::$_configSearchPath)) {
            self::$_configSearchPath[] = _CONFIG_;

        }

        // 插件环境初始化
        //$this->_initConfig();
    }

    /**
     * 指定运行过程中校验插件的位置
     *
     * @param string $paths
     */
    static public function addConfigSearchPath($paths)
    {
        $paths = (array) $paths;
        foreach ($paths as $path) {
            if (false === array_search($path, self::$_configSearchPath)) {
                array_unshift(self::$_configSearchPath, $path);
            }
        }
    }

    /**
     * 得到配置文件搜索路径
     * @return array
     */
    static public function getConfigSearchPath()
    {
        return self::$_configSearchPath;
    }

    /**
     * 初始化插件运行环境
     * 加载插件配置文件
     */
    protected function _initConfig()
    {
        //$cache = Leb_Cache::getInstance();
        foreach (self::$_configSearchPath as $path) {

            //如果不存在本类的配置文件，则用系统默认的配置文件
            $className = strtolower(str_replace('_', '.', $this->getAlias()));
            $configFile = $path . $className . '.php';

            //如果是文件，写入缓存
            /*$key = md5($configFile);
            if (!($config = $cache->get($key))) {
                if (file_exists($configFile)) {
                    $config = require($configFile);
                    $cache->set($key,$config,86000);
                }
            }*/

            if (file_exists($configFile)) {
                $config = require($configFile);
            }

            if (!empty($config)) {
                $config = new Leb_Config($config);
                $this->setParam($config);
                return ;
            }
        }

    }

    /**
     * 设置本插件的名称
     *
     * @param string $alias
     */
    public function setAlias($alias='')
    {
        if (empty($alias)) {
            $class = get_class($this) ;
            /**
             * 类名的第一个"_"前的认为是类前缀
             */
            $pos = strpos($class, '_', 1);
            $this->_alias = ucwords( substr($class , $pos+1));
        } else {
            $this->_alias = $alias;
        }
    }

    /**
     * 获得本插件的类名
     *
     * @return string
     */
    public function getAlias()
    {
        return $this->_alias;
    }

    /**
     * 插件预热
     * 过滤传递进来的插件实例通过另一些配置在配置文件里的插件
     *
     * @param Leb_Plugin_Abstract $plugins
     */
    protected function _preLaunch($plugins=null)
    {
        // 运行配置的的前置插件
        $prePlugins = $this->_getPre();
        $plugins = $this->executePlugins($plugins, $prePlugins);
        return $plugins;
    }

    /**
     * 把本次结果进行二次过滤
     * 本插件运行结束后，插件运算的本插件实例在抛给下一个插件实例时
     *
     * @param Leb_Plugin_Abstract $plugins 通常是$this
     * @return Leb_Plugin_Abstract
     */
    protected function _afterLaunch($plugins=null)
    {
        // 运行配置的的前置插件
        $afterPlugins = $this->_getAfter();
        $plugins = $this->executePlugins($plugins, $afterPlugins);

        return $plugins;
    }

    /**
     * 本插件的核心运行过程
     *
     * 直接使用preLaunch()传过来的插件对象（也可以为null)
     * 然后传出$this对象给afterLaunch()实现真正的插件过程
     * 本方法同样会被(Leb_Plugin_Abstract::run())方法直接调用
     *
     * 子类不一定要实现这个方法，也不一定要返回$this对象，看需求
     * 但建议每一个插件都实现这个方法，让你的插件变得更有主题
     *
     * @param array $plugins 插件组
     * @return Leb_Plugin_Abstract 对象本身
     */
    protected function execute($plugins)
    {
        return $this;
    }

    /**
     * 运行插件三部曲
     *
     * 如果你想要真正的实现插件的前置、后置等完成步聚，你最好定义好
     * 你的execute()方法，并且让你的execute()返回$this实例以供_afterLaunch()使用
     * 你不用管你的实现过程是什么的，在使用插件时，如果你的插件功能是单一的，目的性非常
     * 明确的，并且允许用户在插件运行时把传入时的对象或传出的对象进行修改时，最好使用本
     * 方法。
     *
     * 以下是run方法的调用过程：
     * <code>
     * $request = Leb_Request::getInstance(); //前置插件
     * $router = $this->run($request); //返回本实例以供后续插件使用
     * $dispatcher = Leb_Dispatcher::getInstance();
     * $result = $dispatcher->run($router);
     * </code>
     *
     * 配置说明：
     * 如果你的插件类不允许加载前置及后续插件，请设置环境变量disAllowRun段
     * 如果你想你的插件类允许调试，请打开环境变量debug，
     *
     *
     * @final 插件流程子类不允许重载
     * @param Leb_Plugin_Abstract $plugins
     */
    public final function run($plugins=null)
    {
        // 1:查看环境变量里是否有allowRun
        if (!$this->getEnv('disAllowRun')) {
            $this->_preLaunch($plugins);
        }

        // 2:把plugins传给本类进行加工
        $result = $this->execute($plugins);

        // 3：把自己通过插件过滤出去
        if (!$this->getEnv('disAllowRun')) {
            $this->_afterLaunch($result);
        }

        //显示调试信息
        if(_DEBUG_ && $result && is_object($result))
        {
            $this->__debug($result);
        }
        return $result;
    }

    /**
     * 环境运行参数
     *
     * @param Leb_Config|Array $paramObject
     */
    public function setParam($paramObject)
    {
        if ($paramObject instanceof Leb_Config  ) {
            $this->_params = $paramObject;
        } else {
            $this->_params = new Leb_Config((array) $paramObject);
        }
    }

    /**
     * 获得运行本插件所需的参数
     *
     * @param string $paramName 插件名字
     * @return mixed
     */
    public function getParam($paramName)
    {
        if (isset($this->_params->$paramName)) {
            return $this->_params->$paramName;
        }
        return null;
    }

    /**
     * 获得环境变量
     *
     * @param string $param 参数
     * @final
     * @return mixed
     */
    public function getEnv($param='')
    {
        $config = $this->getParam('env');
        if (!empty($param)) {
            if (isset($config->$param)) {
                return $config->$param;
            } else {
                return null;
            }
        } else {
            return $config;
        }
    }

    /**
     * 获得预插件
     * @param string $param 参数
     * @final
     * @return mixed
     */
    protected function _getPre($param='class')
    {
        $config = $this->getParam('pre');
        if ($param) {
            if (isset($config->$param)) {
                if ($config->$param instanceof Leb_Config ) {
                    return $config->$param->toArray();
                } else {
                    return (array) $config->$param;
                }
            } else {
                return null;
            }
        } elseif ($config) {
            return $config->toArray();
        } else {
            return null;
        }
    }

    /**
     * 获得后插件
     * @param string $param 参数
     * @final
     * @return mixed
     */
    protected function _getAfter($param='class')
    {
        $config = $this->getParam('after');
        if ($param) {
            if (isset($config->$param)) {
                if ($config->$param instanceof Leb_Config ) {
                    return $config->$param->toArray();
                } else {
                    return (array) $config->$param;
                }
            } else {
                return null;
            }
        } elseif ($config) {
            return $config->toArray();
        } else {
            return null;
        }
    }

    /**
     * 调试变量
     *
     * @param mixed $var
     */
    public function __debug($var)
    {
        Leb_Debuger::debug($var);
    }

    /**
     * 开发debug选项
     *
     * @param boolean $debug
     */
    public function openDebug($debug=true)
    {
        $this->_debug = $debug;
    }

    /**
     * 执行插入的插件系列
     * 参考_preLaunch及_afterLaunch
     * 返回一个过滤过的插件实例
     *
     * @param array $pluginsClass
     * @param Leb_Plugin_Abstract $plugin
     * @return Leb_Plugin_Abstract
     */
    protected function executePlugins($plugin, $pluginsClass)
    {
        $result = $plugin;
        if (!empty($pluginsClass)) {
            foreach ((array) $pluginsClass as $p) {
                $pluginName =  ucwords($p);
                $object = new $pluginName();
                $result = $object->run($result);    // 加工厂，把前面一个插件运行的结果传递给后面
            }
        }
        return $result;
    }

    /**
     * 缓存配置文件
     *
     * @param string $var
     * @param mixed $values
     * @return mixed
     */
    public function cacheVar($var, $values=null)
    {
        $cacher = Leb_Cache::getInstance();
        if (empty($values)) {
            return $cacher->get($var);
        } else {
            return $cacher->set($var, $values);
        }
    }
}
