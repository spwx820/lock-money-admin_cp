<?php
/**
 * 渠道收益统计表操作
 *
 * @category   Leb
 * @package    Leb_Model
 * @author 	lihui
 * @version   $Id: channel_income.php 1 2015-04-28 19:35 $
 * @copyright
 * @license
 */
class Channel_income extends Leb_Model
{
    protected $_pk = 'id';
    protected $_tableName = 't_channel_income';
    protected $_daoType = false;

    //获取统计数据
    public function getCICList($opt=array(),$page=1, $limit=20)
    {
        $where = '1';
        if(!empty($opt['id']))
            $where .= " AND id = '{$opt['id']}'";

        if(!empty($opt['ci_id']))
            $where .= " AND ci_id = '{$opt['ci_id']}'";

        if(!empty($opt['channel'])){
            $where .= " AND channel = '{$opt['channel']}'";
        }

        if(!empty($opt['rdate'])){
            $where .= " AND rdate = '{$opt['rdate']}'";
        }
        
        if(!empty($opt['cdate'])){
            $where .= " AND cdate = '{$opt['cdate']}'";
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

    //获取单条统计数据
    public function getCIC($opt=array())
    {
        if(empty($opt)){
            return false;
        }

        $where = '';
        if(!empty($opt['id']))
            $where .= " AND id = '{$opt['id']}'";

        if(!empty($opt['ci_id']))
            $where .= " AND ci_id = '{$opt['ci_id']}'";

        if(!empty($opt['channel'])){
            $where .= " AND channel = '{$opt['channel']}'";
        }

        if(!empty($opt['rdate'])){
            $where .= " AND rdate = '{$opt['rdate']}'";
        }

        if(!empty($opt['cdate'])){
            $where .= " AND cdate = '{$opt['cdate']}'";
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

    //获取统计数据数量
    public function getCICC($opt=array())
    {
        $where = '1';
        if(!empty($opt['id']))
            $where .= " AND id = '{$opt['id']}'";

        if(!empty($opt['ci_id']))
            $where .= " AND ci_id = '{$opt['ci_id']}'";

        if(!empty($opt['channel'])){
            $where .= " AND channel = '{$opt['channel']}'";
        }

        if(!empty($opt['rdate'])){
            $where .= " AND rdate = '{$opt['rdate']}'";
        }

        if(!empty($opt['cdate'])){
            $where .= " AND cdate = '{$opt['cdate']}'";
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

    //添加统计
    public function addCIC($opt=array())
    {
        if(empty($opt))
            return false;

        return $this->add($opt);
    }

}