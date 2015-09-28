<?php
/**
 * 登录验证码记录
 *
 * @category   Leb
 * @package    Leb_Model
 * @author 	lihui
 * @version   $Id: login_code.php 1 2014-09-30 14:35 $
 * @copyright
 * @license
 */
class Login_code extends Leb_Model
{
    protected $_pk = 'id';
    protected $_tableName = 'a_login_code';
    protected $_daoType = false;

    //获取验证码数量
    public function getCodeCount($opt=array())
    {
        $where = '1';
        if(!empty($opt['id']))
            $where .= " AND id = '{$opt['id']}'";

        if(!empty($opt['email'])){
            $where .= " AND email = '{$opt['email']}'";
        }

        if(isset($opt['status']) && is_numeric($opt['status'])){
            $where .= " AND status = ".$opt['status'];
        }

        if(!empty($opt['login_ip'])){
            $where .= " AND login_ip = '{$opt['login_ip']}'";
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

    //添加验证码
    public function addCode($opt=array())
    {
        if(empty($opt)) return false;
        return $this->add($opt);
    }

    public function delCode($email)
    {
        if(empty($email)) return false;

        $where = " email='{$email}'";
        return $this->where($where)->delete();
    }

}