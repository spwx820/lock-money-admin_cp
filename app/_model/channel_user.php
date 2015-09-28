<?php
/**
 * 渠道统计表操作
 *
 * @category   Leb
 * @package    Leb_Model
 * @author 	zw
 * @version   $Id: channel_count.php 1 2015-07-01 14:35 $
 * @copyright
 * @license
 */
class Channel_user extends Leb_Model
{
    protected $_pk = 'id';
    protected $_tableName = 't_channel_user';
    protected $_daoType = false;

    //获取统计数据

    public function addChannelCount($opt=array())
    {
        if(empty($opt)) return false;
        return $this->add($opt);
    }

}