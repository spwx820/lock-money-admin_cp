<?php
/**
 * APP公用消息操作
 *
 * @category   Leb
 * @package    Leb_Model
 * @author 	lihui
 * @version   $Id: public_msg.php 1 2015-01-16 15:42 $
 * @copyright (c) 2015 dianjoy.com
 * @license
 */
class Public_msg extends Leb_Model
{
    protected $_pk = 'id';
    protected $_tableName = 'z_public_msg';
    protected $_daoType = false;

    //获取公用消息数据
    public function getMsgList($opt=array(),$page=1, $limit=20)
    {
        $where = '1';
        if(!empty($opt['id']))
            $where .= " AND id = '{$opt['id']}'";

        if(!empty($opt['os_type']))
            $where .= " AND os_type = '{$opt['os_type']}'";

        if(isset($opt['status']) && is_numeric($opt['status']))
            $where .= " AND status = ".$opt['status'];

        if(isset($opt['condition']))
            $where .= $opt['condition'];

        if(!empty($opt['orderby'])){
            $orderStr = $opt['orderby'];
        }else{
            $orderStr = "id desc";
        }
        $start = ($page-1) * $limit;

        if(!empty($where)){
            $where = trim($where," AND");
            return $this->where($where)->order($orderStr)->limit( "{$start}, {$limit}" )->select();
        }else{
            return $this->order($orderStr)->limit( "{$start}, {$limit}" )->select();
        }
    }

}