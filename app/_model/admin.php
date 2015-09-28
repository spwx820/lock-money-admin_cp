<?php
/**
 * 管理员数据读取
 *
 * @category   Leb
 * @package    Leb_Model
 * @author 	 lihui
 * @version    $Id: admin.php 1 2014-09-03 12:42 $
 * @copyright
 * @license
 */
class Admin extends Leb_Model
{
    protected $_pk = 'id';
    protected $_tableName = 'a_user';
    protected $_daoType = false;

    private $from = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    private $to   = 'B5kodHetMAz+r6DNFiu3Lv0TaXP1VUYqKEOGWwfQ=9I4p2hb7SylgsCcxJZmnjR8';
    const UINFO = 'zhuan_a_uinfo';
    const UKEY  = 'zhuan_a_uk';
    const IHOUSE_KEY = 'nn33DSQgqMd32CZo';  //私钥
    const COOKIE_EXPIRED =  7200;             //cookie生存时间

    //获取管理员信息
    public function getAdminList($opt=array(),$page=1, $limit=20)
    {
        $where = '';
        if(!empty($opt['id']))
            $where .= " AND id = '{$opt['id']}'";

        if(!empty($opt['email']))
            $where .= " AND email = '{$opt['email']}'";

        if(!empty($opt['password']))
            $where .= " AND password ='{$opt['password']}'";

        if(!empty($opt['group_id']) )
            $where .= " AND group_id ='{$opt['group_id']}'";

        if(!empty($opt['department_id']) )
            $where .= " AND department_id ='{$opt['department_id']}'";

        if(!empty($opt['is_del']))
            $where .= " AND is_del ='{$opt['is_del']}'";
        else
            $where .= " AND is_del ='0'";

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

        $start = ($page-1) * $limit;
        if(!empty($where)){
            $where = trim($where," AND");
            return $this->where($where)->order($orderStr)->limit( "{$start}, {$limit}" )->select();
        }else{
            return $this->order($orderStr)->limit( "{$start}, {$limit}" )->select();
        }
    }

