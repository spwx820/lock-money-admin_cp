<?php
/**
 * 打包模型
 *
 * @category   Leb
 * @package    Leb_Model
 * @author 	lihui
 * @version   $Id: package.php 1 2014-09-30 10:42 $
 * @copyright
 * @license
 */
class Package extends Leb_Model
{
    protected $_pk = 'id';
    protected $_tableName = 'a_package_log';
    protected $_daoType = false;

    //获取打包记录
    public function getPackageList($opt=array(),$page=1, $limit=20)
    {
        $where = '1';
        if(!empty($opt['id']))
            $where .= " AND id = '{$opt['id']}'";

        if(isset($opt['uid']))
            $where .= " AND uid = '{$opt['uid']}'";

        if(isset($opt['pk_os']))
            $where .= " AND pk_os = '{$opt['pk_os']}'";

        if(isset($opt['channel_id']))
            $where .= " AND channel_id = '{$opt['channel_id']}'";

        if(!empty($opt['channel']))
            $where .= " AND channel = '{$opt['channel']}'";

        if(isset($opt['is_hidden_invite']))
            $where .= " AND is_hidden_invite = '{$opt['is_hidden_invite']}'";

        if(!empty($opt['creater']))
            $where .= " AND creater = '{$opt['creater']}'";

        //限定开始时间
        if(!empty($opt['start_time'])){
            $where .= " AND left(createtime,10) >= '{$opt['start_time']}'";
        }

        //限定结束时间
        if(!empty($opt['end_time'])){
            $where .= " AND left(createtime,10) <= '{$opt['end_time']}'";
        }

        if(isset($opt['STATUS']))
            $where .= " AND STATUS = '{$opt['STATUS']}'";

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

    //获取单条打包记录
    public function getPackage($opt=array())
    {
        if(empty($opt)){
            return false;
        }
        $where = '';
        if(!empty($opt['id']))
            $where .= " AND id = '{$opt['id']}'";

        if(isset($opt['uid']))
            $where .= " AND uid = '{$opt['uid']}'";

        if(isset($opt['pk_os']))
            $where .= " AND pk_os = '{$opt['pk_os']}'";

        if(isset($opt['channel_id']))
            $where .= " AND channel_id = '{$opt['channel_id']}'";

        if(!empty($opt['channel']))
            $where .= " AND channel = '{$opt['channel']}'";

        if(isset($opt['is_hidden_invite']))
            $where .= " AND is_hidden_invite = '{$opt['is_hidden_invite']}'";

        if(isset($opt['STATUS']))
            $where .= " AND STATUS = '{$opt['STATUS']}'";

        if(!empty($opt['creater']))
            $where .= " AND creater = '{$opt['creater']}'";

        //限定开始时间
        if(!empty($opt['start_time'])){
            $where .= " AND left(createtime,10) >= '{$opt['start_time']}'";
        }

        //限定结束时间
        if(!empty($opt['end_time'])){
            $where .= " AND left(createtime,10) <= '{$opt['end_time']}'";
        }

        if(isset($opt['condition']))
            $where .= $opt['condition'];

        $where = trim($where," AND");
        return $this->where($where)->find(array());
    }

    //获取打包数量
    public function getPackageCount($opt=array())
    {
        $where = '1';
        if(!empty($opt['id']))
            $where .= " AND id = '{$opt['id']}'";

        if(isset($opt['uid']))
            $where .= " AND uid = '{$opt['uid']}'";

        if(isset($opt['pk_os']))
            $where .= " AND pk_os = '{$opt['pk_os']}'";

        if(isset($opt['channel_id']))
            $where .= " AND channel_id = '{$opt['channel_id']}'";

        if(!empty($opt['channel']))
            $where .= " AND channel = '{$opt['channel']}'";

        if(isset($opt['is_hidden_invite']))
            $where .= " AND is_hidden_invite = '{$opt['is_hidden_invite']}'";

        if(!empty($opt['creater']))
            $where .= " AND creater = '{$opt['creater']}'";

        if(isset($opt['STATUS']))
            $where .= " AND STATUS = '{$opt['STATUS']}'";

        //限定开始时间
        if(!empty($opt['start_time'])){
            $where .= " AND left(createtime,10) >= '{$opt['start_time']}'";
        }

        //限定结束时间
        if(!empty($opt['end_time'])){
            $where .= " AND left(createtime,10) <= '{$opt['end_time']}'";
        }

        if(isset($opt['condition']))
            $where .= $opt['condition'];

        $where = trim($where," AND");
        return $this->where($where)->count();
    }

    //记录打包
    public function addPackage($opt=array())
    {
        if(empty($opt)) return false;
        $opt['createtime'] = date("Y-m-d H:i:s",time());
        return $this->add($opt);
    }

    //删除打包
    public function deletePackage($pid)
    {
        if(empty($pid)) return false;
        return  $this->where(" id='{$pid}'")->delete();
    }

}