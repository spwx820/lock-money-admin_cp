<?php
/**
 * iOSpush型
 *
 * @category   Leb
 * @package    Leb_Model
 * @author 	lihui
 * @version   $Id: push_ios.php 1 2015-01-21 10:42 $
 * @copyright
 * @license
 */
class Push_ios extends Leb_Model
{
    protected $_pk = 'id';
    protected $_tableName = 'z_push_ios';
    protected $_daoType = false;

    //获取Push记录
    public function getPushList($opt=array(),$page=1, $limit=20)
    {
        $where = '1';
        if(!empty($opt['id']))
            $where .= " AND id = '{$opt['id']}'";

        if(!empty($opt['uid']))
            $where .= " AND uid = '{$opt['uid']}'";

        if(!empty($opt['device_id']))
            $where .= " AND device_id = '{$opt['device_id']}'";

        //限定开始时间
        if(!empty($opt['start_time'])){
            $where .= " AND ctime >= '{$opt['start_time']}'";
        }

        //限定结束时间
        if(!empty($opt['end_time'])){
            $where .= " AND ctime <= '{$opt['end_time']}'";
        }

        if(isset($opt['condition']))
            $where .= $opt['condition'];

        if(!empty($opt['orderby'])){
            $orderStr =  $opt['orderby'];
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

    //获取单条Push记录
    public function getPush($opt=array())
    {
        if(empty($opt)){
            return false;
        }
        $where = '1';
        if(!empty($opt['id']))
            $where .= " AND id = '{$opt['id']}'";

        if(!empty($opt['uid']))
            $where .= " AND uid = '{$opt['uid']}'";

        if(!empty($opt['device_id']))
            $where .= " AND device_id = '{$opt['device_id']}'";

        //限定开始时间
        if(!empty($opt['start_time'])){
            $where .= " AND ctime >= '{$opt['start_time']}'";
        }

        //限定结束时间
        if(!empty($opt['end_time'])){
            $where .= " AND ctime <= '{$opt['end_time']}'";
        }

        if(isset($opt['condition']))
            $where .= $opt['condition'];

        $where = trim($where," AND");
        return $this->where($where)->find(array());
    }

    //获取Push数量
    public function getPusheCount($opt=array())
    {
        $where = '1';
        if(!empty($opt['id']))
            $where .= " AND id = '{$opt['id']}'";

        if(!empty($opt['uid']))
            $where .= " AND uid = '{$opt['uid']}'";

        if(!empty($opt['device_id']))
            $where .= " AND device_id = '{$opt['device_id']}'";

        //限定开始时间
        if(!empty($opt['start_time'])){
            $where .= " AND ctime >= '{$opt['start_time']}'";
        }

        //限定结束时间
        if(!empty($opt['end_time'])){
            $where .= " AND ctime <= '{$opt['end_time']}'";
        }

        if(isset($opt['condition']))
            $where .= $opt['condition'];

        $where = trim($where," AND");
        return $this->where($where)->count();
    }

}