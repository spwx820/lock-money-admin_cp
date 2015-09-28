<?php
/**
 * 邮箱检查
 *
 * @category   Leb
 * @package    Leb_Plugin
 * @author 	   liuxp
 * @version    $Id: email.php 4501 2012-06-01 08:33:19Z guangzhao $
 * @copyright
 * @license
 */

class Plugin_Validator_Email extends Leb_Validator_Abstract
{
	/**
	 * 待定消息显示内容
	 *
	 * @var string
	 */
	static protected $_messages = array(
										'NOT' => '邮箱格式不正确!'
										);

	/**
	 * 直接验证邮箱的合法性
	 *
	 * @param mixed $value
	 * @param string $infoKey
	 * @return boolean|string
	 */
	static public function isValid($value, $infoKey='')
	{
		$regexp = '/^[\w\-\.]+@[\w\-]+(\.[\w\-]+)*(\.[a-z]{2,})$/';
        if (preg_match($regexp, $value)) {
            return true;
        }else{
        	$messages = self::$_messages['NOT'];
        }

        return self::getMessages($messages, $infoKey);
	}


}

