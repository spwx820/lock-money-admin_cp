<?php
/**
 * 普通路由器
 *
 * 1. 支持如:
 * 	http://域名/app/controller/action/
 * 	http://域名/controller/action/
 *
 *
 * @category   Leb
 * @package    Leb_Router
 * @author 	   liuxp
 * @version    $Id: query.php 4501 2012-06-01 08:33:19Z guangzhao $
 * @copyright
 * @license
 */

class Leb_Router_Query extends Leb_Plugin_Abstract
{
	/**
	 * 获得Request对象
	 *
	 * @var string
	 */
	protected $_request = '';

	/**
	 * 配对后的结果，用于获得最终路由的
	 *
	 * @var array
	 */
	protected $_matches = array();


	/**
	 * 运行路由后的应用程序名
	 *
	 * @see setAppName();
	 * @var string
	 */
	protected $_appName = '';

	/**
	 * 运行路由后的控制器名
	 *
	 * @see setControllerName();
	 * @var string
	 */
	protected $_controllerName = '';

	/**
	 * 运行路由后的动作名
	 *
	 * @see setActionName();
	 * @var string
	 */
	protected $_actionName = '';

	/**
	 * 所有的请求参数
	 *
	 * @var array
	 */
	protected $_querys = array();

	/**
	 * 获得配对结果集
	 * 包括controller, action , app 等
	 *
	 * @return array
	 */
	protected function getMatches()
	{
		return $this->_matches;
	}

	/**
	 * 获得控制器名字
	 * 默认为controller
	 *
	 * @return string
	 */
	public function getController()
	{
		return $this->_controllerName;
	}

	/**
	 *  设置
	 */
	public function setController($controllerName)
	{
		$this->_controllerName = $controllerName;
	}

	/**
	 * 获得控制器名字
	 * 默认为controller
	 *
	 * @return string
	 */
	protected function _getController()
	{
		if (@$this->_matches && @$this->_matches['controller']) {
			return $this->_matches['controller'];
		} else {
			return $this->getBaseController();
		}
	}

	/**
	 * 获得动作名，动作是控制器的具体执行函数
	 * 默认为index
	 *
	 * @return string
	 */
	public function getAction()
	{
		return $this->_actionName;
	}

	/**
	 * 在重新分发时很有用
	 *
	 */
	public function setAction($actionName)
	{
		$this->_actionName = $actionName;
	}

	/**
	 * 获得动作名，动作是控制器的具体执行函数
	 * 默认为index
	 *
	 * @return string
	 */
	protected function _getAction()
	{
		if (@$this->_matches && !empty($this->_matches['action'])) {
			return $this->_matches['action'];
		} else {
			return $this->getBaseAction();
		}
	}

	/**
	 * 获得应用程序名称，应用程序对应一个具体的项目
	 * 默认为default
	 *
	 * @return string
	 */
	public function getApp()
	{
		return $this->_appName;
	}

	/**
	 * 设置应用程序名，为重新分发
	 *
	 * @param string $appName
	 */
	public function setApp($appName)
	{
		$this->_appName = $appName;
	}

	/**
	 * 获得应用程序名称，应用程序对应一个具体的项目
	 * 默认为default
	 *
	 * @return string
	 */
	protected function _getApp()
	{
		if ($this->_matches && !empty($this->_matches['app'])
				&& $this->_matches['app']!='www') {
			return $this->_matches['app'];
		} else {
			return $this->getBaseApp();
		}
	}

	/**
	 * 如果没有指定param返回一个参数数组
	 *
	 * @param string|int $param
	 * @return mixed
	 */
	public function getQuerys($param=null)
	{
		if (!empty($param)) {
			if (isset($this->_querys[$param])) {
				return $this->_querys[$param];
			} else {
				return '';
			}
		} else {
			return $this->_querys;
		}
	}

	/**
	 * 设置查询参数
	 *
	 * @param array $params
	 */
	public function setQuerys($params)
	{
		$this->_querys = array_merge($this->_querys, $params);
	}

	/**
	 * 如果没有指定param返回一个参数数组
	 * 如果指定了param 且存在 param:paramname则返回paramname
	 *
	 * @param string|int $param
	 * @return mixed
	 */
	protected function _getQuerys($param=null)
	{
		if(!empty($param)) {
			return $this->getRequest()->getQuery($param);
		} else {
			return $this->getRequest()->getQuery();
		}
	}

	/**
	 * 设置请求对象
	 *
	 * @param Leb_Request $requestObject
	 */
	public function setRequest($requestObject)
	{
		$this->_request = $requestObject;
	}

