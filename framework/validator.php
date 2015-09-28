<?php
/**
 * 校验类代理
 *
 *
 *
 * @category   Leb
 * @package    Leb_Plugin
 * @author 	   liuxp
 * @version    $Id: validator.php 4501 2012-06-01 08:33:19Z guangzhao $
 * @copyright
 * @license
 */

class Leb_Validator extends Leb_Plugin_Abstract
{
	/**
	 * 单例
	 *
	 * @var Leb_Request
	 */
	static protected $_instance = null;

	/**
	 * 触发错误的方式
	 *
	 * 值可以为 'show' 直接显示返回的消息直接显示,
	 * 'silent' 不理会，只过滤数值
	 *
	 * @var string
	 */
	protected $_trigerErrorMethod = 'show';

	/**
	 * 消息处理对象
	 *
	 * @var Leb_Info
	 */
	protected $_info = null;

	/**
	 * 实例化本程序
	 * @param $args = func_get_args();
     * @return object of this class
	 */
	static public function getInstance()
	{
		if (!isset(self::$_instance)) {
			self::$_instance = new self(func_get_args());
		}
		return self::$_instance;
	}

	/**
	 * 构造函数
	 * @param array $params
	 */
	protected function __construct($params=array())
	{
		parent::__construct($params);
		$errorMapFile = $this->getEnv('errorMapFile');
		if (!empty($errorMapFile)) {
			$this->_errorMapFile = $errorMapFile;
		}

		// 错误触发
		$trggierMethod = $this->getEnv('trggierMethod');
		if (!empty($trggierMethod)) {
			$this->_trigerErrorMethod = $trggierMethod;
		}

	}

	/**
	 * 检验是否有效
	 *
	 * @param string $validator 如numeric可能会调用Plugin_Validator_Numeric或Leb_Validator_Numeric类
	 * @param string $arguments['value'] 值
	 * @param string $arguments['infoKey'] 从外部配置文件获得的键值
	 * @return int
	 */
	protected function _isValid($validator, $arguments)
	{
		$className = 'Leb_Validator_' . ucfirst($validator);
		if (!Leb_Loader::loadClass($className)) {
			$className = 'Plugin_Validator_' . ucfirst($validator);
		}

		if (class_exists($className)) {
			return call_user_func_array(array($className, 'isValid'), $arguments);
		} else {
			return true;
		}
	}

	/**
	 * 可以获得如Leb_Validator::numeric($num,$key)
	 *
	 * @param string $method
	 * @param array $arguments
	 * @return mixed
	 */
	public function __call($method, $arguments)
	{
		$len = count($arguments)-1;
		// silent 表示原样返回数据，保持沉默
		if ('silent' == strtolower($arguments[$len])) {
			$this->_trigerErrorMethod = 'silent';
			array_pop($arguments);
		}

		$status = $this->_isValid($method, $arguments);
		// 如果处理结果正常，返回第一个值
		if ( true === $status) {
			return $arguments[0];
		}

		// 处理错误信息
		if ('show' == $this->_trigerErrorMethod ) {
			$this->_info->showInfo($status, '', '', $_POST);
		} else {
			return $arguments[0];
		}
	}

	/**
	 * 获得自定义的错误文件内容
	 *
	 * @return array
	 */
	protected function _getLanguage()
	{
		$configPath = array_reverse($this->getConfigSearchPath());
		$config = array();
		foreach ($configPath as $key=>$cdir) {
			$configFile = $cdir . '/' . $this->_errorMapFile . '.php';
			if (file_exists($configFile)) {
				$tmpConfig = new Leb_Config($configFile);
				$config = array_merge($config, $tmpConfig->toArray());
			}
		}
		return $config;
	}

	/**
	 * 保持沉默，不显示出错信息
	 *
	 */
	public function keepSilent($silent=false)
	{
		if ($silent) {
			$this->_trigerErrorMethod = 'silent';
		} else {
			$this->_trigerErrorMethod = 'show';
		}
	}
}

