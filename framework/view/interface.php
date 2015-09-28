<?php
/**
 * 渲染视图接口
 *
 *
 * @category   Leb
 * @package    Leb_View
 * @author 	   liuxp
 * @version    $Id: interface.php 4501 2012-06-01 08:33:19Z guangzhao $
 * @copyright
 * @license
 */

interface Leb_View_Interface
{
	/**
	 * 渲染方法
	 * 默认会渲染对应的action 同级目录的action.tpl
	 *
	 * @param string $template 模板名字
	 * @return string 渲染结果
	 */
	public function render($template='');

	/**
	 * 加载变量
	 *
	 * @param string $var 变量名
	 * @param mixed $value
	 */
	public function assign($var, $value);

	/**
	 * 删除模板变量
	 *
	 * @param string $var 变量名称
	 */
    public function clearVars($var);

    /**
     * 设置模板变量
     *
     * @param string $var
     * @param $mixed $value
     */
    public function __set($var, $value);

    /**
     * 删除模板变量
     *
     * @param string $var
     */
    public function __unset($var);

    /**
     * 获取模板变量
     *
     * @param string $var
     */
    public function __get($var);
}
