<?php
/**
 * 公共通知模型，主数据库，用于插入、更新，每次推送前将数据库数据转移到从库中
 *
 * @category   Leb
 * @package    Leb_Model
 * @author 	lihui
 * @version   $Id: notification_public.php 1 2015-01-29 14:42 $
 * @copyright
 * @license
 */
class Notification_public_master extends Leb_Model
{
    protected $_pk = 'id';
    protected $_tableName = 'a_notification_public_master';
    protected $_daoType = false;

    //获取公共通知记录
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

    //获取单条公共通知
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

        if(!empty($opt['orderby'])){
            $orderStr =  $opt['orderby'];
        }else{
            $orderStr = "id desc";
        }

        $where = trim($where," AND");
        return $this->where($where)->order($orderStr)->find(array());
    }

    //获取公共通知数量
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

    //添加公共通知
    public function addNotification($opt=array())
    {
        if(empty($opt) || empty($opt['nid'])) return false;

        $opt['createtime'] = $opt['updatetime'] = date("Y-m-d H:i:s",time());
        return $this->add($opt);
    }


    //发送成功
    public function pushSucceed($uId)
    {
        if(empty($uId) || !is_numeric($uId)) return false;

        $where = "uid = $uId";

        $opt['status'] = 1;
        $opt['updatetime'] = date("Y-m-d H:i:s",time());


        return  $this->where($where)->save($opt);
    }

    //发送失败
    public function pushFail($nId,$wrongMsg='')
    {
        if(empty($nId) || !is_numeric($nId)) return false;

        $where = "uid = $nId";

        $opt['status'] = 2;
        $opt['wrong_msg'] = $wrongMsg;
        $opt['updatetime'] = date("Y-m-d H:i:s",time());



        return  $this->where($where)->save($opt);
    }

}