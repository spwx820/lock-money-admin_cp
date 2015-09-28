<?php
/**
 * 用户数据读取
 *
 * @category   Leb
 * @package    Leb_Model
 * @author 	lihui
 * @version   $Id: user.php 1 2014-08-13 15:42 $
 * @copyright
 * @license
 */
class User extends Leb_Model
{
	protected $_pk = 'uid';
	protected $_tableName = 'z_user';
    protected $_daoType = false;

    //获取单个用户信息
    public function getUserList($opt=array(),$page=1, $limit=20)
    {
        $where = '';
        if(!empty($opt['uid']))
            $where .= " AND uid = '{$opt['uid']}'";

        if(!empty($opt['pnum']))
            $where .= " AND pnum = '{$opt['pnum']}'";

        if(!empty($opt['password']))
            $where .= " AND password ='{$opt['password']}'";

        if(!empty($opt['channel']))
            $where .= " AND channel ='{$opt['channel']}'";

        if(!empty($opt['device_id']))
            $where .= " AND device_id ='{$opt['device_id']}'";

        if(!empty($opt['imsi']) )
            $where .= " AND imsi ='{$opt['imsi']}'";

        if(!empty($opt['os_type']) )
            $where .= " AND os_type ='{$opt['os_type']}'";

        if(!empty($opt['register_ip']) )
            $where .= " AND register_ip ='{$opt['register_ip']}'";

        if(!empty($opt['invite_code']) )
            $where .= " AND invite_code ='{$opt['invite_code']}'";

        if(isset($opt['status']) && is_numeric($opt['status'])){
            $where .= " AND status = ".$opt['status'];
        }else{
            $where .= " AND status > 0";
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
            $orderStr = "ctime desc";
        }

        $start = ($page-1) * $limit;
        if(!empty($where)){
            $where = trim($where," AND");
            return $this->where($where)->order($orderStr)->limit( "{$start}, {$limit}" )->select();
        }else{
            return $this->order($orderStr)->limit( "{$start}, {$limit}" )->select();
        }
    }

    //获取单个用户信息
	public function getUser($opt=array())
	{
        if(empty($opt)){
            return false;
        }

        $where = '';
        if(!empty($opt['uid']))
            $where .= " AND uid = '{$opt['uid']}'";

        if(!empty($opt['pnum']))
            $where .= " AND pnum = '{$opt['pnum']}'";

        if(!empty($opt['password']))
            $where .= " AND password ='{$opt['password']}'";

        if(!empty($opt['channel']))
            $where .= " AND channel ='{$opt['channel']}'";

        if(!empty($opt['device_id']))
            $where .= " AND device_id ='{$opt['device_id']}'";

        if(!empty($opt['imsi']) )
            $where .= " AND imsi ='{$opt['imsi']}'";

        if(!empty($opt['os_type']) )
            $where .= " AND os_type ='{$opt['os_type']}'";

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

    //获取单个用户信息
    public function getUserCount($opt=array())
    {
        $where = '';
        if(!empty($opt['uid']))
            $where .= " AND uid = '{$opt['uid']}'";

        if(!empty($opt['pnum']))
            $where .= " AND pnum = '{$opt['pnum']}'";

        if(!empty($opt['password']))
            $where .= " AND password ='{$opt['password']}'";

        if(!empty($opt['channel']))
            $where .= " AND channel ='{$opt['channel']}'";

        if(!empty($opt['device_id']))
            $where .= " AND device_id ='{$opt['device_id']}'";

        if(!empty($opt['imsi']) )
            $where .= " AND imsi ='{$opt['imsi']}'";

        if(!empty($opt['os_type']) )
            $where .= " AND os_type ='{$opt['os_type']}'";

        if(!empty($opt['register_ip']) )
            $where .= " AND register_ip ='{$opt['register_ip']}'";

        if(!empty($opt['invite_code']) )
            $where .= " AND invite_code ='{$opt['invite_code']}'";

        if(isset($opt['status']) && is_numeric($opt['status'])){
            $where .= " AND status = ".$opt['status'];
        }else{
            $where .= " AND status > 0";
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

    //添加用户
    public function addUser($opt=array())
    {
        if(empty($opt)) return false;
        return $this->add($opt);
    }

    //修改用户密码
    public function updatePassword($pnum,$password)
    {
        if(empty($pnum) || empty($password)) return false;

        $where = " pnum = ".$pnum;
        return  $this->where($where)->save(array("password"=>$password));
    }

    //修改用户设备号
    public function updateDeviceId($uid,$deviceId)
    {
        if(empty($uid) || empty($deviceId)) return false;

        $where = " uid = ".$uid;
        return  $this->where($where)->save(array("device_id"=>$deviceId));
    }

    //删除用户
    public function deleteUser($uid)
    {
//        if(empty($uid)) return false;
//        return  $this->save(array("uid"=>$uid,"status"=>2));
    }

    //打款用户
    public function playUser($uid)
    {
        if(empty($uid) || !is_numeric($uid)) return false;
        return  $this->save(array("uid"=>$uid,"status"=>1));
    }

    public function noPlayUser($uid)
    {
        if(empty($uid) || !is_numeric($uid)) return false;
        return  $this->save(array("uid"=>$uid,"status"=>2));
    }

    //获取错误信息
    public function getErrorMsg($reason) {
        $errors = array (
            '10000' => 'unknown error',
            '10010' => 'data empty',
            '10011' => 'mobile num empty',
            '10012' => 'device error',
            '10013' => 'imsi error',
            '10015' => 'user error',
            '10014' => 'database error',
            '10020' => 'password is empty',
            '10021' => 'verification code is empty',
            '10022' => 'password error',
            '10023' => 'verification code error',
            '10024' => 'number limit',
            '10025' => 'sms sent fail',
        );
        if (array_key_exists($reason,$errors)){
            return $errors[$reason];
        }else{
            return $errors['10000'];
        }
    }

}