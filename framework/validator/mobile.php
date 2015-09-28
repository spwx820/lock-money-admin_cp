<?php
/**
 * 数字检验器
 *
 * @category   Leb
 * @package    Leb_Plugin
 * @author 	   liuxp
 * @version    $Id: mobile.php 4501 2012-06-01 08:33:19Z guangzhao $
 * @copyright
 * @license
 */

class Plugin_Validator_Mobile extends Leb_Validator_Abstract
{
	/**
	 * 待定消息显示内容
	 *
	 * @var string
	 */
	static protected $_messages = array(
										'NOT' => '非法手机号！',
										'NOT_MOBILE' => '您给的手机号非移动手机号!'
										);

	/**
	 * 验证手机号是否移动手机号
	 *
	 * @param mixed $value
	 * @param string $infoKey
	 * @return boolean|string
	 */
	static public function isValid($value, $infoKey='')
	{
		if (ereg('^13[5-9]{1}[0-9]{8}$|^15[0-9]{9}$', $value)) {
			return true;
		} else {
			if ($infoKey) {
				return $infoKey;
			} else {
				return self::$_messages['NOT_MOBILE'];
			}
		}
	}

}

