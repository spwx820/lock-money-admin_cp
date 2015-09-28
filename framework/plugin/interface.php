<?php
/**
 * 插件接口
 *
 * 插件是Leb的灵魂，插件给所有的框架类提供了活力，Leb的插件接口是唯一的。
 * 除少数几个类外（@see Leb_Loader, @see Leb_Config），
 * 所有的站内框架类都是从实现自于本插件接口，如果你要实现你的插件，你也必须遵守本接口定义
 * 当然，你也可以直接从本接口的直接抽象类(@see Leb_Plugin_Abstract)直接继承
 * 这样你只要实现插件逻辑就可以了，也就是说最少你只要有一个execute()方法就可以了<br/>
 *
 *
 * @category   Leb
 * @package    Leb_Plugin
 * @author 	   liuxp
 * @version    $Id: interface.php 4501 2012-06-01 08:33:19Z guangzhao $
 * @copyright
 * @license
 */


interface Leb_Plugin_Interface
{
	/**
	 * 运行插件
	 *
	 * 这个方式通常情况是不重新实现的，它只实现于
	 * @see Leb_Plugin_Abstract::run($plugins=null)
	 *
	 *
	 * @param Leb_Plugin_Abstract $plugins
     * @return mixed
     * @throws Leb_Exception
	 */
	public function run($plugins=null);

	/**
	 * 插件配置环境
	 *
	 * 配置环境参数是一些配置文件系列
	 *
	 * @param Leb_Config|Array $paramObject 参数对象
	 */
	public function setParam($paramObject);

	/**
	 * 获得配置环境参数
	 * @param string $paramName  参数名字
	 * @return mixed 参数实例
	 */
	public function getParam($paramName);

	/**
	 * 设置插件别名，在$this->getPlugin插件运行时的对象
	 *
	 * @param string $alias
	 */
	public function setAlias($alias='');

	/**
	 * 调试运行过程的变量或实例
	 * @param mixed $var
	 */
	public function __debug($var);

}
