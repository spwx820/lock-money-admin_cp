<?php
/**
 * 反馈模型
 *
 * @category   Leb
 * @package    Leb_Model
 * @author 	lihui
 * @version   $Id: package.php 1 2014-10-08 10:42 $
 * @copyright
 * @license
 */
class Feedback extends Leb_Model
{
    protected $_pk = 'id';
    protected $_tableName = 'z_user_report';
    protected $_daoType = false;

    //获取返回信息
    public function getFeedbackList($opt=array(),$page=1, $limit=20)
    {
        $where = '1';
        if(!empty($opt['id']) )
            $where .= " AND id = '{$opt['id']}'";

        if(!empty($opt['uid']))
            $where .= " AND uid = '{$opt['uid']}'";

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

    //获取单条返回信息
    public function getFeedback($opt=array())
    {
        if(empty($opt)){
            return false;
        }
        $where = '';
        if(!empty($opt['id']) )
            $where .= " AND id = '{$opt['id']}'";

        if(!empty($opt['uid']))
            $where .= " AND uid = '{$opt['uid']}'";

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

    //获取返回信息数量
    public function getFeedbackCount($opt=array())
    {
        $where = '1';
        if(!empty($opt['id']) )
            $where .= " AND id = '{$opt['id']}'";

        if(!empty($opt['uid']))
            $where .= " AND uid = '{$opt['uid']}'";

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