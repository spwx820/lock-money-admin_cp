<?php
/**
 * 短信发送密码
 *
 * @category   Leb
 * @package    Leb_Model
 * @author 	lihui
 * @version   $Id: smspasswd.php 1 2014-09-15 15:42 $
 * @copyright
 * @license
 */
class Smspasswd extends Leb_Model
{
    protected $_pk = 'id';
    protected $_tableName = 'a_sms_passwd';
    protected $_daoType = false;

    //获取短信密码信息
    public function getPasswdList($opt=array(),$page=1, $limit=20)
    {
        $where = '';
        if(!empty($opt['id']))
            $where .= " AND id = '{$opt['id']}'";

        if(!empty($opt['mobile']))
            $where .= " AND mobile = '{$opt['mobile']}'";

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

    //获取单个密码信息
    public function getPasswd($opt=array())
    {
        if(empty($opt)){
            return false;
        }
        $where = '';
        if(!empty($opt['id']))
            $where .= " AND id = '{$opt['id']}'";

        if(!empty($opt['mobile']))
            $where .= " AND mobile = '{$opt['mobile']}'";

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

    //获取短信密码数量
    public function getPasswdCount($opt=array())
    {
        $where = '';
        if(!empty($opt['id']))
            $where .= " AND id = '{$opt['id']}'";

        if(!empty($opt['mobile']))
            $where .= " AND mobile = '{$opt['mobile']}'";

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

    //添加密码记录
    public function addPasswd($opt=array())
    {
        if(empty($opt)) return false;
        $opt['createtime'] = date("Y-m-d H:i:s",time());
        return $this->add($opt);
    }

    //发送成功修改状态
    public function passwdSendSucceed($passwdId)
    {
        if(empty($passwdId) || !is_numeric($passwdId)) return false;

        $where = " status = 0 AND id = ".$passwdId;
        return  $this->where($where)->save(array("status"=>'1'));
    }

    //发送失败修改状态
    public function passwdSendFail($passwdId,$wrongMsg='')
    {
        if(empty($passwdId) || !is_numeric($passwdId)) return false;

        $where = " status = 0 AND id = ".$passwdId;
        return  $this->where($where)->save(array('wrong_msg'=>$wrongMsg));
    }

}