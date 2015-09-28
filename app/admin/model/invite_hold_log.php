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
class Invite_hold_log extends Leb_Model
{
    protected $_pk = 'id';
    protected $_tableName = 'z_invite_hold_log';
    protected $_daoType = false;

    //获取单个密码信息
    public function getInviteHoldLog($opt=array())
    {
        if(empty($opt)){
            return false;
        }
        $where = '';
        if(!empty($opt['id']))
            $where .= " AND id = '{$opt['id']}'";

        if(!empty($opt['uid']))
            $where .= " AND uid = '{$opt['uid']}'";

        if(isset($opt['condition']))
            $where .= $opt['condition'];

        $where = trim($where," AND");
        return $this->where($where)->find(array());
    }
}