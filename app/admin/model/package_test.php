<?php
/**
 * 测试包模型
 *
 * @category   Leb
 * @package    Leb_Model
 * @author 	lihui
 * @version   $Id: package_test.php 1 2014-12-29 10:42 $
 * @copyright
 * @license
 */
class Package_test extends Leb_Model
{
    protected $_pk = 'id';
    protected $_tableName = 'z_package_test';
    protected $_daoType = false;

    //获取包记录
    public function getTestPackageList($opt=array(),$page=1, $limit=20)
    {
        $where = '1';
        if(!empty($opt['id']))
            $where .= " AND id = '{$opt['id']}'";

        if(!empty($opt['pk_version'])){
            $where .= " AND pk_version = '{$opt['pk_version']}'";
        }

        if(isset($opt['pk_os'])){
            $where .= " AND pk_os = '{$opt['pk_os']}'";
        }

        if(isset($opt['status']) && is_numeric($opt['status'])){
            $where .= " AND status = ".$opt['status'];
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

    //获取单条包记录
    public function getTestPackage($opt=array())
    {
        if(empty($opt)){
            return false;
        }
        $where = '';
        if(!empty($opt['id']))
            $where .= " AND id = '{$opt['id']}'";

        if(!empty($opt['pk_version'])){
            $where .= " AND pk_version = '{$opt['pk_version']}'";
        }

        if(isset($opt['pk_os'])){
            $where .= " AND pk_os = '{$opt['pk_os']}'";
        }

        if(isset($opt['status']) && is_numeric($opt['status'])){
            $where .= " AND status = ".$opt['status'];
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
        return $this->where($where)->find(array());
    }

    //获取包数量
    public function getTestPackageCount($opt=array())
    {
        $where = '1';
        if(!empty($opt['id']))
            $where .= " AND id = '{$opt['id']}'";

        if(!empty($opt['pk_version'])){
            $where .= " AND pk_version = '{$opt['pk_version']}'";
        }

        if(isset($opt['pk_os'])){
            $where .= " AND pk_os = '{$opt['pk_os']}'";
        }

        if(isset($opt['status']) && is_numeric($opt['status'])){
            $where .= " AND status = ".$opt['status'];
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

    //记录包信息
    public function addPackage($opt=array())
    {
        if(empty($opt)) return false;
        $opt['createtime'] = date("Y-m-d H:i:s",time());
        return $this->add($opt);
    }

    //保存包信息
    public function savePackage($pId,$opt=array())
    {
        if(empty($pId) || !is_numeric($pId) || !isset($opt)) return false;

        $where = " id = ".$pId;
        $opt['updatetime'] = date("Y-m-d H:i:s",time());
        return  $this->where($where)->save($opt);
    }

    //开启包
    public function openPackage($pId)
    {
        if(empty($pId) || !is_numeric($pId)) return false;

        $where = " status = 0 AND id = ".$pId;
        $opt['updatetime'] = date("Y-m-d H:i:s",time());
        $opt['status'] = 1;
        return  $this->where($where)->save($opt);
    }

    //关闭包
    public function shutPackage($pId)
    {
        if(empty($pId) || !is_numeric($pId)) return false;

        $where = " status = 1 AND id = ".$pId;
        $opt['updatetime'] = date("Y-m-d H:i:s",time());
        $opt['status'] = 0;
        return  $this->where($where)->save($opt);
    }

}