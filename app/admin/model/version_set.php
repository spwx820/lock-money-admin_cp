<?php
/**
 * 版本配置操作
 *
 * @category   Leb
 * @package    Leb_Model
 * @author 	lihui
 * @version   $Id: version_set.php 1 2015-05-20 15:07 $
 * @copyright
 * @license
 */
class Version_set extends Leb_Model
{
    protected $_pk = 'id';
    protected $_tableName = 'z_version';
    protected $_daoType = false;

    //获取配置数据
    public function getVersionList($opt=array(),$page=1, $limit=20)
    {
        $where = '1';
        if(!empty($opt['id']) )
            $where .= " AND id = '{$opt['id']}'";

        if(!empty($opt['os_type'])){
            $where .= " AND os_type = '{$opt['os_type']}'";
        }

        if(!empty($opt['rate'])){
            $where .= " AND rate = '{$opt['rate']}'";
        }
        $where .= " AND app_id = 0";

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

    //获取单条配置数据
    public function getVersion($opt=array())
    {
        if(empty($opt)){
            return false;
        }
        $where = '';
        if(!empty($opt['id']) )
            $where .= " AND id = '{$opt['id']}'";

        if(!empty($opt['os_type'])){
            $where .= " AND os_type = '{$opt['os_type']}'";
        }
        $where .= " AND app_id = 0";

        if(!empty($opt['version'])){
            $where .= " AND version = '{$opt['version']}'";
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

    //获取配置数量
    public function getVersionCount($opt=array())
    {
        $where = '1';
        if(!empty($opt['id']) )
            $where .= " AND id = '{$opt['id']}'";

        if(!empty($opt['os_type'])){
            $where .= " AND os_type = '{$opt['os_type']}'";
        }
        $where .= " AND app_id = 0";

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

    //添加配置
    public function addVersion($opt=array())
    {
        if(empty($opt)) return false;

        $opt['app_id'] = 0;
        $opt['ctime']  = date("Y-m-d H:i:s",time());
        return $this->add($opt);
    }

    //保存配置
    public function saveVersion($vId,$opt=array())
    {
        if(empty($vId) || !is_numeric($vId) || empty($opt)) return false;

        $where = " app_id = 0 AND id = ".$vId;
        return  $this->where($where)->save($opt);
    }

    //审核
    public function auditVersion($vId)
    {
        if(empty($vId) || !is_numeric($vId)) return false;

        $where = " app_id = 0 AND status = 0 AND id = ".$vId;
        $opt['status'] = 1;
        return  $this->where($where)->save($opt);
    }

}