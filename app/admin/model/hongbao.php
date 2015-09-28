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
class Hongbao extends Leb_Model
{
    protected $_pk = 'id';
    protected $_tableName = 'a_hongbao_send';
    protected $_daoType = false;

    //获取短信密码信息
    public function getHongbaoList($opt=array(),$page=1, $limit=20)
    {
        $where = '1';
        if(!empty($opt['id']) )
            $where .= " AND id = '{$opt['id']}'";

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

        $start = ($page-1) * $limit;
        if(!empty($where)){
            $where = trim($where," AND");
            return $this->where($where)->order($orderStr)->limit( "{$start}, {$limit}" )->select();
        }else{
            return $this->order($orderStr)->limit( "{$start}, {$limit}" )->select();
        }
    }

    //获取单个密码信息
    public function getHongbao($opt=array())
    {
        if(empty($opt)){
            return false;
        }
        $where = '';
        if(!empty($opt['id']) )
            $where .= " AND id = '{$opt['id']}'";

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
        return $this->where($where)->find(array());
    }

    //获取短信密码数量
    public function getHongbaoCount($opt=array())
    {
        $where = '1';
        if(!empty($opt['id']) )
            $where .= " AND id = '{$opt['id']}'";

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

    //添加密码记录
    public function addHongbao($opt=array())
    {
        if(empty($opt)) return false;
        $opt['createtime'] = date("Y-m-d H:i:s",time());
        return $this->add($opt);
    }

    //发送成功
    public function hongbaoSendSucceed($hId)
    {
        if(empty($hId) || !is_numeric($hId)) return false;

        $where = " status = 0 AND id = ".$hId;
        return  $this->where($where)->save(array("status"=>'1'));
    }

    //返回成功
    public function hongbaoSucceed($hId,$wrongMsg='')
    {
        if(empty($hId) || !is_numeric($hId)) return false;

        $where = " status = 1 AND id = ".$hId;
        return  $this->where($where)->save(array("status"=>'2','wrong_msg'=>$wrongMsg));
    }

    //返回失败
    public function hongbaoFail($hId,$wrongMsg='')
    {
        if(empty($hId) || !is_numeric($hId)) return false;

        $where = " id = ".$hId;
        return  $this->where($where)->save(array("status"=>'3','wrong_msg'=>$wrongMsg));
    }

}