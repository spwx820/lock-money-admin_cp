<?php
/**
 * 广告banner数据操作
 *
 * @category   Leb
 * @package    Leb_Model
 * @author 	lihui
 * @version   $Id: adbanner.php 1 2014-12-30 10:42 $
 * @copyright
 * @license
 */
class Adbanner extends Leb_Model
{
    protected $_pk = 'id';
    protected $_tableName = 'z_ad_banner';
    protected $_daoType = false;

    //获取banner数据
    public function getAdBannerList($opt=array(),$page=1, $limit=20)
    {
        $where = '1';
        if(!empty($opt['id']))
            $where .= " AND id = '{$opt['id']}'";

        if(isset($opt['os_type'])){
            $where .= " AND os_type = '{$opt['os_type']}'";
        }

        if(isset($opt['st']) && is_numeric($opt['st'])){
            $where .= " AND st = ".$opt['st'];
        }

        if(isset($opt['condition']))
            $where .= $opt['condition'];

        if(!empty($opt['orderby'])){
            $orderStr = $opt['orderby'];
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

    //获取单个banner数据
    public function getAdBanner($opt=array())
    {
        if(empty($opt)){
            return false;
        }

        $where = '';
        if(!empty($opt['id']))
            $where .= " AND id = '{$opt['id']}'";

        if(isset($opt['os_type'])){
            $where .= " AND os_type = '{$opt['os_type']}'";
        }

        if(isset($opt['st']) && is_numeric($opt['st'])){
            $where .= " AND st = ".$opt['st'];
        }

        if(isset($opt['condition']))
            $where .= $opt['condition'];

        $where = trim($where," AND");
        return $this->where($where)->find(array());
    }

    //获取banner数量
    public function getAdBannerCount($opt=array())
    {
        $where = '1';
        if(!empty($opt['id']))
            $where .= " AND id = '{$opt['id']}'";

        if(isset($opt['os_type'])){
            $where .= " AND os_type = '{$opt['os_type']}'";
        }

        if(isset($opt['st']) && is_numeric($opt['st'])){
            $where .= " AND st = ".$opt['st'];
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

    //添加banner
    public function addAdBanner($opt=array())
    {
        if(empty($opt)) return false;
        return $this->add($opt);
    }

    //保存banner
    public function saveAdBanner($adId,$opt=array())
    {
        if(empty($adId) || !is_numeric($adId) || empty($opt)) return false;

        $where = " id = ".$adId;
        $opt['utime'] = date("Y-m-d H:i:s",time());
        return  $this->where($where)->save($opt);
    }

    //开启banner
    public function openAdBanner($adId)
    {
        if(empty($adId) || !is_numeric($adId)) return false;

        $where = " st = 0 AND id = ".$adId;
        $utime = date("Y-m-d H:i:s",time());
        return  $this->where($where)->save(array("st"=>'1','utime'=>$utime));
    }

    //关闭banner
    public function shutAdBanner($adId)
    {
        if(empty($adId) || !is_numeric($adId)) return false;

        $where = " st = 1 AND id = ".$adId;
        $utime = date("Y-m-d H:i:s",time());
        return  $this->where($where)->save(array("st"=>'0','utime'=>$utime));
    }

}