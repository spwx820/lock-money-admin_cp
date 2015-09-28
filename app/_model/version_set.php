<?php
/**
 * 用户数据读取
 *
 * @category   Leb
 * @package    Leb_Model
 * @author 	lihui
 * @version    $Id: version_set.php 1 2015-05-12 15:42 $
 * @copyright
 * @license
 */
class Version_set extends Leb_Model
{
    protected $_pk = 'id';
    protected $_tableName = 'z_version';
    protected $_daoType = false;

    //获取iOS版本
    public function getIosVersion()
    {
        return $this->where(" os_type='ios' AND app_id=0")->order("id desc")->find(array());
    }
}