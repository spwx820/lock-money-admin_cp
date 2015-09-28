<?php
/**
 * 上传包模型
 *
 * @category   Leb
 * @package    Leb_Model
 * @author 	lihui
 * @version   $Id: package_upload.php 1 2014-11-24 10:42 $
 * @copyright
 * @license
 */
class Package_upload extends Leb_Model
{
    protected $_pk = 'id';
    protected $_tableName = 'a_package_upload';
    protected $_daoType = false;

    //获取包记录
    public function getUpPackageList($opt=array(),$page=1, $limit=20)
    {
        $where = '1';
        if(!empty($opt['id']))
            $where .= " AND id = '{$opt['id']}'";

        if(!empty($opt['creater']))
            $where .= " AND creater = '{$opt['creater']}'";

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
    public function getUpPackage($opt=array())
    {
        if(empty($opt)){
            return false;
        }
        $where = '';
        if(!empty($opt['id']))
            $where .= " AND id = '{$opt['id']}'";

        if(!empty($opt['creater']))
            $where .= " AND creater = '{$opt['creater']}'";

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
    public function getUpPackageCount($opt=array())
    {
        $where = '1';
        if(!empty($opt['id']))
            $where .= " AND id = '{$opt['id']}'";

        if(!empty($opt['creater']))
            $where .= " AND creater = '{$opt['creater']}'";

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
    public function upPackage($opt=array())
    {
        if(empty($opt)) return false;
        $opt['createtime'] = date("Y-m-d H:i:s",time());
        return $this->add($opt);
    }

}