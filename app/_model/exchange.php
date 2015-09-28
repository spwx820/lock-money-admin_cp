<?php
/**
 * 兑换记录读取
 *
 * @category   Leb
 * @package    Leb_Model
 * @author 	 lihui
 * @version    $Id: exchange.php 1 2014-08-13 15:42 $
 * @copyright
 * @license
 */
class Exchange extends Leb_Model
{
    protected $_pk = 'id';
    protected $_tableName = 'z_present_exchange';
    protected $_daoType = false;

    //获取兑换记录信息
    public function getExchangeList($opt = array(), $page = 1, $limit = 20)
    {
        $where = '';
        if (!empty($opt['id']))
            $where .= " AND id = '{$opt['id']}'";

        if (!empty($opt['present_id']))
            $where .= " AND present_id = '{$opt['present_id']}'";

        if (!empty($opt['uid']))
            $where .= " AND uid ='{$opt['uid']}'";

        if (!empty($opt['device_id']))
            $where .= " AND device_id ='{$opt['device_id']}'";

        if (!empty($opt['pay_content']))
            $where .= " AND pay_content ='{$opt['pay_content']}'";

        if (!empty($opt['ptype']))
            $where .= " AND ptype ='{$opt['ptype']}'";

        if (!empty($opt['admin']))
            $where .= " AND admin ='{$opt['admin']}'";

        if (!empty($opt['ip']))
            $where .= " AND ip ='{$opt['ip']}'";

        if (isset($opt['pay_status']))
        {
            $where .= " AND pay_status = " . $opt['pay_status'];
        }

        //限定开始时间
        if (!empty($opt['start_time']))
        {
            $where .= " AND ctime >= '{$opt['start_time']}'";
        }

        //限定结束时间
        if (!empty($opt['end_time']))
        {
            $where .= " AND ctime <= '{$opt['end_time']}'";
        }

        if (isset($opt['condition']))
            $where .= $opt['condition'];

        if (!empty($opt['orderby']))
        {
            $orderStr = $opt['orderby'];
        } else
        {
            $orderStr = "ctime desc";
        }

        $start = ($page - 1) * $limit;
        if (!empty($where))
        {
            $where = trim($where, " AND");
            return $this->where($where)->order($orderStr)->limit("{$start}, {$limit}")->select();
        } else
        {
            return $this->order($orderStr)->limit("{$start}, {$limit}")->select();
        }
    }

    //获取单条兑换记录
    public function getExchange($opt = array())
    {
        if (empty($opt))
        {
            return false;
        }
        $where = '';
        if (!empty($opt['id']))
            $where .= " AND id = '{$opt['id']}'";

        if (!empty($opt['present_id']))
            $where .= " AND present_id = '{$opt['present_id']}'";

        if (!empty($opt['uid']))
            $where .= " AND uid ='{$opt['uid']}'";

        if (!empty($opt['device_id']))
            $where .= " AND device_id ='{$opt['device_id']}'";

        if (!empty($opt['ptype']))
            $where .= " AND ptype ='{$opt['ptype']}'";

        if (!empty($opt['pay_content']))
            $where .= " AND pay_content ='{$opt['pay_content']}'";

        if (!empty($opt['admin']))
            $where .= " AND admin ='{$opt['admin']}'";

        if (!empty($opt['ip']))
            $where .= " AND ip ='{$opt['ip']}'";

        if (isset($opt['pay_status']))
        {
            $where .= " AND pay_status = " . $opt['pay_status'];
        }

        //限定开始时间
        if (!empty($opt['start_time']))
        {
            $where .= " AND ctime >= '{$opt['start_time']}'";
        }

        //限定结束时间
        if (!empty($opt['end_time']))
        {
            $where .= " AND ctime <= '{$opt['end_time']}'";
        }

        if (isset($opt['condition']))
            $where .= $opt['condition'];

        $where = trim($where, " AND");
        if (!empty($opt['orderby']))
        {
            return $this->where($where)->order($opt['orderby'])->find(array());
        } else
        {
            return $this->where($where)->find(array());
        }
    }

    //获取兑换记录数量
    public function getExchangeCount($opt = array())
    {
        $where = '1';
        if (!empty($opt['id']))
            $where .= " AND id = '{$opt['id']}'";

        if (!empty($opt['present_id']))
            $where .= " AND present_id = '{$opt['present_id']}'";

        if (!empty($opt['uid']))
            $where .= " AND uid ='{$opt['uid']}'";

        if (!empty($opt['device_id']))
            $where .= " AND device_id ='{$opt['device_id']}'";

        if (!empty($opt['ip']))
            $where .= " AND ip ='{$opt['ip']}'";

        if (!empty($opt['admin']))
            $where .= " AND admin ='{$opt['admin']}'";

        if (!empty($opt['pay_content']))
            $where .= " AND pay_content ='{$opt['pay_content']}'";

        if (!empty($opt['ptype']))
            $where .= " AND ptype ='{$opt['ptype']}'";

        if (isset($opt['pay_status']))
        {
            $where .= " AND pay_status = " . $opt['pay_status'];
        }

        //限定开始时间
        if (!empty($opt['start_time']))
        {
            $where .= " AND ctime >= '{$opt['start_time']}'";
        }

        //限定结束时间
        if (!empty($opt['end_time']))
        {
            $where .= " AND ctime <= '{$opt['end_time']}'";
        }

        if (isset($opt['condition']))
            $where .= $opt['condition'];

        $where = trim($where, " AND");
        return $this->where($where)->count();
    }

