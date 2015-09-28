<?php
/**
 * 收益统计模型(按天统计)
 *
 * @category   Leb
 * @package    Leb_Model
 * @author 	lihui
 * @version   $Id: income_day.php 1 2014-11-27 16:42 $
 * @copyright
 * @license
 */
class Income_day extends Leb_Model
{
    protected $_pk = 'id';
    protected $_tableName = 't_income_count_day';
    protected $_daoType = false;

    //获取统计信息
    public function getIncomeDayList($opt=array(),$page=1, $limit=20)
    {
        $where = '1';
        if(!empty($opt['id']) )
            $where .= " AND id = '{$opt['id']}'";

        if(!empty($opt['cdate']))
            $where .= " AND cdate = '{$opt['cdate']}'";

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

    //获取统计数量
    public function getIncomeDayCount($opt=array())
    {
        $where = '1';
        if(!empty($opt['id']) )
            $where .= " AND id = '{$opt['id']}'";

        if(!empty($opt['cdate']))
            $where .= " AND cdate = '{$opt['cdate']}'";

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

    //记录统计信息
    public function addIncomeDay($opt=array())
    {
        if(empty($opt)) return false;
        $opt['ctime'] = date("Y-m-d H:i:s",time());
        return $this->add($opt);
    }

}