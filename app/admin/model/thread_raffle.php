<?php
/**
 * 帖子抽奖模型
 *
 * @category   Leb
 * @package    Leb_Model
 * @author 	lihui
 * @version   $Id: thread_raffle.php 1 2015-03-24 10:42 $
 * @copyright
 * @license
 */
class Thread_raffle extends Leb_Model
{
    protected $_pk = 'id';
    protected $_tableName = 'a_thread_raffle';
    protected $_daoType = false;

    //获取抽奖列表
    public function getRaffleList($opt=array(),$page=1, $limit=20)
    {
        $where = '1';
        if(!empty($opt['id']) )
            $where .= " AND id = '{$opt['id']}'";

        if(!empty($opt['tid']))
            $where .= " AND tid = '{$opt['tid']}'";

        if(isset($opt['is_images']) && is_numeric($opt['is_images'])){
            $where .= " AND is_images = ".$opt['is_images'];
        }

        //限定开始时间
        if(!empty($opt['start_time'])){
            $where .= " AND createtime >= '{$opt['start_time']}'";
        }

        //限定结束时间
        if(!empty($opt['end_time'])){
            $where .= " AND createtime <= '{$opt['end_time']}'";
        }

        if(isset($opt['condition']))
            $where .= $opt['condition'];

        if(!empty($opt['orderby'])){
            $orderStr =  $opt['orderby'];
        }else{
            $orderStr = "createtime desc";
        }

        $start = ($page-1) * $limit;
        if(!empty($where)){
            $where = trim($where," AND");
            return $this->where($where)->order($orderStr)->limit( "{$start}, {$limit}" )->select();
        }else{
            return $this->order($orderStr)->limit( "{$start}, {$limit}" )->select();
        }
    }

    //获取抽奖数量
    public function getRaffleCount($opt=array())
    {
        $where = '1';
        if(!empty($opt['id']) )
            $where .= " AND id = '{$opt['id']}'";

        if(!empty($opt['tid']))
            $where .= " AND tid = '{$opt['tid']}'";

        if(isset($opt['is_images']) && is_numeric($opt['is_images'])){
            $where .= " AND is_images = ".$opt['is_images'];
        }

        //限定开始时间
        if(!empty($opt['start_time'])){
            $where .= " AND createtime >= '{$opt['start_time']}'";
        }

        //限定结束时间
        if(!empty($opt['end_time'])){
            $where .= " AND createtime <= '{$opt['end_time']}'";
        }

        if(isset($opt['condition']))
            $where .= $opt['condition'];

        $where = trim($where," AND");
        return $this->where($where)->count();
    }

    //添加抽奖列表
    public function addRaffle($opt=array())
    {
        if(empty($opt)) return false;
        $opt['createtime'] = date("Y-m-d H:i:s",time());
        return $this->add($opt);
    }

}