	/**
	 * 获得请求对象
	 * @return Leb_Request
	 */
	public function getRequest()
	{
		if (!$this->_request) {
			throw new Leb_Exception('Please run Leb_Router::run() first! ');
		}
		return $this->_request;
	}

	/**
	 * 获得路由配置信息
	 * @return Leb_Config
	 */
	public function getConfig()
	{
		return $this->getEnv();
	}

	/**
	 * 获得基本的路由设置
	 *
	 * @param string $section 哪一段
	 * @return mixed
	 */
	public function getBase($section='')
	{
		$config = $this->getConfig();
		if (isset($config->base)) {
			if ($section && isset($config->base->$section)) {
				return $config->base->$section;
			} else {
				return $this->base;
			}
		} else {
			return null;
		}
	}

	/**
	 * 获得基本域
	 * @return string
	 */
	public function getBaseDomain()
	{
		$basedomain = $this->getBase('basedomain');
		if (!$basedomain) {
			$domain = Leb_Request::get('HTTP_HOST');
			$domainSections  = explode('.', $domain);
			$domainparts = count($domainSections);
			$basedomain = $domainSections[$domainparts-2] . $domainSections[$domainparts-1];
		}
		return $basedomain;
	}

	/**
	 * 获得基本端口
	 * @return string
	 */
	public function getBasePort()
	{
		$baseport = $this->getBase('baseport');
		$port = isset($baseport) ? $baseport : '';
		return $port;
	}

	/**
	 * 获得基本参数名，默认为id
	 *
	 * @return string
	 */
	public function getBaseParam()
	{
		$param = $this->getBase('baseparam');
		return isset($param) ? $param : 'id';
	}

	/**
	 * 获得基本应用程序名，默认为www或default
	 *
	 * @return string
	 */
	public function getBaseApp()
	{
		$baseapp = $this->getBase('baseapp');
		if (empty($baseapp) || $baseapp == 'www') {
			return 'default';
		} else {
			return $baseapp;
		}
	}

	/**
	 * 获得基本控制器名
	 *
	 * @return string
	 */
	public function getBaseController()
	{
		$controller = $this->getBase('basecontroller');
		if (empty($controller)) {
			$controller = 'default';
		}
		return $controller;
	}

	/**
	 * 获得基本动作名
	 *
	 * @return string
	 */
	public function getBaseAction()
	{
		$action = $this->getBase('baseaction');
		if (empty($controller)) {
			$action = 'index';
		}
		return $action;
	}

	/**
	 * 生成URL
	 *
	 * @param string $action       动作名
	 * @param string $controller   控制器名，默认与当前
	 * @param string $controller   控制器名 ，可选，默认与当前控制器同名
 	 * @param string $application  模块名   ，可选，默认与当前模块名相同
 	 * @param array $params        传递的参数，参数将以GET方法传递
	 * @return string
	 */
	public function buildUrl($action, $controller='', $application='', $param=array())
	{
		if ('' == $controller) {
			$params['controller'] = $this->getController();
		} else {
		    $params['controller'] = $controller;
		}

		if ('' == $application) {
			$params['app'] = $this->getApp();
		} else {
		    $params['app'] = $application;
		}
		$params['action'] = $action;
		$params += $param;
		return _DOMAIN_ . '/?' . http_build_query($params);
	}
	/**
	 * 设置本对象需要的请求对象
	 *
	 * @param Leb_Request $plugins
	 */
	public function execute($plugins)
	{
		$this->_initConfig();
		// 获得传递过来的request对象
		$this->setRequest($plugins);

		// 解析网址以生成相应的参数对象
		$this->_querys = $this->_matches = $this->_getQuerys();
        if($this->_matches){
            unset($this->_matches[0]);
            $this->_matches['app'] = $this->_matches['controller'] = $this->_matches['action'] = '';
            foreach($this->_matches as $key => $value)
            {
                $pre = strpos($value, '--');
                $pos = strpos($value, '=');
                if(false === $pre && false === $pos){
                    if(! $this->_matches['app'])
                        $this->_matches['app'] = $value;
                    elseif(!$this->_matches['controller'])
                        $this->_matches['controller'] = $value;
                    elseif(!$this->_matches['action'])
                        $this->_matches['action'] = $value;
                }
            }
        }
		$this->_appName = $this->_getApp();
		$this->_controllerName = $this->_getController();
		$this->_actionName = $this->_getAction();

		return parent::execute($plugins);
	}


}


