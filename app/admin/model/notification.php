<?php
/**
 * 通知发送模型
 *
 * @category   Leb
 * @package    Leb_Model
 * @author 	lihui
 * @version   $Id: notification.php 1 2014-01-22 18:42 $
 * @copyright
 * @license
 */
class Notification extends Leb_Model
{
    protected $_pk = 'id';
    protected $_tableName = 'a_notification';
    protected $_daoType = false;

    //获取通知记录
    public function getNotificationList($opt=array(),$page=1, $limit=15)
    {
        $where = '1';
        if(!empty($opt['id']))
            $where .= " AND id = '{$opt['id']}'";

        if(!empty($opt['action']))
            $where .= " AND action = '{$opt['action']}'";

        if(is_numeric($opt['os_type']))
            $where .= " AND os_type = '{$opt['os_type']}'";

        if(is_numeric($opt['n_type']))
            $where .= " AND n_type = '{$opt['n_type']}'";

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

    //获取单条通知
    public function getNotification($opt=array())
    {
        if(empty($opt)){
            return false;
        }
        $where = '';
        if(!empty($opt['id']))
            $where .= " AND id = '{$opt['id']}'";

        if(!empty($opt['action']))
            $where .= " AND action = '{$opt['action']}'";

        if(is_numeric($opt['os_type']))
            $where .= " AND os_type = '{$opt['os_type']}'";

        if(is_numeric($opt['n_type']))
            $where .= " AND n_type = '{$opt['n_type']}'";

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

    //获取通知数量
    public function getNotificationCount($opt=array())
    {
        $where = '1';
        if(!empty($opt['id']))
            $where .= " AND id = '{$opt['id']}'";

        if(!empty($opt['action']))
            $where .= " AND action = '{$opt['action']}'";

        if(is_numeric($opt['os_type']))
            $where .= " AND os_type = '{$opt['os_type']}'";

        if(is_numeric($opt['n_type']))
            $where .= " AND n_type = '{$opt['n_type']}'";

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

    //添加通知
    public function addNotification($opt=array())
    {
        if(empty($opt)) return false;
        
        $opt['createtime'] = date("Y-m-d H:i:s",time());
        return $this->add($opt);
    }

    //保存通知
    public function saveNotification($nid,$opt=array())
    {
        if(empty($nid) || !is_numeric($nid) || empty($opt)) return false;

        $where = " id = ".$nid;
        $opt['updatetime'] = date("Y-m-d H:i:s",time());
        return  $this->where($where)->save($opt);
    }

    //删除通知
    public function delNotification($nid)
    {
        if(empty($nid)) return false;
        return  $this->where(" id='{$nid}'")->delete();
    }

    //审核成功
    public function auditSucceed($nId)
    {
        if(empty($nId) || !is_numeric($nId)) return false;
        $where = " status = 0 AND id = ".$nId;

        $opt['status'] = 1;
        $opt['updatetime'] = date("Y-m-d H:i:s",time());
        return  $this->where($where)->save($opt);
    }

    //处理成功
    public function disposeSucceed($nId)
    {
        if(empty($nId) || !is_numeric($nId)) return false;
        $where = " status = 1 AND id = ".$nId;

        $opt['status'] = 2;
        $opt['updatetime'] = date("Y-m-d H:i:s",time());
        return  $this->where($where)->save($opt);
    }

    //发送成功
    public function notificationSucceed($nId,$numStr='')
    {
        if(empty($nId) || !is_numeric($nId)) return false;
        $where = "id = ".$nId;

//        status = 2 AND

        if(!empty($numStr)){
            $opt['send_num'] = $numStr;
        }

        $opt['status'] = 3;
        $opt['updatetime'] = date("Y-m-d H:i:s",time());
        return  $this->where($where)->save($opt);
    }

    //发送失败
    public function notificationFail($nId,$wrongMsg='',$numStr='')
    {
        if(empty($nId) || !is_numeric($nId)) return false;
        $where = "id = ".$nId;
//        status = 2 AND

        if(!empty($wrongMsg)){
            $opt['wrong_msg'] = $wrongMsg;
        }
        if(!empty($numStr)){
            $opt['send_num'] = $numStr;
        }

        $opt['status'] = 4;
        $opt['updatetime'] = date("Y-m-d H:i:s",time());
        return  $this->where($where)->save($opt);
    }

}