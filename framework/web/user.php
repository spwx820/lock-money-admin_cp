<?php
/**
 * 用户类
 *
 * @category   Leb
 * @package    Leb_Plugin
 * @author 	   ziyuan 
 * @version    $Id: user.php 1 2011-04-08 07:42:35Z ziyuan $
 * @copyright
 * @license
 */

class Leb_User
{
    private $_id;
    private $_name;

    public function __construct()
    {
        $this->_id = null;
        $this->_name = '';
    }

    public function getName()
    {
        return $this->_name;
    }

    public function getId()
    {
        return $this->_id;
    }

    public function setId($id)
    {
        $this->_id = $id;
    }

    public function setName($name)
    {
        $this->_name = $name;
    }

    public function isGuest()
    {
        return null === $this->_id;
    }

    public function checkAccess($item)
    {
    }

    public function isLogin()
    {
        return null !== $this->_id; 
    }
}
