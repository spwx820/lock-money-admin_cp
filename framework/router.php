<?php
/**
 * 路由适配器
 *
 *
 * @category   Leb
 * @package    Leb_Dispatcher
 * @author 	   liuxp
 * @version    $Id: router.php 4501 2012-06-01 08:33:19Z guangzhao $
 * @copyright
 * @license
 */
require_once ('router/query.php');
require_once ('router/stand.php');
class Leb_Router extends Leb_Plugin_Abstract
{
	/**
	 * 单例
	 *
	 * @var Leb_Request
	 */
	static protected $_instance = null;

	/**
	 * 路由引擎
	 *
	 * @var Leb_Router
	 */
	static protected $_engine = null;

	/**
	 * 实例化本程序
	 * @param $args = func_get_args();
     * @return object of this class
	 */
	static public function getInstance()
	{
		if (!isset(self::$_instance)) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * 构造函数
	 *
	 * @param Leb_Config
	 */
	protected function __construct($params=array())
	{
		parent::__construct($params);
		$this->_initConfig();
    	$engine = $this->getEnv('engine');
    	$engine =  isset($engine) && $engine ? $engine : "Leb_Router_Stand";
    	if (!self::$_engine) {
    		self::$_engine = new $engine();
    	}
	}

    /**
     * 调用代理的类的相应方法
     * 如$this->display()可能调用的是smarty->display();
     *
     * @param unknown_type $methodName
     */
    public function __call($methodName, $arguments)
    {
    	if (self::$_engine) {
    		$result =  call_user_func_array( array(self::$_engine, $methodName), $arguments);
    		return $result;
    	}
    }

	/**
	 * 设置本对象需要的请求对象
	 *
	 * @param Leb_Request $plugins
	 */
	public function execute($plugins)
	{
		return self::$_engine->execute($plugins);
	}

}


