<?php
/**
 * 邀请暂缓日志表
 *
 * @category   Leb
 * @package    Leb_Model
 * @author 	lihui
 * @version   $Id: invite_hold_log.php 1 2014-10-10 10:42 $
 * @copyright
 * @license
 */
class Invite_hold_exception extends Leb_Model
{
    protected $_pk = 'uid';
    protected $_tableName = 'z_invite_hold_exception';
    protected $_daoType = false;


    //获取邀请暂缓记录
    public function getInviteHoldExceptionList($opt=array(),$page=1, $limit=20)
    {
        $where = '1';
        if(!empty($opt['uid']) )
            $where .= " AND uid = '{$opt['uid']}'";

        if(isset($opt['condition']))
            $where .= $opt['condition'];

        if(!empty($opt['orderby'])){
            $orderStr =  $opt['orderby'];
        }else{
            $orderStr = "uid desc";
        }

        $start = ($page-1) * $limit;
        if(!empty($where)){
            $where = trim($where," AND");
            return $this->where($where)->order($orderStr)->limit( "{$start}, {$limit}" )->select();
        }else{
            return $this->order($orderStr)->limit( "{$start}, {$limit}" )->select();
        }
    }

    //获取统计数量
    public function getInviteHoldExceptionCount($opt=array())
    {
        $where = '1';
        if(!empty($opt['uid']) )
            $where .= " AND uid = '{$opt['uid']}'";

        if(isset($opt['condition']))
            $where .= $opt['condition'];

        $where = trim($where," AND");
        return $this->where($where)->count();
    }

    //获取单条记录
    public function getInviteHoldException($uid)
    {
        if(empty($uid)){
            return false;
        }

        $where = " uid = '{$uid}'";
        if(isset($opt['condition']))
            $where .= $opt['condition'];

        $where = trim($where," AND");
        return $this->where($where)->find(array());
    }

    //添加记录
    public function addInviteHoldException($opt=array())
    {
        if(empty($opt)) return false;
        $opt['ctime'] = date("Y-m-d H:i:s",time());
        return $this->add($opt);
    }

    //删除记录
    public function deleteInviteHoldException($uid)
    {
        if(empty($uid)) return false;
        return  $this->where(" uid='{$uid}'")->delete();
    }

}