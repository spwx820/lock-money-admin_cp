<?php
/**
 * 插件代理用于管理插件
 *
 *
 * @category   Leb
 * @package    Leb_Bootstrap
 * @version    $Id: abstract.php 1 2011-04-08 07:42:35Z xiaoping1 $
 * @copyright
 * @license
 */
require_once('plugin/abstract.php');
class Leb_Plugin_Broker_Abstract extends Leb_Plugin_Abstract
{

	/**
	 * 实例化过的插件系列
	 *
	 * @var array
	 */
	protected $_plugins = array();

	/**
	 * 通过插件系统运行后返回的插件实例对象
	 *
	 * @var array of objects
	 */
	protected $_runners = array();

	/**
	 * 应用程序加载前实例化
	 * 如果需要对本方法进行扩展，可以重载本方法
	 * @param array $plugins 要在本执行里执行的插件组
	 * @return self
	 */
	protected function execute($plugins)
	{
		if (!empty($plugins)) {
			$this->setPlugins($plugins);
		}

		$result = null;
		$plugins = $this->getPlugins();
		if (!empty($plugins) && is_array($plugins)) {
			$result = null;
			foreach ($plugins as $plugin) {
				// 加工厂，把前面一个插件运行的结果传递给后面
				$result = $plugin->run($result);
				// 把前面的结果保存在运行对象里供后续使用
				if (($result instanceof Leb_Plugin_Abstract)) {
					$this->setRunner($result->getAlias(), $result);
				}
			}
		}

		return parent::execute($result);
	}

	/**
	 * 添加要初始化的插件
	 * @param array $pluginClass 插件名字
	 */
	public function setPlugins($pluginClass)
	{
		foreach ((array)$pluginClass as $class=>$plugin)
		{
			$array = array($class=>$plugin);
			$this->setPlugin($array);
		}
	}

	/**
	 * 初始化插件
	 * @param array $pluginClass 插件参数
	 * @example
	 * setPlugin(array('router' => array('engine'=>'stand')));
	 */
	public function setPlugin($pluginClass)
	{
		try {
			if (is_string($pluginClass)) {
				$className = $pluginClass;
				$arguments = null;
			} else {
				$className = array_keys($pluginClass);
				$className = $className[0];
				$arguments = $pluginClass[$className];
			}
			if ($className) {
				$this->_plugins[$className] = new $className(extract($arguments));
			}
		} catch (Leb_Exception  $e) {
			throw $e;
		}
	}

	/**
	 * 获得初始化后的插件对象
	 * @return array 实例化的插件对象
	 */
	public function getPlugins()
	{
		return $this->_plugins;
	}

	/**
	 * 获得插件实例
	 * @param string $pluginClass
	 * @return Leb_Plugin_Interface
	 */
	public function getPlugin($pluginClass)
	{
		if (!empty($pluginClass) && isset($this->_plugins[$plugin])) {
			return $this->_plugins[$plugin];
		} else {
			return null;
		}
	}

	/**
	 * 设置跑过的对象
	 * @param string $objectName 对象全局名称
	 * @param Leb_Plugin_Abstract $object 具体实例化过后的对象
	 */
	public function setRunner($objectName , &$object=null)
	{
		if ($objectName instanceof Leb_Plugin_Abstract ) {
			$objectClassName = substr(get_class($objectName), 4);
			$this->_runners[$objectClassName] = $objectName;
		} else {
			if (!is_null($object)) {
				$objectName = ucwords($objectName);
				$this->_runners[$objectName] = $object;
			}
		}
	}

	/**
	 * 得到具体的对象通过对象类名或对象名
	 *
	 * @param string $objectName
	 * @return Leb_Plugin_Abstract
	 */
	public function getRunner($objectName)
	{
		if ('Leb_' == substr($objectName, 0 , 4)) {
			$objectName = substr($objectName, 4);
		}
		if (empty($this->_runners)) {
			return null;
		} else {
			$objectName = ucwords($objectName);
			if (array_key_exists($objectName, $this->_runners)) {
				return $this->_runners[$objectName];
			}
		}
	}

	/**
	 * 直接在代理里获得插件对象的实例,
	 * 如 $this->request
	 *
	 * @param string $objectName
	 * @return Leb_Plugin_Abstract
	 */
	public function __get($objectName)
	{
		if (isset($this->_runners[$objectName])) {
			return $this->getRunner($objectName);
		} else {
			throw new Leb_Exception('Error Runner ' . $objectName . ' set in current object');
		}
	}

	/**
	 * 获得具体的运行对象
	 * 可以调用如$this->getRequest()这样的方法
	 *
	 * @param string $methodName
	 * @param $arguments
	 * @return Leb_Plugin_Abstract
	 */
	public function __call($methodName, $arguments)
	{
		if ( 'get' == substr($methodName, 0, 3)) {
			$objectName = ucfirst(substr($methodName, 3));
			return $this->__get($objectName);
		}
	}
}


