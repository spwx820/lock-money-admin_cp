<?php
/**
 * 渠道统计配置操作
 *
 * @category   Leb
 * @package    Leb_Model
 * @author 	lihui
 * @version   $Id: channel_s_set.php 1 2014-09-24 14:35 $
 * @copyright
 * @license
 */
class Channel_s_set extends Leb_Model
{
    protected $_pk = 'id';
    protected $_tableName = 'a_channel_count_set';
    protected $_daoType = false;

    //获取统计配置数据
    public function getChannelSetList($opt=array(),$page=1, $limit=20)
    {
        $where = '1';
        if(!empty($opt['id']) )
            $where .= " AND id = '{$opt['id']}'";

        if(!empty($opt['channel'])){
            $where .= " AND channel = ".$opt['channel'];
        }

        if(isset($opt['status']) && is_numeric($opt['status'])){
            $where .= " AND status = ".$opt['status'];
        }

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
            $orderStr = "ctime desc";
        }

        $start = ($page-1) * $limit;
        if(!empty($where)){
            $where = trim($where," AND");
            return $this->where($where)->order($orderStr)->limit( "{$start}, {$limit}" )->select();
        }else{
            return $this->order($orderStr)->limit( "{$start}, {$limit}" )->select();
        }
    }

    //获取单条统计配置数据
    public function getChannelSet($opt=array())
    {
        if(empty($opt)){
            return false;
        }

        $where = '';
        if(!empty($opt['id']) )
            $where .= " AND id = '{$opt['id']}'";

        if(!empty($opt['channel'])){
            $where .= " AND channel = '{$opt['channel']}'";
        }

        if(isset($opt['status']) && is_numeric($opt['status'])){
            $where .= " AND status = ".$opt['status'];
        }

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

    //获取统计配置数量
    public function getChannelSetCount($opt=array())
    {
        $where = '1';
        if(!empty($opt['id']) )
            $where .= " AND id = '{$opt['id']}'";

        if(!empty($opt['channel'])){
            $where .= " AND channel = '{$opt['channel']}'";
        }

        if(isset($opt['status']) && is_numeric($opt['status'])){
            $where .= " AND status = ".$opt['status'];
        }

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

    //添加统计配置
    public function addChannelSet($opt=array())
    {
        if(empty($opt)) return false;
        return $this->add($opt);
    }

    //保存统计配置
    public function saveChannelSet($sId,$opt=array())
    {
        if(empty($sId) || !is_numeric($sId) || empty($opt)) return false;

        $where = " id = ".$sId;
        return  $this->where($where)->save($opt);
    }

}