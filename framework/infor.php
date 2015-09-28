<?php
/**
 * 信息提示类
 *
 * @category   Leb
 * @package    Leb_Bootstrap
 * @author 	   liuxp
 * @version    $Id: infor.php 4501 2012-06-01 08:33:19Z guangzhao $
 * @copyright
 * @license
 */

class Leb_Infor extends Leb_Plugin_Abstract
{
	public function show($msg, $url)
	{
		echo $msg;
		exit;
	}
}