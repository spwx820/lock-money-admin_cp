<?php
/**
 * 设备访问日志模型
 *
 * @category   Leb
 * @package    Leb_Model
 * @author 	 lihui
 * @version    $Id: device_log.php 1 2014-10-13 15:42 $
 * @copyright
 * @license
 */
class Device_log extends Leb_Model
{
    protected $_pk = 'id';
    protected $_tableName = 'z_device_log';
    protected $_daoType = false;

    function getDeviceCount($opt){
        $where = '1';
        if(!empty($opt['id']) )
            $where .= " AND id = '{$opt['id']}'";

        if(!empty($opt['device_id']))
            $where .= " AND device_id = '{$opt['device_id']}'";

        if(!empty($opt['channel']))
            $where .= " AND channel = '{$opt['channel']}'";

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

}