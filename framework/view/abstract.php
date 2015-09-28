<?php
/**
 * 标准渲染器
 *
 * 用于渲染php格式的模板
 *
 * @category   Leb
 * @package    Leb_View
 * @author 	   liuxp
 * @version    $Id: abstract.php 4501 2012-06-01 08:33:19Z guangzhao $
 * @copyright
 * @license
 */
require_once('view/interface.php');
class Leb_View_Abstract extends Leb_Plugin_Abstract implements Leb_View_Interface
{
	/**
	 * 模板变量
	 *
	 * @var mixed
	 */
	protected $_vars = array();

	/**
	 * 渲染过的结果
	 *
	 * @var string
	 */
	protected $_renderContent = '';

	/**
	 * 默认的模板，全路径
	 *
	 * @var string
	 */
	protected $_template = '';

	/**
	 * 哪个目录下的模板？
	 * 默认根据app,controller,action路径搜索
	 *
	 * @var string
	 */
	protected $_templateDirectory = '';

	/**
	 * 模板扩展名
	 *
	 * @var string
	 */
	protected $_templateSuffix = '';

	/**
	 * 设置要渲染的模板
	 *
	 * @param string $template
	 */
	public function setTemplate($template)
	{
		$this->_template = $template;
	}

	/**
	 * 设置模板位置目录
	 *
	 * @param $baseDirectory
	 */
	public function setBase($baseDirectory)
	{
		$this->_templateDirectory = $baseDirectory;
	}

	/**
	 * 获得模板位置目录
	 *
	 * @return string
	 */
	public function getBase()
	{
		if (empty($this->_templateDirectory)) {
			$dir = $this->getEnv('templateDir');
			! isset($dir)  && $dir = '_template/';
			$this->setBase($dir);
		}
		return $this->_templateDirectory;
	}

	/**
	 * 获得模板绝对位置
	 *
	 * @return string
	 */
	protected function getTemplate()
	{
		return $this->_template;
	}

	/**
	 * 获得模板物理位置
	 *
	 * @return string
	 */
	public function getTemplatePath()
	{
		$template = $this->getTemplate();
		if (!file_exists($template)) {
			$template1 = _APP_ . $this->getBase() . $template . $this->getTemplateSuffix();
			if (!file_exists($template1)) {
				$dir = $this->getEnv('templateDir');
				! isset($dir)  && $dir = '_template';
				$template2 = _APP_ . $dir . '/' . $template . $this->getTemplateSuffix();
				if (file_exists($template2)) {
					return $template2;
				} else {
					die('Template <strong>['
					. $template . $this->getTemplateSuffix() . ']</strong> not found '
					. "in directorys <br/> -- " . _APP_ . $dir . '/'
					. " <br/> -- " . _APP_ . $this->getBase()
					);
				}
			} else {
				return $template1;
			}
		} else {
			return $template;
		}
	}

	/**
	 * 设置模板扩展
	 *
	 * @param string $suffix
	 */
	public function setTemplateSuffix($suffix)
	{
		$this->_templateSuffix = $suffix;
	}

	/**
	 * 获得模板扩展
	 * 通过插件变量设置
	 *
	 * @return string
	 */
	public function  getTemplateSuffix()
	{
		if ($this->_templateSuffix) {
			return $this->_templateSuffix;
		} else {
			$suffix = $this->getEnv('suffix');
			return $suffix ? $suffix : '.tpl';
		}
	}

	/**
	 * 渲染方法
	 * 默认会渲染对应的action 同级目录的action.tpl
	 *
	 * @param string $template 模板名字
	 * @return string 渲染模板结果
	 */
	public function render($template='')
	{
		ob_start();
		include_once($template);
		$this->_renderContent =  ob_get_clean();
		return $this->_renderContent;
	}

	/**
	 * 获得渲染结果
	 *
	 * @return string
	 */
	public function getRenderContent()
	{
		return $this->_renderContent;
	}

	/**
	 * 加载变量
	 *
	 * @param string $var 变量名
	 * @param mixed $value
	 */
	public function assign($var, $value)
	{
		$this->_vars[$var] = $value;
	}

	/**
	 * 获得模板变量
	 *
	 * @param string $var 变量名称
	 * @return mixed
	 */
	public function getVar($var)
	{
		return $this->__get($var);
	}

	/**
	 * 删除模板变量
	 *
	 * @param string $var 变量名称
	 */
    public function clearVars($var)
    {
    	if (isset($this->_vars[$var])) {
    		$this->__unset($var);
    	}
    }

	/**
	 * 设置模板变量
	 * @see self::assign()
	 * @param string $var
	 * @param mixed $value
	 *
	 */
    public function __set($var, $value)
    {
    	$this->assign($var, $value);
    }

    /**
     * 清除模板变量
     *
     * @param string $var
     */
    public function __unset($var)
    {
    	unset($var);
    }

    /**
     * 获得模板变量
     *
     * @param string $var 变量名
     * @return mixed
     */
    public function __get($var)
    {
    	if (isset($this->_vars[$var])) {
    		return $this->_vars[$var];
    	} else {
    		return null;
    	}
    }

	/**
	 * 代理执行渲染
	 *
	 * @param  string $template
	 * @return string
	 */
	protected function execute($template)
	{
		return $this->render($template);
	}
}

