<?php
/**
 * 错误码统计记录
 *
 * @category   Leb
 * @package    Leb_Model
 * @author 	lihui
 * @version   $Id: errorcode_log.php 1 2014-10-16 15:42 $
 * @copyright
 * @license
 */
class Errorcode_log extends Leb_Model
{
    protected $_pk = 'id';
    protected $_tableName = 't_errorcode_log';
    protected $_daoType = false;

    //获取记录列表
    public function getErrorCodeLogList($opt=array(),$page=1, $limit=20)
    {
        $where = '1';
        if(!empty($opt['id']))
            $where .= " AND id = '{$opt['id']}'";

        if(!empty($opt['errorcode']))
            $where .= " AND errorcode = '{$opt['errorcode']}'";

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

    public function getErrorCodeLogCount($opt=array())
    {
        $where = '1';
        if(!empty($opt['id']))
            $where .= " AND id = '{$opt['id']}'";

        if(!empty($opt['errorcode']))
            $where .= " AND errorcode = '{$opt['errorcode']}'";

        if(isset($opt['condition']))
            $where .= $opt['condition'];

        $where = trim($where," AND");
        return $this->where($where)->count();
    }

    //获取单条记录
    public function getErrorCodeLog($opt=array())
    {
        if(empty($opt)){
            return false;
        }
        $where = '';
        if(!empty($opt['id']))
            $where .= " AND id = '{$opt['id']}'";

        if(!empty($opt['errorcode']))
            $where .= " AND errorcode = '{$opt['errorcode']}'";

        $where = trim($where," AND");
        return $this->where($where)->find(array());
    }

    //添加记录
    public function addErrorCodeLog($opt)
    {
        if(empty($opt)) return false;
        return $this->add($opt);
    }

    //删除记录
    public function deleteErrorCodeLog($logId)
    {
        if(empty($logId)) return false;

        $where = " id = ".$logId;
        return  $this->where($where)->delete();
    }

}