<?php
/**
 * 标准渲染器
 *
 * 用于渲染php格式的模板
 *
 * @category   Leb
 * @package    Leb_View
 * @author 	   liuxp
 * @version    $Id: stand.php 4501 2012-06-01 08:33:19Z guangzhao $
 * @copyright
 * @license
 */
require_once ('view/abstract.php');
class Leb_View_Stand extends Leb_View_Abstract
{
	/**
	 * Application对象
	 *
	 * @param Leb_Action
	 * @var unknown_type
	 */
	public $manager = null;

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
		$this->inc($template);
		$this->_renderContent =  ob_get_clean();
		return $this->_renderContent;
	}

	/**
	 * 包含文件
	 *
	 * @param string $template
	 * @param array $var 页面变量
	 */
	public function inc($template, $var=array())
	{
		foreach ($var as $k=>$v) {
			$this->$k = $v;
		}

		if ($template) {
			$this->setTemplate($template);
		}
		$templateFullPath = $this->getTemplatePath();
		include($templateFullPath);
	}

	/**
	 * 支持普通视图插件
	 * 调用方法：
	 * $this->functionName();
	 *
	 * 插件存放在项目目录以
	 * stand.func.php为名其中func为函数名
	 */
	public function __call($methodName, $arguments)
	{
		$fileName = 'stand.' . $methodName . '.php';
		$pluginPath = '';
		if ($this->manager instanceof  Leb_Action ) {
			$pluginPath = _APP_ . $this->manager->getRouter()->getApp() .
						'/' . $this->manager->getRouter()->getController() .
						'/template/plugins/' . $fileName;

		}

		if (file_exists($pluginPath)) {
			include_once($pluginPath);
		} else {
			$pluginPath = _APP_ . '_template/plugins/' . $fileName;
			if (file_exists($pluginPath)) {
				include_once($pluginPath);
			}
		}

		if (function_exists($methodName)) {
			return call_user_func_array($methodName, $arguments);
		}
	}
}

