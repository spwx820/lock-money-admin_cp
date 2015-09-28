<?php
/**
 * 渠道统计表操作
 *
 * @category   Leb
 * @package    Leb_Model
 * @author 	lihui
 * @version   $Id: channel_count.php 1 2014-09-24 14:35 $
 * @copyright
 * @license
 */
class Channel_count extends Leb_Model
{
    protected $_pk = 'id';
    protected $_tableName = 't_channel_count';
    protected $_daoType = false;

    //获取统计数据
    public function getChannelCountList($opt = array(), $page = 1, $limit = 20)
    {
        $where = '';
        if (!empty($opt['id']))
            $where .= " AND id = '{$opt['id']}'";

        if (!empty($opt['channel']))
        {
            $where .= " AND channel = '{$opt['channel']}'";
        }

        if (!empty($opt['cdate']))
        {
            $where .= " AND cdate = '{$opt['cdate']}'";
        }

        if (isset($opt['status']) && is_numeric($opt['status']))
        {
            $where .= " AND status = " . $opt['status'];
        }

        //限定开始时间
        if (!empty($opt['start_time']))
        {
            $where .= " AND cdate >= '{$opt['start_time']}'";
        }

        //限定结束时间
        if (!empty($opt['end_time']))
        {
            $where .= " AND cdate <= '{$opt['end_time']}'";
        }

        if (isset($opt['condition']))
            $where .= $opt['condition'];

        if (!empty($opt['orderby']))
        {
            $orderStr = $opt['orderby'];
        } else
        {
            $orderStr = "ctime desc, channel";
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

    //获取单条统计数据
    public function getChannelCount($opt = array())
    {
        if (empty($opt))
        {
            return false;
        }

        $where = '';
        if (!empty($opt['id']))
            $where .= " AND id = '{$opt['id']}'";

        if (!empty($opt['channel']))
        {
            $where .= " AND channel = '{$opt['channel']}'";
        }

        if (!empty($opt['cdate']))
        {
            $where .= " AND cdate = '{$opt['cdate']}'";
        }

        if (isset($opt['status']) && is_numeric($opt['status']))
        {
            $where .= " AND status = " . $opt['status'];
        }

        //限定开始时间
        if (!empty($opt['start_time']))
        {
            $where .= " AND cdate >= '{$opt['start_time']}'";
        }

        //限定结束时间
        if (!empty($opt['end_time']))
        {
            $where .= " AND cdate <= '{$opt['end_time']}'";
        }

        if (isset($opt['condition']))
            $where .= $opt['condition'];

        $where = trim($where, " AND");
        return $this->where($where)->find(array());
    }

    //获取统计数据数量
    public function getChannelCountC($opt = array())
    {
        $where = '1';
        if (!empty($opt['id']))
            $where .= " AND id = '{$opt['id']}'";

        if (!empty($opt['channel']))
        {
            $where .= " AND channel = '{$opt['channel']}'";
        }

        if (!empty($opt['cdate']))
        {
            $where .= " AND cdate = '{$opt['cdate']}'";
        }

        if (isset($opt['status']) && is_numeric($opt['status']))
        {
            $where .= " AND status = " . $opt['status'];
        }

        //限定开始时间
        if (!empty($opt['start_time']))
        {
            $where .= " AND cdate >= '{$opt['start_time']}'";
        }

        //限定结束时间
        if (!empty($opt['end_time']))
        {
            $where .= " AND cdate <= '{$opt['end_time']}'";
        }

        if (isset($opt['condition']))
            $where .= $opt['condition'];

        $where = trim($where, " AND");
        return $this->where($where)->count();
    }

    //添加统计
    public function addChannelCount($opt = array())
    {
        if (empty($opt)) return false;
        return $this->add($opt);
    }

}