<?php
/**
 * 控制器类
 * MVC的核心类
 * 实现请求->路由->过滤->分发->响应过程
 * 同时实例化插件中心
 *
 * @category   Leb
 * @package    Leb_Bootstrap
 * @version    $Id: controller.php 28310 2013-01-30 02:24:15Z ziyuan $
 * @copyright
 * @license
 */
require_once('plugin/broker/abstract.php');
require_once('request.php');
require_once('dispatcher.php');

class Leb_Controller extends Leb_Plugin_Broker_Abstract
{
	/**
	 * 单例模式
	 *
	 * @var instance of self
	 */
	static protected $_instance = null;
    private $shell = false;

	/**
	 * 实例化本程序
	 * @param $args = func_get_args();
     * @return object of this class
	 */
	static public function getInstance($shell=false)
	{
		if (!isset(self::$_instance)) {
			self::$_instance = new self($shell);
		}
		return self::$_instance;
	}

	protected function __construct($shell=false)
	{
        if($shell && !isset($_SERVER['argv']))
        {
            throw new Leb_Exception('this script must be run in command line.');
        }

        if($this->shell = $shell)
        {
            $this->parseParam();
        }

		$this->_initConfig();
	}

	/**
	 * 运行初始化程序
	 * 本实例运行单请求，简单路由功能
	 */
	public function execute($plugins)
	{
		$myPlugins = array(
            'Leb_Request'	 => array(),//请求对象
            'Leb_Dispatcher' => array(),//路由分发
        );

		// MVC 完毕
		$result = parent::execute($myPlugins);
		return $result;
	}

    private function parseParam()
    {
        $argv = $_SERVER['argv'];
        unset($argv[0]);
        $app = $ctl = $act = '';
        unset($_SERVER['argv']);
        foreach($argv as $key => $value)
        {
            $pre = strpos($value, '--');
            $pos = strpos($value, '=');
            if($pos > 3 && 0 === $pre)
            {
                $k = substr($value, 2, $pos - 2); 
                $v = substr($value, $pos + 1);
                $_SERVER['argv'][$k] = $v;
                continue;
            }

            if(false === $pre && false === $pos)
            {
                if(!$act)
                    $act = $value;
                elseif(!$ctl)
                    $ctl = $value;
                elseif(!$app)
                    $app = $value;
            }
        }

        if(!isset($_SERVER['argv']))
        {
            $_SERVER['argv'] = null;
        }
        $this->setRouter($act, $ctl, $app);
    }

    /**
     * 设置触发Action
     */
    public function setRouter($act, $ctl='', $app='', $params=array())
    {
        if(!$this->shell)
        {
            throw new Leb_Exception('this script must be run in command line.');
        }

        $_SERVER['REQUEST_URI'] = strtolower('/'.($app ? $app : _DEF_APP_)
            .'/'.($ctl ? $ctl : _DEF_CONTROLLER_)
            .'/'.($act ? $act : _DEF_ACTION_));
        if($params)
        {
            $_SERVER['argv'] = $params;
        }
    }

    /**
     * 设置Action参数
     */
    public function setArgv()
    {
        $argv = func_get_args();
        $_SERVER['argv'] = $argv;
    }

    /**
     * 获取Action返回值
     */
    public function getReturn()
    {
        return $this->_plugins['Leb_Dispatcher']->getActionReturn();
    }
}
