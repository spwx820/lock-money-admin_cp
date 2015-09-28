<?php
/**
 * 兑换暂缓标记记录
 *
 * @category   Leb
 * @package    Leb_Model
 * @author 	 lihui
 * @version    $Id: exchange_hold.php 1 2014-08-13 15:42 $
 * @copyright
 * @license
 */
class Exchange_hold extends Leb_Model
{
    protected $_pk = 'id';
    protected $_tableName = 'z_present_exchange_hold';
    protected $_daoType = false;

    //获取单条记录
    public function getExchangeH($opt=array())
    {
        if(empty($opt)){
            return false;
        }
        $where = '';
        if(!empty($opt['id']))
            $where .= " AND id = '{$opt['id']}'";

        if(!empty($opt['exchange_id']))
            $where .= " AND exchange_id = '{$opt['exchange_id']}'";

        $where = trim($where," AND");
        return $this->where($where)->find(array());
    }

    //添加记录
    public function addExchangeH($opt)
    {
        if(empty($opt)) return false;
        return $this->add($opt);
    }

    //保存记录
    public function saveExchangeH($exchangeId,$remark)
    {
        if(empty($exchangeId)){
            return false;
        }
        $where = " exchange_id = '{$exchangeId}'";
        return  $this->where($where)->save(array("remark"=>$remark));
    }

    //删除记录
    public function deleteExchangeH($exchange_id)
    {
        if(empty($exchange_id)) return false;

        $where = " exchange_id = ".$exchange_id;
        return  $this->where($where)->delete();
    }

}