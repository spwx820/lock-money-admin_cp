<?php
/**
 * Cache代理
 *
 * @category   Leb
 * @package    Leb_Storage
 * @author 	   liuxp
 * @version    $Id: cache.php 6406 2012-07-31 08:27:55Z kaikai $
 * @copyright
 * @license
 */
require_once('dao/memcache.php');
class Leb_Cache
{
	/**
	 * 单例
	 *
	 * @var Leb_Cache
	 */
	static protected $_instance = null;

	/**
	 * 存储对象
	 *
	 * @var Leb_Cache
	 */
	protected $_cacher = '';

	/**
	 * 存储引擎
	 *
	 * @var Leb_Storage_Abstract
	 */
	protected $_engine = null;

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
	 */
	protected function __construct()
	{
		if (empty($this->_engine)) {
			$cache = require_once('config/cache.php');
			// 缓存类型

			$cacher = $cache['engine'];
	    	if (empty($cacher)) {
	    		if (!empty($this->_cacher)) {
	    			$cacher = $this->_cacher;
	    		} else {
					$cacher = "Leb_Dao_Memcache";
	    		}
	    	}
    		$cacher = $cacher->getInstance();
    		// $this->_engine = $cacher->getCacher();
    		$this->_engine = $cacher;
    	}
	}

	/**
	 * 代理Cache的所有方法
	 *
	 * @param string $method
	 * @param array $arguments
	 */
	public function __call($method, $arguments)
	{
		return call_user_func_array(array($this->_engine, $method), $arguments);
	}

	/**
	 * 改变引擎
	 * 确保设置的引擎类在framework/Cache目录下如果只用单个单词传递
	 *
	 * @param string $engine
	 */
	public function changeEngine($engine="memcache")
	{
		$engine = "Leb_Dao_" . ucfirst($engine);
		if (class_exists($engine)) {
			$this->_engine = new $engine();
		} else {
			throw new Leb_Exception('Error cache Engine ' . $engine);
		}
	}

	/**
	 * 返回使用的引擎
	 *
	 * @return string
	 */
	public function getEngine()
	{
		return $this->_engine;
	}

	/**
	 * 代理对象执行_set
	 *
	 * @param string $key
	 * @param mixed $value
	 * @param int $expire
	 */

	public function set($key, $value, $expire=600)
	{
		return $this->_engine->set($key, $value, array('expire' => $expire));
	}

	/**
	 * 代理对象执行_get
	 *
	 * @param string $key
	 */
	public function get($key)
	{
		return $this->_engine->get($key);
	}


	/**
	 * 代理对象执行del
	 *
	 * @param string $key
	 */
	public function del($key)
	{
		return $this->_engine->del($key);
	}

}

