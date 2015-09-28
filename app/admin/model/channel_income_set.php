<?php
/**
 * 渠道收益统计表配置操作
 *
 * @category   Leb
 * @package    Leb_Model
 * @author 	lihui
 * @version   $Id: channel_income_set.php 1 2015-04-28 19:35 $
 * @copyright
 * @license
 */
class Channel_income_set extends Leb_Model
{
    protected $_pk = 'id';
    protected $_tableName = 't_channel_income_set';
    protected $_daoType = false;

    //获取配置数据
    public function getCICSList($opt=array(),$page=1, $limit=20)
    {
        $where = '1';
        if(!empty($opt['id']))
            $where .= " AND id = '{$opt['id']}'";

        if(!empty($opt['channel'])){
            $where .= " AND channel = '{$opt['channel']}'";
        }

        if(!empty($opt['rdate'])){
            $where .= " AND rdate = '{$opt['rdate']}'";
        }

        if(isset($opt['status']) && is_numeric($opt['status'])){
            $where .= " AND status = ".$opt['status'];
        }

        //限定开始时间
        if(!empty($opt['start_time'])){
            $where .= " AND rdate >= '{$opt['start_time']}'";
        }

        //限定结束时间
        if(!empty($opt['end_time'])){
            $where .= " AND rdate <= '{$opt['end_time']}'";
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

    //获取单条配置数据
    public function getCICS($opt=array())
    {
        if(empty($opt)){
            return false;
        }

        $where = '';
        if(!empty($opt['id']))
            $where .= " AND id = '{$opt['id']}'";

        if(!empty($opt['channel'])){
            $where .= " AND channel = '{$opt['channel']}'";
        }

        if(!empty($opt['rdate'])){
            $where .= " AND rdate = '{$opt['rdate']}'";
        }

        if(isset($opt['status']) && is_numeric($opt['status'])){
            $where .= " AND status = ".$opt['status'];
        }

        //限定开始时间
        if(!empty($opt['start_time'])){
            $where .= " AND rdate >= '{$opt['start_time']}'";
        }

        //限定结束时间
        if(!empty($opt['end_time'])){
            $where .= " AND rdate <= '{$opt['end_time']}'";
        }

        if(isset($opt['condition']))
            $where .= $opt['condition'];

        $where = trim($where," AND");
        return $this->where($where)->find(array());
    }

    //获取配置数据数量
    public function getCICSC($opt=array())
    {
        $where = '1';
        if(!empty($opt['id']))
            $where .= " AND id = '{$opt['id']}'";

        if(!empty($opt['channel'])){
            $where .= " AND channel = '{$opt['channel']}'";
        }

        if(!empty($opt['rdate'])){
            $where .= " AND rdate = '{$opt['rdate']}'";
        }

        if(isset($opt['status']) && is_numeric($opt['status'])){
            $where .= " AND status = ".$opt['status'];
        }

        //限定开始时间
        if(!empty($opt['start_time'])){
            $where .= " AND rdate >= '{$opt['start_time']}'";
        }

        //限定结束时间
        if(!empty($opt['end_time'])){
            $where .= " AND rdate <= '{$opt['end_time']}'";
        }

        if(isset($opt['condition']))
            $where .= $opt['condition'];

        $where = trim($where," AND");
        return $this->where($where)->count();
    }

    //添加配置
    public function addCICS($opt=array())
    {
        if(empty($opt))
            return false;

        return $this->add($opt);
    }

    //删除配置
    public function deleteCICS($cid)
    {
        if(empty($cid)) return false;
        return  $this->where(" id='{$cid}'")->delete();
    }

}