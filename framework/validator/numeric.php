<?php
/**
 * 数字检验器
 *
 * @category   Leb
 * @package    Leb_Plugin
 * @author 	   liuxp
 * @version    $Id: numeric.php 4501 2012-06-01 08:33:19Z guangzhao $
 * @copyright
 * @license
 */

class Leb_Validator_Numeric extends Leb_Validator_Abstract
{
	/**
	 * 待定消息显示内容
	 *
	 * @var string
	 */
	static protected $_messages = array(
										'NOT' => '不是数字！'
										);

	/**
	 * 验证某一个等式
	 *
	 * @param mixed $value
	 * @param string $infoKey
	 * @return boolean|string
	 */
	static public function isValid($value, $infoKey='')
	{
		if (is_numeric($value)) {
			return true;
		} else {
			if ($infoKey) {
				return $infoKey;
			} else {
				return self::$_messages['NOT'];
			}
		}
	}

}

