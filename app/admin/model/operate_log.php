<?php
/**
 * 后台操作日志
 *
 * @category   Leb
 * @package    Leb_Model
 * @author 	lihui
 * @version   $Id: operate_log.php 1 2014-11-14 19:35 $
 * @copyright
 * @license
 */
class Operate_log extends Leb_Model
{
    protected $_pk = 'id';
    protected $_tableName = 'a_operate_log';
    protected $_daoType = false;

    //获取操作记录
    public function getOpLogList($opt=array(),$page=1, $limit=20)
    {
        $where = '1';
        if(!empty($opt['id']))
            $where .= " AND id = '{$opt['id']}'";

        if(!empty($opt['operat']))
            $where .= " AND operat = '{$opt['operat']}'";

        //限定开始时间
        if(!empty($opt['start_time'])){
            $where .= " AND operatetime >= '{$opt['start_time']}'";
        }

        //限定结束时间
        if(!empty($opt['end_time'])){
            $where .= " AND operatetime <= '{$opt['end_time']}'";
        }

        if(isset($opt['condition']))
            $where .= $opt['condition'];

        if(!empty($opt['orderby'])){
            $orderStr =  $opt['orderby'];
        }else{
            $orderStr = "operatetime desc";
        }

        $start = ($page-1) * $limit;
        if(!empty($where)){
            $where = trim($where," AND");
            return $this->where($where)->order($orderStr)->limit( "{$start}, {$limit}" )->select();
        }else{
            return $this->order($orderStr)->limit( "{$start}, {$limit}" )->select();
        }
    }

    //获取单条操作记录
    public function getOpLog($opt=array())
    {
        if(empty($opt)){
            return false;
        }
        $where = '';
        if(!empty($opt['id']))
            $where .= " AND id = '{$opt['id']}'";

        if(!empty($opt['operat']))
            $where .= " AND operat = '{$opt['operat']}'";

        //限定开始时间
        if(!empty($opt['start_time'])){
            $where .= " AND operatetime >= '{$opt['start_time']}'";
        }

        //限定结束时间
        if(!empty($opt['end_time'])){
            $where .= " AND operatetime <= '{$opt['end_time']}'";
        }

        if(isset($opt['condition']))
            $where .= $opt['condition'];

        $where = trim($where," AND");
        return $this->where($where)->find(array());
    }

    //获取操作记录数量
    public function getOpLogCount($opt=array())
    {
        $where = '1';
        if(!empty($opt['id']))
            $where .= " AND id = '{$opt['id']}'";

        if(!empty($opt['operat']))
            $where .= " AND operat = '{$opt['operat']}'";

        //限定开始时间
        if(!empty($opt['start_time'])){
            $where .= " AND operatetime >= '{$opt['start_time']}'";
        }

        //限定结束时间
        if(!empty($opt['end_time'])){
            $where .= " AND operatetime <= '{$opt['end_time']}'";
        }

        if(isset($opt['condition']))
            $where .= $opt['condition'];

        $where = trim($where," AND");
        return $this->where($where)->count();
    }

    //添加操作记录
    public function addOpLog($opt=array())
    {
        if(empty($opt)) return false;
        $opt['operatetime'] = date("Y-m-d H:i:s",time());
        return $this->add($opt);
    }

}