<?php
/**
 * 渲染代理
 *
 * 根据渲染配置的环境变量选择演染插件，泻染插件必须要有自己的接口以便统一操作
 * 如果要用其他模板的渲染，请重载Leb_View_Interface的几个方法
 *
 * @category   Leb
 * @package    Leb_Plugin
 * @author 	   liuxp
 * @author     guangzhao1@leju.com
 * @version    $Id: view.php 36776 2013-03-08 07:21:10Z ziyuan $
 * @copyright
 * @license
 */

  // move to code, improved selected require_once
  // require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'view/smarty.php');
  // require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'view/stand.php');
  // require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'view/layout.php');
require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'plugin/abstract.php');
class Leb_View extends Leb_Plugin_Abstract
{
	/**
	 * 单例
	 *
	 * @var Leb_Request
	 */
	static protected $_instance = null;

	/**
	 * 渲染引擎
	 *
	 * @var Leb_View
	 */
	static protected $_engine = null;

	/**
	 * 是否允许使用$_renderLayout
	 *
	 * @var boolean
	 */
	private $_renderLayout = true;

	/**
	 * 布局对象
	 *
	 * @var Leb_View_Layout
	 */
	private $_layouter = null;

	/**
	 * CSS系列
	 *
	 * @var array
	 */
	private $_csses	= array();

	/**
	 * js系列
	 *
	 * @var array
	 */
	private $_jses	= array();

	/**
	 * js代码系列
	 *
	 * @var array
	 */
	private $_jsCodes= array();

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
	 * 代理某个具体渲染器的方法
	 *
	 * @param Leb_Config
	 */
	protected function __construct($params=array())
	{
		parent::__construct($params);
		$this->_initConfig();
    	$engine = $this->getEnv('engine');
    	$engine =  isset($engine) && $engine ? $engine : "Leb_View_Stand";

    	if (!self::$_engine) {
            $engine_file_name = strtolower(substr($engine, strlen(__CLASS__)+1)) . '.php';
            require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'view' . DIRECTORY_SEPARATOR . $engine_file_name);
    		self::$_engine = new $engine();
    	}

