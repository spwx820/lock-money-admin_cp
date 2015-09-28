<?php
/**
 * 消息发送
 *
 * @category   Leb
 * @package    Leb_Model
 * @author 	lihui
 * @version   $Id: message.php 1 2014-09-15 15:42 $
 * @copyright
 * @license
 */
class Message extends Leb_Model
{
    protected $_pk = 'id';
    protected $_tableName = 'a_message_send';
    protected $_daoType = false;

    //获取消息记录
    public function getMessageList($opt=array(),$page=1, $limit=20)
    {
        $where = '1';
        if(!empty($opt['id']))
            $where .= " AND id = '{$opt['id']}'";

        if(isset($opt['message_type']) && is_numeric($opt['message_type'])){
            $where .= " AND message_type = ".$opt['message_type'];
        }

        if(isset($opt['status']) && is_numeric($opt['status'])){
            $where .= " AND status = ".$opt['status'];
        }

        if(isset($opt['os_type']) && is_numeric($opt['os_type'])){
            $where .= " AND os_type = ".$opt['os_type'];
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

    //获取单条消息
    public function getMessage($opt=array())
    {
        if(empty($opt)){
            return false;
        }
        $where = '';
        if(!empty($opt['id']))
            $where .= " AND id = '{$opt['id']}'";

        if(isset($opt['message_type']) && is_numeric($opt['message_type'])){
            $where .= " AND message_type = ".$opt['message_type'];
        }

        if(isset($opt['status']) && is_numeric($opt['status'])){
            $where .= " AND status = ".$opt['status'];
        }

        if(isset($opt['os_type']) && is_numeric($opt['os_type'])){
            $where .= " AND os_type = ".$opt['os_type'];
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

    //获取消息数量
    public function getMessageCount($opt=array())
    {
        $where = '1';
        if(!empty($opt['id']))
            $where .= " AND id = '{$opt['id']}'";

        if(isset($opt['message_type']) && is_numeric($opt['message_type'])){
            $where .= " AND message_type = ".$opt['message_type'];
        }

        if(isset($opt['status']) && is_numeric($opt['status'])){
            $where .= " AND status = ".$opt['status'];
        }

        if(isset($opt['os_type']) && is_numeric($opt['os_type'])){
            $where .= " AND os_type = ".$opt['os_type'];
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

    //添加消息
    public function addMessage($opt=array())
    {
        if(empty($opt)) return false;
        $opt['createtime'] = date("Y-m-d H:i:s",time());
        return $this->add($opt);
    }

    //保存消息
    public function saveMessage($mId,$opt=array())
    {
        if(empty($mId) || !is_numeric($mId) || empty($opt)) return false;

        $where = " id = ".$mId;
        return $this->where($where)->save($opt);
    }

    //删除消息
    public function deleteMessage($mId)
    {
        if(empty($mId)) return false;
        return $this->where(" id='{$mId}'")->save(array("status"=>'-1'));
    }

    //审核成功
    public function messageauditSucceed($mId)
    {
        if(empty($mId) || !is_numeric($mId)) return false;

        $where = " status = 0 AND id = ".$mId;
        return $this->where($where)->save(array("status"=>'1'));
    }

    //处理成功
    public function disposeSucceed($mId)
    {
        if(empty($mId) || !is_numeric($mId)) return false;
        $where = " status = 1 AND id = ".$mId;

        $opt['status'] = 4;
        return $this->where($where)->save($opt);
    }

    //发送成功
    public function messageSucceed($mId,$callbackInfo='',$numStr='')
    {
        if(empty($mId) || !is_numeric($mId)) return false;

        $where = " status = 4 AND id = ".$mId;

        if(!empty($callbackInfo)){
            $saveOpt['callback_info'] = $callbackInfo;
        }
        if(!empty($numStr)){
            $saveOpt['send_num'] = $numStr;
        }

        $saveOpt['status'] = 2;
        return $this->where($where)->save($saveOpt);
    }

    //发送失败
    public function messageFail($mId,$callbackInfo='',$numStr='')
    {
        if(empty($mId) || !is_numeric($mId)) return false;

        $where = " status = 4 AND id = ".$mId;

        if(!empty($callbackInfo)){
            $saveOpt['callback_info'] = $callbackInfo;
        }
        if(!empty($numStr)){
            $saveOpt['send_num'] = $numStr;
        }

        $saveOpt['status'] = 3;
        return $this->where($where)->save($saveOpt);
    }

}