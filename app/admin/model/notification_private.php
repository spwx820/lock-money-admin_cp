<?php
/**
 * 私有通知模型
 *
 * @category   Leb
 * @package    Leb_Model
 * @author 	lihui
 * @version   $Id: notification_private.php 1 2015-01-22 19:42 $
 * @copyright
 * @license
 */
class Notification_private extends Leb_Model
{
    protected $_pk = 'id';
    protected $_tableName = 'a_notification_private';
    protected $_daoType = false;

    //获取私有通知记录
    public function getNotificationList($opt=array(),$page=1, $limit=20)
    {
        if(empty($opt['nid'])) return false;

        $where = " nid = '{$opt['nid']}'";
        if(!empty($opt['uid']))
            $where .= " AND uid = '{$opt['uid']}'";

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

    //获取单条私有通知
    public function getNotification($opt=array())
    {
        if(empty($opt['nid'])) return false;

        $where = " nid = '{$opt['nid']}'";
        if(!empty($opt['uid']))
            $where .= " AND uid = '{$opt['uid']}'";

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

    //获取私有通知数量
    public function getNotificationCount($opt=array())
    {
        if(empty($opt['nid'])) return false;

        $where = " nid = '{$opt['nid']}'";
        if(!empty($opt['uid']))
            $where .= " AND uid = '{$opt['uid']}'";

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

    //添加私有通知
    public function addNotification($opt=array())
    {
        if(empty($opt) || empty($opt['nid'])) return false;

        $opt['createtime'] = $opt['updatetime'] = date("Y-m-d H:i:s",time());
        return $this->add($opt);
    }
    //保存私有通知
    public function saveNotification($nid,$opt=array())
    {
        if(empty($nid) || !is_numeric($nid) || empty($opt)) return false;

        $where = " nid = ".$nid;

        $opt['updatetime'] = date("Y-m-d H:i:s",time());
        return  $this->where($where)->save($opt);
    }

    //删除私有通知
    public function delNotification($nid)
    {
        if(empty($nid)) return false;
        return  $this->where(" nid='{$nid}'")->delete();
    }

    //审核成功
    public function auditSucceed($nId)
    {
        if(empty($nId) || !is_numeric($nId)) return false;

        $where = " status = 0 AND nid = ".$nId;

        $opt['status'] = 1;
        $opt['updatetime'] = date("Y-m-d H:i:s",time());
        return  $this->where($where)->save($opt);
    }

    //处理成功
    public function disposeSucceed($nId)
    {
        if(empty($nId) || !is_numeric($nId)) return false;

        $where = " status = 1 AND nid = ".$nId;

        $opt['status'] = 2;
        $opt['updatetime'] = date("Y-m-d H:i:s",time());
        return  $this->where($where)->save($opt);
    }

    //发送成功
    public function notificationSucceed($nId)
    {
        if(empty($nId) || !is_numeric($nId)) return false;

        $where = " status = 2 AND nid = ".$nId;

        $opt['status'] = 3;
        $opt['updatetime'] = date("Y-m-d H:i:s",time());
        return  $this->where($where)->save($opt);
    }

    public function notificationSucceedByUid($nId,$uId)
    {
        if(empty($nId) || !is_numeric($nId)) return false;
        if(empty($uId) || !is_numeric($uId)) return false;

        $where = " status = 2 AND nid = $nId AND uid = $uId";

        $opt['status'] = 3;
        $opt['updatetime'] = date("Y-m-d H:i:s",time());
        return  $this->where($where)->save($opt);
    }

    //发送失败
    public function notificationFail($nId,$wrongMsg='')
    {
        if(empty($nId) || !is_numeric($nId)) return false;

        $where = " status = 2 AND nid = ".$nId;

        $opt['status'] = 4;
        $opt['wrong_msg'] = $wrongMsg;
        $opt['updatetime'] = date("Y-m-d H:i:s",time());
        return  $this->where($where)->save($opt);
    }

    public function notificationFailByUid($nId,$uId,$wrongMsg='')
    {
        if(empty($nId) || !is_numeric($nId)) return false;
        if(empty($uId) || !is_numeric($uId)) return false;

        $where = " status = 2 AND nid = $nId AND uid = $uId";

        $opt['status'] = 4;
        $opt['wrong_msg'] = $wrongMsg;
        $opt['updatetime'] = date("Y-m-d H:i:s",time());
        return  $this->where($where)->save($opt);
    }

}