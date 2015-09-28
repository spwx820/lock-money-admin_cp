<?php
/**
 * 广告数据操作
 *
 * @category   Leb
 * @package    Leb_Model
 * @author 	lihui
 * @version   $Id: ad_operate.php 1 2014-09-15 15:42 $
 * @copyright
 * @license
 */
class Ad_operate extends Leb_Model
{
    protected $_pk = 'id';
    protected $_tableName = 'z_ad';
    protected $_daoType = false;

    //获取广告数据
    public function getAdList($opt=array(),$page=1, $limit=20)
    {
        $where = '1';
        if(!empty($opt['id']))
            $where .= " AND id = '{$opt['id']}'";

        if(!empty($opt['ad_id'])){
            $where .= " AND ad_id = '{$opt['ad_id']}'";
        }

        if(!empty($opt['title'])){
            $where .= " AND name like '%{$opt['title']}%'";
        }

        if(!empty($opt['action'])){
            $where .= " AND action = '{$opt['action']}'";
        }

        if(isset($opt['z_status']) && is_numeric($opt['z_status'])){
            $where .= " AND z_status = ".$opt['z_status'];
        }

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

        if(!empty($opt['orderby'])){
            $orderStr = $opt['orderby'];
        }else{
            $orderStr = "ctime desc";
        }

        $start = ($page-1) * $limit;
        if(!empty($where)){
            $where = trim($where," AND");
            return $this->where($where)->order($orderStr)->limit( "{$start}, {$limit}" )->select();
        }else{
            return $this->order($orderStr)->limit( "{$start}, {$limit}" )->select();
        }
    }

    //获取单个广告数据
    public function getAd($opt=array())
    {
        if(empty($opt)){
            return false;
        }

        $where = '';
        if(!empty($opt['id']))
            $where .= " AND id = '{$opt['id']}'";

        if(!empty($opt['ad_id'])){
            $where .= " AND ad_id = '{$opt['ad_id']}'";
        }

        if(!empty($opt['title'])){
            $where .= " AND name like '%{$opt['title']}%'";
        }

        if(!empty($opt['action'])){
            $where .= " AND action = '{$opt['action']}'";
        }

        if(isset($opt['z_status']) && is_numeric($opt['z_status'])){
            $where .= " AND z_status = ".$opt['z_status'];
        }

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
        return $this->where($where)->find(array());
    }

    //获取广告数量
    public function getAdCount($opt=array())
    {
        $where = '1';
        if(!empty($opt['id']))
            $where .= " AND id = '{$opt['id']}'";

        if(!empty($opt['ad_id'])){
            $where .= " AND ad_id = '{$opt['ad_id']}'";
        }

        if(!empty($opt['title'])){
            $where .= " AND name like '%{$opt['title']}%'";
        }

        if(!empty($opt['action'])){
            $where .= " AND action = '{$opt['action']}'";
        }

        if(isset($opt['z_status']) && is_numeric($opt['z_status'])){
            $where .= " AND z_status = ".$opt['z_status'];
        }

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

    //添加广告
    public function addAd($opt=array())
    {
        if(empty($opt)) return false;
        return $this->add($opt);
    }

    //保存广告
    public function saveAd($adId,$opt=array())
    {
        if(empty($adId) || !is_numeric($adId) || empty($opt)) return false;

        $where = " id = ".$adId;
        return  $this->where($where)->save($opt);
    }

    //开启
    public function openAd($adId)
    {
        if(empty($adId) || !is_numeric($adId)) return false;

        $where = " z_status in (0,2) AND id = ".$adId;
        return  $this->where($where)->save(array("z_status"=>'1'));
    }

    //关闭
    public function shutAd($adId)
    {
        if(empty($adId) || !is_numeric($adId)) return false;

        $where = " z_status = 1 AND id = ".$adId;
        return  $this->where($where)->save(array("z_status"=>'2'));
    }

}