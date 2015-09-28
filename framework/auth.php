<?php
/**
 * 权限验证类
 *
 * @category   Leb
 * @package    Leb_Bootstrap
 * @author 	   liuxp
 * @version    $Id: auth.php 4501 2012-06-01 08:33:19Z guangzhao $
 * @copyright
 * @license
 */

class Leb_Auth extends Leb_Plugin_Abstract
{
    public function __construct()
    {
        parent::__construct();
    }
	public function is_valid()
	{
		return true;
	}
}