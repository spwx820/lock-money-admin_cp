<?php
/**
 * URL合法性检查
 *
 * @category   Leb
 * @package    Leb_Plugin
 * @author 	   liuxp
 * @version    $Id: url.php 4501 2012-06-01 08:33:19Z guangzhao $
 * @copyright
 * @license
 */

class Plugin_Validator_Url extends Leb_Validator_Abstract
{
	/**
	 * 待定消息显示内容
	 *
	 * @var string
	 */
	static protected $_messages = array(
										'NOT' => '网址格式不对!'
										);

	/**
	 * 直接验证网址的合法性
	 *
	 * @param mixed $value
	 * @param string $infoKey
	 * @return boolean|string
	 */
	static public function isValid($value, $infoKey='')
	{
	    $regex = "((https?|ftp)\:\/\/)?"; // SCHEME
	    $regex .= "([a-z0-9+!*(),;?&=\$_.-]+(\:[a-z0-9+!*(),;?&=\$_.-]+)?@)?"; // User and Pass
	    $regex .= "([a-z0-9-.]*)\.([a-z]{2,3})"; // Host or IP
	    $regex .= "(\:[0-9]{2,5})?"; // Port
	    $regex .= "(\/([a-z0-9+\$_-]\.?)+)*\/?"; // Path
	    $regex .= "(\?[a-z+&\$_.-][a-z0-9;:@&%=+\/\$_.-]*)?"; // GET Query
	    $regex .= "(#[a-z_.-][a-z0-9+\$_.-]*)?"; // Anchor
        if (preg_match("/^$regex$/", $value)) {
            return true;
        } else {
        	$messages = self::$_messages['NOT'];
        }

        return self::getMessages($messages, $infoKey);
	}


}

