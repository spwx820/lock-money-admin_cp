<?php
/**
 * 普通路由器
 *
 * 1. 支持如?controller=default&action=index&app=default
 *
 *
 * @category   Leb
 * @package    Leb_Router
 * @author 	   liuxp
 * @version    $Id: stand.php 25892 2013-01-16 08:22:23Z guangzhao $
 * @copyright
 * @license
 */

class Leb_Router_Stand extends Leb_Plugin_Abstract
{
	/**
	 * 用户路由规则集正则
	 *
	 * @var array
	 */
	protected $_userRouters = array();

	/**
	 * 默认路由规则集正则
	 *
	 * @var array
	 */
	protected $_defaultRouters = array();

	/**
	 * 路由规则文件
	 *
	 * @var string
	 */
	//protected $_router_config_file = 'router.sample.xml';

	/**
	 * 路由规则配置对象
	 *
	 * @var Leb_Config
	 */
	protected $_router_config = null;

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
	 * 系统键
	 *
	 * @var array
	 */
	protected $_systemKeys = array('action', 'controller', 'querys', 'params', 'port', 'domain', 'app');

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
		if (!$this->getEnv('rewrite')) {
			$controller = $this->getRequest()->getQuery('controller');
			if (empty($controller)) {
				return $this->getBaseController();
			} else {
				return $controller;
			}
		}

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
		if (!$this->getEnv('rewrite')) {
			$action = $this->getRequest()->getQuery('action');
			if (empty($action)) {
				return $this->getBaseAction();
			} else {
				return $action;
			}
		}

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
		if (!$this->getEnv('rewrite')) {
			$app = $this->getRequest()->getQuery('app');
			if (empty($app)) {
				return $this->getBaseApp();
			} else {
				return $app;
			}
		}
        
