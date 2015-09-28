<?php
/**
 * 校验父类
 *
 *
 *
 * @category   Leb
 * @package    Leb_Plugin
 * @author 	   liuxp
 * @version    $Id: abstract.php 4501 2012-06-01 08:33:19Z guangzhao $
 * @copyright
 * @license
 */

class Leb_Validator_Abstract extends Leb_Plugin_Abstract
{
	/**
	 * 待定消息显示内容
	 *
	 * @var string
	 */
	static protected $_messages = array();

	/**
	 * 验证某一个等式
	 *
	 * @param mixed $value
	 * @param string $infoKey
	 * @return boolean|string
	 */
	static public function isValid($value, $infoKey='')
	{
		return true;
	}

	/**
	 * 返回自定义消息或系统消息
	 *
	 * @param string $message
	 * @param string $infoKey
	 * @return string
	 */
	static public function getMessages($message, $infoKey='')
	{
		if ($infoKey) {
			return $infoKey;
		} else {
			return $message;
		}
	}
}

