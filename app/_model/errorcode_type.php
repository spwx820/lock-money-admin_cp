<?php
/**
 * 错误码类别记录
 *
 * @category   Leb
 * @package    Leb_Model
 * @author 	lihui
 * @version   $Id: errorcode_type.php 1 2014-10-20 15:42 $
 * @copyright
 * @license
 */
class Errorcode_type extends Leb_Model
{
    protected $_pk = 'id';
    protected $_tableName = 't_errorcode_type';
    protected $_daoType = false;

    //获取记录列表
    public function getErrorCodeTypeList($opt=array(),$page=1, $limit=20)
    {
        $where = '1';
        if(!empty($opt['id']))
            $where .= " AND id = '{$opt['id']}'";

        if(!empty($opt['errorcode']))
            $where .= " AND errorcode = '{$opt['errorcode']}'";

        if(!empty($opt['type_name']))
            $where .= " AND type_name = '{$opt['type_name']}'";

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

    public function getErrorCodeTypeCount($opt=array())
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
    public function getErrorCodeType($opt=array())
    {
        if(empty($opt)){
            return false;
        }
        $where = '';
        if(!empty($opt['id']))
            $where .= " AND id = '{$opt['id']}'";

        if(!empty($opt['errorcode']))
            $where .= " AND errorcode = '{$opt['errorcode']}'";

        if(!empty($opt['type_name']))
            $where .= " AND type_name = '{$opt['type_name']}'";

        $where = trim($where," AND");
        return $this->where($where)->find(array());
    }

    //添加记录
    public function addErrorCodeType($opt)
    {
        if(empty($opt)) return false;
        return $this->add($opt);
    }

}