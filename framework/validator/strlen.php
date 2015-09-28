<?php
/**
 * 字符及长度校验
 *
 * 只允许数字与字母及_-
 *
 * @category   Leb
 * @package    Leb_Plugin
 * @author 	   liuxp
 * @version    $Id: strlen.php 4501 2012-06-01 08:33:19Z guangzhao $
 * @copyright
 * @license
 */

class Leb_Validator_strlen extends Leb_Validator_Abstract
{
	/**
	 * 待定消息显示内容
	 *
	 * @var string
	 */
	static protected $_messages = array(
										'NOT_STRING' => '非法字符，只允许数字与字母及_-',
										'LENGTH_TOO_LARGE' => '字符长度过长,当前字符长度为%d，最大允许长度为%d',
										'LENGTH_TOO_SHORT' => '字符长度过短，当前字符长度为%d，最少要%d个字符'
										);

	/**
	 * 验证是否字符串以及字符串长度
	 *
	 * @param mixed $value
	 * @param string $infoKey
	 * @return boolean|string
	 */
	static public function isValid($value, $minLength=0, $maxLength=100000, $infoKey='')
	{
		$reg = '/[a-zA-Z0-9_-]*/';
		if (!$maxLength) {
			$maxLength = 100000000;
		}
		if (preg_match($reg, $value)) {
			$currentLength = mb_strlen($value);
			if ($currentLength>$maxLength) {
				$messages = sprintf(self::$_messages['LENGTH_TOO_LARGE'], $currentLength, $maxLength);
			} elseif ($currentLength<$minLength) {
				$messages = sprintf(self::$_messages['LENGTH_TOO_SHORT'], $currentLength, $minLength);
			} else {
				return true;
			}
		} else {
			$messages = self::$_messages['NOT_STRING'];
		}

		return self::getMessages($messages, $infoKey);
	}

}