    //获取管理员信息
    public function getAdminCount($opt=array())
    {
        $where = '';
        if(!empty($opt['id']))
            $where .= " AND id = '{$opt['id']}'";

        if(!empty($opt['email']))
            $where .= " AND email = '{$opt['email']}'";

        if(!empty($opt['password']))
            $where .= " AND password ='{$opt['password']}'";

        if(!empty($opt['group_id']) )
            $where .= " AND group_id ='{$opt['group_id']}'";

        if(!empty($opt['department_id']) )
            $where .= " AND department_id ='{$opt['department_id']}'";

        if(!empty($opt['is_pause']))
            $where .= " AND is_pause ='{$opt['is_pause']}'";

        if(!empty($opt['is_del']))
            $where .= " AND is_del ='{$opt['is_del']}'";
        else
            $where .= " AND is_del ='0'";

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

    //获取管理员信息
    public function getAdmin($opt=array())
    {
        if(empty($opt)){
            return false;
        }

        $where = '';
        if(!empty($opt['id']))
            $where .= " AND id = '{$opt['id']}'";

        if(!empty($opt['email']))
            $where .= " AND email = '{$opt['email']}'";

        if(!empty($opt['password']))
            $where .= " AND password ='{$opt['password']}'";

        if(!empty($opt['group_id']) )
            $where .= " AND group_id ='{$opt['group_id']}'";

        if(!empty($opt['department_id']) )
            $where .= " AND department_id ='{$opt['department_id']}'";

        if(!empty($opt['is_pause']))
            $where .= " AND is_pause ='{$opt['is_pause']}'";

        if(!empty($opt['is_del']))
            $where .= " AND is_del ='{$opt['is_del']}'";
        else
            $where .= " AND is_del ='0'";

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

    /**
     * 创建新账号
     */
    public function createAdmin($data)
    {
        $this->salt = $this->generateSalt();
        $this->password = $this->hashPassword($data['password'], $this->salt);
        $this->email  = $data['email'];
        $this->uname  = $data['truename'];
        $data['salt']= $this->salt;
        $data['password'] = $this->password;
        return $this->add($data);
    }

    //修改用户密码
    public function updatePassword($uid,$password)
    {
        if(empty($uid) || empty($password)) return false;
        return  $this->save(array("id"=>$uid,"password"=>$password));
    }

    /**
     * 删除指定管理员账号
     */
    public function removeUser($uid)
    {
        if(empty($uid)) return false;
        return  $this->save(array("id"=>$uid,"is_del"=>1));
    }

    /**
     * 恢复指定管理员账号
     */
    public function recoverUser($uid)
    {
        if(empty($uid)) return false;
        return  $this->save(array("id"=>$uid,"is_pause"=>0));
    }

    /**
     * 停用指定管理员账号
     */
    public function disableUser($uid)
    {
        if(empty($uid)) return false;
        return  $this->save(array("id"=>$uid,"is_pause"=>1));
    }

    /**
     * 锁定指定管理员账号
     */
    public function lockUser($uid)
    {
        if(empty($uid)) return false;
        return  $this->save(array("id"=>$uid,"is_pause"=>2));
    }

    /**
     * 返回随机加密串
     */
    static public function generateSalt()
    {
        return uniqid('',true);
    }

    /**
     * 返回密码hash值
     */
    static public function hashPassword($password, $salt)
    {
        return md5($salt.$password);
    }

    /**
     * 验证密码
     */
    public function validatePassword($password,$ypassword='',$ysalt='')
    {
        if(!empty($ypassword) && !empty($ysalt)){
            return self::hashPassword($password,$ysalt) ===$ypassword;
        }else{
            return self::hashPassword($password, $this->salt) === $this->password;
        }
    }
    /**
     * 登录
     */
    public function login($email, $password)
    {
        $model = $this->getUserByMail($email);
        if(!$model){
            return false;
        }

        $this->salt = $model['salt'];
        $this->password = $model['password'];
        if(!$this->validatePassword($password)){
            return false;
        }
        $getRealIp = get_real_ip();
        $obj = null;
        if($this->save(array('id'=>$model['id'], 'login_ip'=>$getRealIp, 'last_time'=>date('Y-m-d H:i:s')))){
            $uinfo = $this->encrypt(array('uid'=>$model['id'], 'uname'=>$model['truename'], 'last'=>$model['last_time']));
            $ukey = md5($uinfo.self::IHOUSE_KEY);
            $now = time()+self::COOKIE_EXPIRED;
            $host= $_SERVER['HTTP_HOST'];
            $obj = (setcookie(self::UINFO, $uinfo, $now, '/', $host) && setcookie(self::UKEY, $ukey, $now, '/', $host)) ? $this : null;
            if($obj){
                $this->uid    = $model['id'];
                $this->uname  = $model['truename'];
                $this->is_pause = $model['is_pause'];
                $this->last_time = $model['last_time'];
            }
        }
        return $obj;
    }

    //根据邮箱获取model对象
    public function getUserByMail($email)
    {
        return $this->find(array('where'=>" email='$email'"));
    }

    //根据uid获取model对象
    public function getUserById($uid)
    {
        if(empty($uid))
            return false;

        return $this->find(array('where'=>" id='$uid'"));
    }

    //验证邮箱账号是否存在
    static public function isExists($email)
    {
        $model = new self();
        return false !== $model->getUserByMail($email);
    }

    //返回账号是否登录
    public function isLogin()
    {
        $uinfo = isset($_COOKIE[self::UINFO]) ? $_COOKIE[self::UINFO] : null;
        $ukey  = isset($_COOKIE[self::UKEY]) ? $_COOKIE[self::UKEY] : null;
        if(!$uinfo || !$ukey){
            return false;
        }
        $key = md5($uinfo.self::IHOUSE_KEY);
        $isLogin = $ukey === $key;
        if($isLogin){
            $ar = $this->decrypt($uinfo);
            $this->uid   = $ar['uid'];
            $this->uname = $ar['uname'];
            $this->last_time = $ar['last'];

            $now = time()+self::COOKIE_EXPIRED;
            $host= $_SERVER['HTTP_HOST'];
            setcookie(self::UINFO, $uinfo, $now, '/', $host);
            setcookie(self::UKEY, $ukey, $now, '/', $host);
        }
        return $isLogin;
    }

    /**
     * 加密
     */
    public function encrypt($info=array())
    {
        $str = base64_encode(json_encode($info));
        $from = str_split($this->from);
        $to = str_split($this->to);
        $from = array_flip($from);

        $arr = str_split($str);
        foreach($arr as $k => $v){
            $arr[$k] = $to[$from[$v]];
        }
        return implode('', $arr);
    }

    //解密
    public function decrypt($str)
    {
        $from = str_split($this->from);
        $to = str_split($this->to);
        $to = array_flip($to);

        $arr = str_split($str);
        foreach($arr as $k => $v){
            $arr[$k] = $from[$to[$v]];
        }

        if(!$str = implode('', $arr))
            return '';
        if(!$str = base64_decode($str))
            return '';

        if(!$data = json_decode($str, true))
            return null;
        else
            return $data;
    }

    /**
     * 注销
     */
    public function logout()
    {
        $host= $_SERVER['HTTP_HOST'];
        setcookie(self::UINFO, '', 0, '/', $host);
        setcookie(self::UKEY, '', 0, '/', $host);
    }

}