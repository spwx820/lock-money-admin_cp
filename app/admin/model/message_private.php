<?php
/**
 * 私有消息详细记录
 *
 * @category   Leb
 * @package    Leb_Model
 * @author 	lihui
 * @version   $Id: message_private.php 1 2014-09-15 15:42 $
 * @copyright
 * @license
 */
class Message_private extends Leb_Model
{
    protected $_pk = 'id';
    protected $_tableName = 'a_message_private';
    protected $_daoType = false;

    //获取消息记录
    public function getMessageList($opt=array(),$page=1, $limit=20)
    {
        $where = '1';
        if(!empty($opt['mid']))
            $where .= " AND mid = '{$opt['mid']}'";

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

    //获取消息记录
    public function getMessage($opt=array())
    {
        $where = '1';
        if(!empty($opt['mid']))
            $where .= " AND mid = '{$opt['mid']}'";

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

        $where = trim($where," AND");
        return $this->where($where)->find(array());
    }

    //获取消息数量
    public function getMessageCount($opt=array())
    {
        $where = '1';
        if(!empty($opt['mid']))
            $where .= " AND mid = '{$opt['mid']}'";

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

    //记录消息
    public function addMessage($opt=array())
    {
        if(empty($opt)) return false;
        $opt['createtime'] = date("Y-m-d H:i:s",time());
        return $this->add($opt);
    }

    //保存消息
    public function saveMessage($mId,$opt=array(),$uId=0)
    {
        if(empty($mId) || !is_numeric($mId) || empty($opt)) return false;

        $where = " mid = $mId";
        if(!empty($uId)){
            $where .= " AND uid= $uId";
        }
        return  $this->where($where)->save($opt);
    }

    //返回成功
    public function messageSucceed($mId)
    {
        if(empty($mId) || !is_numeric($mId)) return false;

        $where = " status = 0 AND id = ".$mId;
        return  $this->where($where)->save(array("status"=>'1'));
    }

    //返回失败
    public function messageFail($mId,$callbackInfo='')
    {
        if(empty($mId) || !is_numeric($mId)) return false;

        if(!empty($callbackInfo)){
            $saveOpt['callback_info'] = $callbackInfo;
        }
        $saveOpt['status'] = 2;

        $where = " status = 0 AND id = ".$mId;
        return  $this->where($where)->save($saveOpt);
    }

}