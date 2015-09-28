<?php
/**
 * 邮编检测插件
 *
 * @category   Leb
 * @package    Leb_Plugin
 * @author 	   liuxp
 * @version    $Id: zipcode.php 4501 2012-06-01 08:33:19Z guangzhao $
 * @copyright
 * @license
 */

class Leb_Validator_Zipcode extends Leb_Validator_Abstract
{
	/**
	 * 待定消息显示内容
	 *
	 * @var string
	 */
	static protected $_messages = array(
										'NOT' => '邮编格式不正确！'
										);

	/**
	 * 直接验证邮编的合法性
	 *
	 * @param mixed $value
	 * @param string $infoKey
	 * @return boolean|string
	 */
	static public function isValid($value, $minLength=0, $maxLength=32, $infoKey='')
	{
		 $regexp = '/^\d{6}$/';
        if (preg_match($regexp, $value)) {
            return true;
        }else{
        	$messages = self::$_messages['NOT'];
        }

        return self::getMessages($messages, $infoKey);
	}

}