    //获取兑换记录金额
    public function getExchangeSum($opt = array())
    {
        $where = '';
        if (!empty($opt['id']))
            $where .= " AND id = '{$opt['id']}'";

        if (!empty($opt['present_id']))
            $where .= " AND present_id = '{$opt['present_id']}'";

        if (!empty($opt['uid']))
            $where .= " AND uid ='{$opt['uid']}'";

        if (!empty($opt['device_id']))
            $where .= " AND device_id ='{$opt['device_id']}'";

        if (!empty($opt['ip']))
            $where .= " AND ip ='{$opt['ip']}'";

        if (!empty($opt['admin']))
            $where .= " AND admin ='{$opt['admin']}'";

        if (!empty($opt['ptype']))
            $where .= " AND ptype ='{$opt['ptype']}'";

        if (isset($opt['pay_status']))
        {
            $where .= " AND pay_status = " . $opt['pay_status'];
        }

        //限定开始时间
        if (!empty($opt['start_time']))
        {
            $where .= " AND ctime >= '{$opt['start_time']}'";
        }

        //限定结束时间
        if (!empty($opt['end_time']))
        {
            $where .= " AND ctime <= '{$opt['end_time']}'";
        }

        if (isset($opt['condition']))
            $where .= $opt['condition'];

        $where = trim($where, " AND");
        return $this->where($where)->getField('SUM(pay)', $where);
    }

    //支付宝状态处理
    public function alipaySucceed($payId, $operat = '')
    {
        if (empty($payId) || !is_numeric($payId)) return false;

        $where = " pay_status = 1 AND id = " . $payId;
        if (!empty($operat))
        {
            $opt['admin'] = $operat;
        }
        $opt['pay_status'] = 2;
        return $this->where($where)->save($opt);
    }

    //支付审核
    public function alipayAudit($payId, $operat = '')
    {
        if (empty($payId) || !is_numeric($payId)) return false;

        $where = " pay_status = 2 AND id = " . $payId;
        if (!empty($operat))
        {
            $opt['admin'] = $operat;
        }
        $opt['pay_status'] = 3;
        return $this->where($where)->save($opt);
    }

    public function paySucceed($payId, $operat = '')
    {
        if (empty($payId) || !is_numeric($payId)) return false;

        $where = " pay_status = 1 AND id = " . $payId;
        if (!empty($operat))
        {
            $opt['admin'] = $operat;
        }
        $opt['pay_status'] = 2;
        return $this->where($where)->save($opt);
    }

    public function noPaySucceed($uid, $operat = '')
    {
        if (empty($uid) || !is_numeric($uid)) return false;

        $where = " pay_status = 1 AND uid = " . $uid;
        if (!empty($operat))
        {
            $opt['admin'] = $operat;
        }
        $opt['pay_status'] = -1;
        return $this->where($where)->save($opt);
    }

    //退款
    public function refundSucceed($payId, $remark = '', $operat = '')
    {
        if (empty($payId) || !is_numeric($payId)) return false;

        $where = " pay_status = 1 AND id = " . $payId;
        if (!empty($remark))
        {
            $opt['remark'] = $remark;
        }
        if (!empty($operat))
        {
            $opt['admin'] = $operat;
        }
        $opt['pay_status'] = 4;
        return $this->where($where)->save($opt);
    }

    //支付退款
    public function alipayRefund($payId, $remark = '', $operat = '')
    {
        if (empty($payId) || !is_numeric($payId)) return false;

        $where = " pay_status = 2 AND id = " . $payId;
        if (!empty($remark))
        {
            $opt['remark'] = $remark;
        }
        if (!empty($operat))
        {
            $opt['admin'] = $operat;
        }
        $opt['pay_status'] = 4;
        return $this->where($where)->save($opt);
    }

    //人工退款(已确认后的退款)
    public function artificialRefund($payId, $remark = '', $operat = '')
    {
        if (empty($payId) || !is_numeric($payId)) return false;

        $where = " (pay_status = 3 or pay_status = 2) AND id = " . $payId;
        if (!empty($remark))
        {
            $opt['remark'] = $remark;
        }
        if (!empty($operat))
        {
            $opt['admin'] = $operat;
        }
        $opt['pay_status'] = 7;
        return $this->where($where)->save($opt);
    }

}