<?php
/**
 * 设备修改记录模型
 *
 * @category   Leb
 * @package    Leb_Model
 * @author 	lihui
 * @version   $Id: device_modify.php 1 2014-09-30 10:42 $
 * @copyright
 * @license
 */
class Device_modify extends Leb_Model
{
    protected $_pk = 'id';
    protected $_tableName = 'a_device_modify';
    protected $_daoType = false;

    //获取设备修改记录
    public function getDeviceModeifyList($opt=array(),$page=1, $limit=20)
    {
        $where = '1';
        if(!empty($opt['id']) )
            $where .= " AND id = '{$opt['id']}'";

        if(!empty($opt['uid']))
            $where .= " AND uid = '{$opt['uid']}'";

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

    //获取单条设备修改记录
    public function getDeviceModeify($opt=array())
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

    //获取设备修改数量
    public function getDeviceModeifyCount($opt=array())
    {
        $where = '1';
        if(!empty($opt['id']) )
            $where .= " AND id = '{$opt['id']}'";

        if(!empty($opt['uid']))
            $where .= " AND uid = '{$opt['uid']}'";

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

    //添加设备修改记录
    public function addDeviceModeify($opt=array())
    {
        if(empty($opt)) return false;
        $opt['createtime'] = date("Y-m-d H:i:s",time());
        return $this->add($opt);
    }

    //设备修改成功
    public function deviceModeifySucceed($dId)
    {
        if(empty($dId) || !is_numeric($dId)) return false;

        $where = " status = 0 AND id = ".$dId;
        return  $this->where($where)->save(array("status"=>'1'));
    }

}