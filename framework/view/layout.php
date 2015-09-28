<?php
/**
 * 渲染代理
 *
 * 根据渲染配置的环境变量选择演染插件，泻染插件必须要有自己的接口以便统一操作
 * 如果要用其他模板的渲染，请重载Leb_View_Interface的几个方法
 *
 * @category   Leb
 * @package    Leb_View
 * @author 	   liuxp
 * @version    $Id: layout.php 4501 2012-06-01 08:33:19Z guangzhao $
 * @copyright
 * @license
 */

class Leb_View_Layout extends Leb_Plugin_Abstract
{
	/**
	 * 单例
	 *
	 * @var Leb_Request
	 */
	static protected $_instance = null;

	protected $_layout = 'layout';

	protected $_layoutTag = 'content';

	/**
	 * 默认的模板，全路径
	 *
	 * @var string
	 */
	protected $_template = '';

	/**
	 * 模板扩展名
	 *
	 * @var string
	 */
	protected $_layoutSuffix = '.tpl';

	/**
	 * 哪个目录下的模板？
	 * 默认根据app,controller,action路径搜索
	 *
	 * @var string
	 */
	private $_layoutDirectory = '';


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
	 * 设置布局模板
	 *
	 * @param string $layoutTpl
	 */
	public function setLayout($layoutTpl)
	{
		$this->_layout = $layoutTpl;
	}

	/**
	 * 获得布局模板
	 * @return string
	 */
	public function getLayout()
	{
		return $this->_layout;
	}

	/**
	 * 设置布局物理模板的位置，
	 * @param array $directorys
	 * @return array
	 */
	public function setLayoutPath($directorys)
	{
		$this->_layoutDirectory  = (array) $directorys;
	}

	/**
	 * 获得布局位置
	 *
	 * @return array
	 */
	protected function getLayoutPath()
	{
		return $this->_layoutDirectory;
	}

	/**
	 * 设置布局占位符
	 *
	 * @param string $tagName
	 */
	public function setLayoutTag($tagName)
	{
		$this->_layoutTag = $tagName;
	}

	/**
	 * 获得布局占位符
	 * @return string
	 */
	protected function getLayoutTag()
	{
		return $this->_layoutTag;
	}

	/**
	 * 获得布局的后缀
	 *
	 * @return string
	 */
	protected function getLayoutSuffix()
	{
		return $this->_layoutSuffix;
	}

	/**
	 * 设置布局视图的suffix
	 *
	 * @param string $suffix
	 */
	public function setLayoutSuffix($suffix)
	{
		$this->_layoutSuffix = $suffix;
	}

    /**
     * 渲染布局视图
     * 遍历所给的目录列表，找到合适的layout模板
     *
     * @return null|string
     */
    public function renderLayout(&$renderer, $content)
    {
    	$renderer->assign($this->getLayoutTag(), $content);

    	$layoutPaths = $this->getLayoutPath();
    	if (empty($layoutPaths)) {
    		die('Layout Path not found!');
    	}

    	foreach ((array) $layoutPaths as $layoutPath)
    	{
    		$template = $this->getLayout();
			$renderer->setBase($layoutPath);
			$renderer->setTemplate('layout/' . $template);
			$path = $renderer->getTemplatePath();
			if (file_exists($path)) {
				return $renderer->run();
			}
    	}

    	return null;
    }

}