		if ($this->_matches && !empty($this->_matches['app']) && $this->_matches['app']!='index.php') {
			return $this->_matches['app'];
		} else {
			return $this->getBaseApp();
		}
	}

	/**
	 * 如果没有指定param返回一个参数数组
	 * 如果指定了param 且存在 param:paramname则返回paramname
	 *
	 * @param string|int $param
	 * @return mixed
	 */
	public function getQuerys($param=null)
	{
		if (!empty($param)) {
			if (empty($this->_querys[$param])) {
				return '';
			} else {
				return $this->_querys[$param];
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
		if (!$this->getEnv('rewrite')) {
			return $this->getRequest()->getQuery($param);
		}

		$systemKeys = $this->_systemKeys;
		foreach ($this->_matches as $key=>$match) {
			if (!in_array($key, $systemKeys) && !is_numeric($key)) {
				$this->_matches['param'][$key] = $match;
			}
		}

		if (empty($this->_matches['param'])) {
			$params = array();
			// params 像/ab/cd/ef这样，没有键名
			if (!empty($this->_matches['params'])) {
				$params += explode('/', $this->_matches['params']);
			}

			// querystring part 带键名
			if (@$this->_matches['querys']) {
				$querys = parse_url($this->_matches['querys']);
				parse_str(@$querys['query'] , $queryArray);
				$params += $queryArray;
			}
			$this->_matches['param'] = $params;
		}

		if ($this->_matches && isset($this->_matches['param'])) {
			if (!is_null($param)) {
				if ( isset($this->_matches['param'][$param])) {
					return $this->_matches['param'][$param];
				} else {
					return null;
				}
			} else {
				return  $this->_matches['param'];
			}
		} else {
			return null;
		}

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
	public function buildUrl($action, $controller='', $application='', $params=array())
	{
		if ('' == $controller) {
			$controller = $this->getController();
		}

		if ('' == $application) {
			$application = $this->getApp();
		}
        
		if (!$this->getEnv('rewrite')) {
			$params['controller'] = $controller;
			$params['app'] = $application;
			$params['action'] = $action;
			return _DOMAIN_ . '/?' . http_build_query($params);
		} else {
            $userRules = array_merge($userRules = $this->getRouters('rule'), $this->getRouters('defaultrule'));
			foreach ($userRules as $rule) {
				if (preg_match('/action/',$rule) && preg_match('/controller/',$rule) && preg_match('/app/',$rule)) {
					$rule = substr($rule, strpos($rule, '/'));
					$url = str_replace(array('[app:]', '[controller:]', '[action:]'),array($application, $controller, $action),$rule);
					if (!empty($params)) {
						$url .= '?' . http_build_query($params);
					}
					return _DOMAIN_ .$url;
				}
			}
			return '';
		}
	}
	/**
	 * 根据当前的request对象获得匹配的路由规则
	 *
	 * 1. 根据base参数把所有的路由规则生成合适的正则表达式
	 * 2. 根据当前的网址匹配已设置的路由规则正则
	 * 3. 如果匹配成功，把匹配的结果返回，否则抛出404 Error
	 *
	 * @example
	 * rule  : [app:][domain:][port:]/[controller:]/[action:]/[param:]
	 * match : www.lebwork.org/help/index/about_lebwork
	 * result: app=> www , domain=>lebwork.org, port=>80, controller=>help, action=> index, param=>about_lebwork
	 *
	 * @return array|boolean:false
	 */
	public function matchRouter()
	{
		// 用户规则正则集
		if (empty($this->_userRouters)) {
			$userRules =  (array) $this->getRouters('rule');
			foreach ($userRules as $userRule) {
				$this->_userRouters[] = $this->parseRule($userRule);
			}
		}
		// 默认正则集
		if (empty($this->_defaultRouters)) {
			$defaultRules = (array) $this->getRouters('defaultrule');
			foreach ($defaultRules as $rule) {
				$this->_defaultRouters[] = $this->parseRule($rule);
			}
		}
		// 先用户路由后默认路由
		$request = $this->getRequest();
		$routers = array_merge($this->_userRouters, $this->_defaultRouters);
 
		$url = $request->getUrl();
		// 匹配用户规则，如果配对成功，返回配对结果
		foreach ($routers as $router) {
			if (preg_match($router, $url, $matches)) {
                $this->_matches = $matches ;
                // 实现虚拟action功能，就是只能获取action的值，类似/virtaction1/xxxxxxxxx.html
                // remap router, virtual action ==> real action
                if (!isset($matches['app']) && !isset($matches['controller']) && isset($matches['vaction'])) {
                    $config = $this->getConfig()->toArray();
                    $vaction = $matches['vaction'];
                    if (isset($config['mapper']) && isset($config['mapper'][$vaction])) {
                        $loc = $config['mapper'][$vaction];
                        $matches['app'] = $loc['app'];
                        $matches['controller'] = $loc['controller'];
                        $matches['action'] = $loc['action'];
                        $this->_matches = $matches ;
                    }
                }
				return $matches;
              
			}
		}
		return false;
		//throw new Leb_Exception('Error 404, Page not found!');
	}

	/**
	 * 1.把路由器转换成正则
		. 先把统一在每一段的:后加上默认规则，如app由getBaseApp()获得
		. 定义好alpha, char, mixed的正则表达式
		. 参数由数据类型nubmer, char, all 补上如果没有指定，默认用char补上
		. 定义特殊字符"/" , "_" , "-", "."  作为网址允许的连接字符串
		. 正则替换，把[]按":"分成前后两段内容，转换成(?<前一段>[后一段])这样的表达式
	 * array('action', 'controller', 'querys', 'params', 'port', 'domain', 'app')
	 * @param string $rule
	 * @return string
	 */
	public function parseRule($rule)
	{
		// 转义连接符
		$specialChars = array('/', '-', '.');
		$replaceWith = array('[/]', '[-]', '[.]');
		$rule = str_replace($specialChars, $replaceWith, $rule);

		// 域名
        // $baseDomain =  $this->getBaseDomain();
        // $rule = str_replace(array('[domain:]'), array('[domain:' . $baseDomain . ']'), $rule);

		// 转换参数规则
		$rule = preg_replace('/\[(\w+):\]/', '[\\1:all]', $rule);
		$paramRegs = array(
						'alpha' => '\w+',
						'all'	=> '{tag}',
						'number' => '\d+'
						);
		foreach ($paramRegs as $key => $paramReg) {
			$rule = preg_replace("/\[(\w+):$key\]/", "[\\1:$paramReg]", $rule);
		}

		// 转换定义的规则
		$rule = preg_replace("/\[(\w+):(.*?)\]/", "(?<\\1>\\2)", $rule);

		// 转义[_]等连接规则
		$rule = preg_replace('/\[(.*?)\]/', '\\\\\1', $rule);
		$rule = str_replace('{tag}', '[^\/\-\?]*?', $rule);

		//$result = '/' . $rule . '(?<params>.*?)(?<querys>\?.*?)*\/*$/';
		$result = '/' . $rule . '(?<querys>[\/\?].*)*\/*$/';

		return $result;
	}

	/**
	 * 获得路由器
	 *
	 * @param string $section 哪一段
	 * @return mixed
	 */
	public function getRouters($section='')
	{
		$config = $this->getConfig()->toArray();
		if (isset($config['router'])) {
			if ($section && isset($config['router'][$section])) {
				return $config['router'][$section];
			} else { //不存在返回默认路由
                return array();
				//return $config['router']['defaultrule'];
			}
		} else {
			return array();
		}
	}

	/**
	 * 设置配置对象
	 * @param string $section 解析哪一段
	 * @param string $configFile 可以解析某个文件
	 * @return Leb_Config
	 */
	public function parseConfig($section='', $configFile='')
	{
		$this->setConfigFile($configFile);
		if (empty($this->_router_config_file)) {
			return null;
		} else {
			$configer = new Leb_Config_Xml($this->getConfigFile());
			$this->_router_config = $configer;
			return $this->_router_config;
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
			return _DEF_APP_;
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
			$controller = _DEF_CONTROLLER_;
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
			$action = _DEF_ACTION_;
		}
		return $action;
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
		if ($this->getEnv('rewrite')) {
			$this->matchRouter();
			$this->_appName = $this->_getApp();
			$this->_controllerName = $this->_getController();
			$this->_actionName = $this->_getAction();
			//$this->_querys = $this->_getQuerys();
		}

		return parent::execute($plugins);
	}

}


