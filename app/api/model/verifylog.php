<?php
/**
 * 验证日志模型
 *
 * @category   Leb
 * @package    Leb_Model
 * @author 	lihui
 * @version    $Id: verifylog.php 1 2014-08-13 18:21 $
 * @copyright
 * @license
 */
class Verifylog extends Leb_Model
{
    protected $_pk = 'id';
    protected $_tableName = 'z_verify_log';
    protected $_daoType = false;

    //获取单个日志
    public function getVerifylog($opt)
    {
        if(empty($opt['mobile']))
            return false;

        $where = '';
        if(!empty($opt['mobile']))
            $where .= " AND mobile = '{$opt['mobile']}'";

        if(!empty($opt['status']) && is_numeric($opt['status']))
            $where .= " AND status = ".$opt['status'];
        else{
            $where .= " AND status = 1";
        }

        //限定开始时间
        if(!empty($opt['start_time'])){
            $where .= " AND ctime >= '{$opt['start_time']}'";
        }

        //限定结束时间
        if(!empty($opt['end_time'])){
            $where .= " AND ctime <= '{$opt['end_time']}'";
        }
        $where = trim($where," AND");
        return $this->where($where)->order('id desc')->find(array());
    }

    //获取日志数量
    public function getVerifylogCount($opt= array())
    {
        if(empty($opt['mobile']))
            return false;

        $where = '';
        if(!empty($opt['mobile']))
            $where .= " AND mobile = '{$opt['mobile']}'";

        if(!empty($opt['code']))
            $where .= " AND code = '{$opt['code']}'";

        if(!empty($opt['status']) && is_numeric($opt['status']))
            $where .= " AND status = ".$opt['status'];

        //限定开始时间
        if(!empty($opt['start_time'])){
            $where .= " AND ctime >= '{$opt['start_time']}'";
        }

        //限定结束时间
        if(!empty($opt['end_time'])){
            $where .= " AND ctime <= '{$opt['end_time']}'";
        }

        $where = trim($where," AND");
        return $this->where($where)->count('*');
    }

    //添加日志
    public function addVerifylog($param= array())
    {
        if(empty($param)) return false;
        return $this->add($param);
    }

    //保存日志
    public function saveVerifylog($param,$where)
    {
        if(empty($param) || empty($where))
            return false;

        return $this->where($where)->save($param,array());
    }

}