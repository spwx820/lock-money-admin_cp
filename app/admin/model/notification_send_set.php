<?php
/**
 * 通知发送配置模型
 *
 * @category   Leb
 * @package    Leb_Model
 * @author 	lihui
 * @version   $Id: notification_send_set.php 1 2015-01-29 14:42 $
 * @copyright
 * @license
 */
class Notification_send_set extends Leb_Model
{
    protected $_pk = 'id';
    protected $_tableName = 'a_notification_send_set';
    protected $_daoType = false;

    //获取单条信息
    public function getSendSet($nId,$opt=array())
    {
        $where = " nid = '{$nId}'";
        if(isset($opt['status']) && is_numeric($opt['status'])){
            $where .= " AND status = ".$opt['status'];
        }
        if(isset($opt['condition']))
            $where .= $opt['condition'];

        $where = trim($where," AND");
        return $this->where($where)->find(array());
    }

    //获取统计数量
    public function getSendSetCount($nId,$opt=array())
    {
        $where = " nid = '{$nId}'";
        if(isset($opt['status']) && is_numeric($opt['status'])){
            $where .= " AND status = ".$opt['status'];
        }
        if(isset($opt['condition']))
            $where .= $opt['condition'];

        $where = trim($where," AND");
        return $this->where($where)->count();
    }

    //更新单条数据
    public function saveSendSet($sId,$opt)
    {
        if(empty($sId) || !is_numeric($sId)) return false;

        $where = " id = {$sId}";
        if(isset($opt['condition']))
            $where .= $opt['condition'];

        $where = trim($where," AND");
        $opt['utime'] = date("Y-m-d H:i:s",time());

//        $querystr = "UPDATE a_notification_send_set SET send_star = '{$opt['send_star']}' WHERE id = {$sId}";

//        return $this->query($querystr);

        return  $this->where($where)->save($opt);

    }

    //清空数据
    public function clearSendSet()
    {
        $opt['nid'] =  $opt['star_num'] =  $opt['end_num'] = $opt['send_star']= $opt['status'] = 0;
        $opt['utime'] = date("Y-m-d H:i:s",time());

        $where = " nid > 0 AND status = 1";

        $sendnum = $this->query("SELECT COUNT(id) FROM  a_notification_send_set ");



        for($i =1;$i<= intval($sendnum[0]["COUNT(id)"]);$i++){
            $querystr = "UPDATE a_notification_send_set SET nid = '{$opt['nid']}', star_num = '{$opt['star_num']}', end_num = '{$opt['end_num']}',send_star = '{$opt['send_star']}' , status = 0  WHERE id = {$i}";

            $this->query($querystr);
        }

        return;
//        return  $this->where($where)->save($opt);
    }

}