    	if ($this->isAllowLayout()) {
            require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'view/layout.php');
			$this->_layouter = Leb_View_Layout::getInstance();
		}

		self::$_engine->setTemplateSuffix($this->getEnv('suffix'));
	}

	/**
	 * 执行渲染过程
	 * 调用Render方法，看看系统要不要用layout对象
	 *
	 * @param array $plugins 插件组
	 * @return self 对象本身
	 */
	protected function execute($plugins)
	{
		// 加载模板内js及css
		$this->assignCssAndJs();

		// 渲染模板
		$engine = $this->getEngine();
		$result = $engine->run($plugins);
		if ($this->isAllowLayout()) {
			$result2 = $this->_layouter->renderLayout($engine, $result);
			$result2 && $result = $result2;
		}

		// 显示结果
		$this->output($result);
	}

	/**
	 * 渲染模板文件，并返回内容
	 *
	 * @param string $template
	 * @return string
	 */
	public function fetch($template='')
	{
        $engine = $this->getEngine();
        $result = $engine->execute($template);
        if ($this->isAllowLayout()) {
			$result2 = $this->_layouter->renderLayout($engine, $result);
			$result2 && $result = $result2;
		}
		return $result;
	}

    /**
     * 获得模板引擎
     * 根据插件环境变量获得调用哪个具体的渲染实例 类
     * 默认Leb_View
     *
     * @return string
     */
    public function getEngine()
    {
    	return self::$_engine;
    }

    /**
     * 调用代理的类的相应方法
     * 如$this->display()可能调用的是smarty->display();
     *
     * @param string  $methodName
     * @param array   $arguments
     */
    public function __call($methodName, $arguments)
    {
    	if (self::$_engine) {
    		return  call_user_func_array( array(self::$_engine, $methodName), $arguments);
    	}
    }

 	/**
	 * 启用或禁用layout
	 *
	 * @param boolean $need
	 */
	public function needLayout($need=false)
	{
		$this->_renderLayout = $need;
	}

	/**
	 * 是否允许使用布局视图
	 * @return boolean
	 */
	public function isNeedLayout()
	{
		return $this->_renderLayout;
	}

	/**
	 * 允许布局对象，通过环境变量获得
	 * 默认允许
	 *
	 */
	protected function isAllowLayout()
	{
		$configAllow = $this->getEnv('allowLayout') ;
		$allowLayout =  isset($configAllow) ? $configAllow : true;
		return $allowLayout && $this->isNeedLayout();
	}

	/**
	 * 设置布局对象的路径
	 * 代理layout对象的对应方法
	 */
	public function setLayoutPath($directorys = array())
	{
		$this->_layouter ->setLayoutPath($directorys);
	}

	/**
	 * 设置布局视图的suffix
	 *
	 * @param string $suffix
	 */
	public function setLayoutSuffix($suffix)
	{
		$this->_layouter->setLayoutSuffix($suffix);
	}

	/**
	 * 设置布局占位符
	 *
	 * @param string $tagName
	 */
	public function setLayoutTag($tagName)
	{
		$this->_layouter->setLayoutTag($tagName);
	}

	/**
	 * 设置布局模板
	 *
	 * @param string $layoutTpl
	 */
	public function setLayout($layoutTpl)
	{
		$this->_layouter->setLayout($layoutTpl);
	}

	/**
	 * 得到布局模板
	 * @return string
	 */
	public function getLayout()
	{
		$this->_layouter->getLayout();
	}

	/**
	 * 为模板加载变量
	 *
	 * @param string $key
	 * @param mixed $value
	 */
	public function __set($key, $value)
	{
		self::$_engine->assign($key, $value);
	}

	/**
	 * 应用类方法
	 * @author chenjin
	 */

	/**
	 * 把所有的js,css转换成字符串并加载
	 *
	 */
	private function assignCssAndJs()
	{
		// 渲染css
		$str = $this->cssToString();
		self::$_engine->assign('csses', rtrim($str, "\r\n"));

		// js
		$str = $this->jsToString();
		self::$_engine->assign('javascripts', rtrim($str, "\r\n"));
	}

	/**
	 * 向头部添加 css 文件
	 *
	 * Example:
	 * <code>
	 * Leb_View::addCss('/css/main.css');
	 * Leb_View::addCss(array('/css/Mypage.css', '/css/style.css'));
	 * </code>
	 *
	 * @param string|array $css			文件路径
	 * @return void
	 * @see : Leb_View::cssToString();
	 */
	public function addCss($css = array())
	{
		$css = (array) $css;
		$cssBase = $this->getEnv('cssBase');
		foreach ($css as $k => $v) {
			if (false == stristr($v, 'http://') && false == stristr($v, 'https://')) {
				if ((false == stripos($v, $cssBase)) ||
					('/' == $cssBase && '/' != substr($v, 0, 1))) {
						$css[$k] = $cssBase . $v;
				}
			}
		}
		$this->_csses = array_merge($this->_csses, $css);
	}

	/**
	 * 向头部添加 js 文件
	 *
	 * @param string|array $js			文件路径
	 * @return void
	 * @see Leb_View::jsToString()
	 */
	public function addJs($js = array())
	{
		$js = (array) $js;
		$jsBase = $this->getEnv('jsBase');
		foreach ($js as $k => $v) {
			if (false == stristr($v, 'http://') && false == stristr($v, 'https://')) {
				if ((false == stripos($v, $jsBase))
					|| ('/' == $jsBase && '/' != substr($v, 0, 1))) {
					$js[$k] = $jsBase . $v;
				}
			}
		}
		$this->_jses = array_merge($this->_jses, $js);
	}

	/**
	 * 向头部添加 meta 标签
	 *
	 *
	 * @param array $metas			键值对
	 * @param boolean $multi		是否是二维数组
	 * @return void
	 */
	public function addMeta($metas, $multi = false)
	{
		$str = '';
		if($multi) {
			foreach ($metas as $meta) {
				$str .= $this->metaToString($meta);
			}
		} else {
			$str .= $this->metaToString($metas);
		}

		self::$_engine->assign('metas', rtrim($str, "\r\n"));
	}

	/**
	 * 向头部添加 JS 片断
	 *
	 * @param string|array $code
	 * @return void
	 */
	public function addJsCode($code)
	{
		$str = "";

		if (!is_array($code)) {
			$code = array($code);
		}

		$this->_jsCodes = array_merge($this->_jsCodes, $code);

		$str .= '<script language="javascript" type="text/javascript">' . "\r\n";
		$str .= implode("\r\n", $this->_jsCodes);
		$str .= "\r\n</script>";
		self::$_engine->assign('jsCodes', $str);
	}

	/**
	 * 设置页面 <title></title>
	 *
	 * @param string $title
	 * @return void
	 */
	public function setTitle($title)
	{
		self::$_engine->assign('headTitle', $title);
	}


	/**
	 * 生成 <link .. />
	 *
	 * @return string
	 */
	protected function cssToString()
	{
		$str = '';
		foreach ($this->_csses as $v) {
			$str .= '<link href="'. $v . '" rel="stylesheet" type="text/css" />';
			$str .= "\r\n";
		}
		return $str;
	}

	/**
	 * 生成 <script ...></script>
	 *
	 * @return string
	 */
	protected function jsToString()
	{
		$str = '';
		foreach ($this->_jses as $v) {
			$str .= '<script language="javascript" src="' . $v .'"></script>';
			$str .= "\r\n";
		}

		return $str;
	}

	/**
	 * 生成 <meta ... />
	 *
	 * @param array $meta
	 * @return string
	 */
	protected function metaToString(array $meta)
	{
		$str = '<meta ';
		$tmp = array();

		foreach ($meta as $k => $v) {
			$tmp[] = $k . '="' . addslashes($v) . '"';
		}

		$str .= implode(' ', $tmp);
		$str .= " />\r\n";

		return $str;
	}

	/**
	 * 设置页面 <title></title>
	 *
	 * @param string $title
	 * @return void
	 */
	public function addMoreString($str)
	{
		self::$_engine->assign('moreString', $str);
	}

	/**
	 * 输出内容前的过滤方法
	 * 可以替换一些特殊方法
	 *
	 * @param string $str
	 */
	public function output($str)
	{
		/*if (preg_match('/buildUrl\(.*\) /', $str, $matches)) {
			var_dump($matches);
		}
		$router = Leb_Router::getInstance();
		var_dump($router->getApp());exit;*/
		echo $str;
	}

	/**
	 * 设置本对象的caller，一般为Leb_Action
	 *
	 * @param 一般为Leb_Action $manager
	 */
	public function setManager($manager)
	{
		self::$_engine->manager = $manager;
	}

	/**
	 * 外部获得本地环境变量
	 *
	 * @param string $key
	 * @return string
	 */
	static public function getEnvVar($key)
	{
		$object = self::getInstance();
		$path = $object->getEnv($key);
		return $path;
	}
}

