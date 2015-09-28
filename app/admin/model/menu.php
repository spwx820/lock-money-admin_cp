<?php
/**
 * 菜单模型
 *
 * @category   Leb
 * @package    Leb_Model
 * @author 	lihui
 * @version   $Id: menu.php 1 2014-10-22 15:42 $
 * @copyright
 * @license
 */
class Menu extends Leb_Model
{
    protected $_pk = 'id';
    protected $_tableName = 'a_menus';
    protected $_daoType = false;

    //获取菜单记录列表
    public function getMenuList($opt=array(),$page=1, $limit=20)
    {
        $where = '1';
        if(!empty($opt['id']))
            $where .= " AND id = '{$opt['id']}'";

        if(isset($opt['parent_id']) && is_numeric($opt['parent_id']))
            $where .= " AND parent_id = '{$opt['parent_id']}'";

        if(isset($opt['status']) && is_numeric($opt['status'])){
            $where .= " AND status = ".$opt['status'];
        }

        if(isset($opt['condition']))
            $where .= $opt['condition'];

        if(!empty($opt['orderby'])){
            $orderStr =  $opt['orderby'];
        }else{
            $orderStr = "operattime desc";
        }

        $start = ($page-1) * $limit;
        if(!empty($where)){
            $where = trim($where," AND");
            return $this->where($where)->order($orderStr)->limit( "{$start}, {$limit}" )->select();
        }else{
            return $this->order($orderStr)->limit( "{$start}, {$limit}" )->select();
        }
    }

    //获取单条菜单
    public function getMenu($opt=array())
    {
        if(empty($opt)){
            return false;
        }
        $where = '1';
        if(!empty($opt['id']))
            $where .= " AND id = '{$opt['id']}'";

        if(isset($opt['parent_id']) && is_numeric($opt['parent_id']))
            $where .= " AND parent_id = '{$opt['parent_id']}'";

        if(!empty($opt['app']))
            $where .= " AND app = '{$opt['app']}'";

        if(!empty($opt['controller']))
            $where .= " AND controller = '{$opt['controller']}'";

        if(!empty($opt['action']))
            $where .= " AND action = '{$opt['action']}'";

        if(isset($opt['status']) && is_numeric($opt['status'])){
            $where .= " AND status = ".$opt['status'];
        }

        if(isset($opt['condition']))
            $where .= $opt['condition'];

        $where = trim($where," AND");
        return $this->where($where)->find(array());
    }

    //获取菜单数量
    public function getMenuCount($opt=array())
    {
        $where = '1';
        if(!empty($opt['id']))
            $where .= " AND id = '{$opt['id']}'";

        if(isset($opt['parent_id']) && is_numeric($opt['parent_id']))
            $where .= " AND parent_id = '{$opt['parent_id']}'";

        if(isset($opt['status']) && is_numeric($opt['status'])){
            $where .= " AND status = ".$opt['status'];
        }

        if(isset($opt['condition']))
            $where .= $opt['condition'];

        $where = trim($where," AND");
        return $this->where($where)->count();
    }

    //记录菜单
    public function addMenu($opt=array())
    {
        if(empty($opt)) return false;
        $opt['operattime'] = date("Y-m-d H:i:s",time());
        return $this->add($opt);
    }

    //记录菜单
    public function saveMenu($mId,$opt=array())
    {
        if(empty($mId) || empty($opt)) return false;
        $where = " id = ".$mId;
        return  $this->where($where)->save($opt);
    }

    //设置有效
    public function menuValidate($mId)
    {
        if(empty($mId) || !is_numeric($mId)) return false;

        $where = " status = 0 AND id = ".$mId;
        return  $this->where($where)->save(array("status"=>'1'));
    }

    //设置失效
    public function menuDisable($mId)
    {
        if(empty($mId) || !is_numeric($mId)) return false;

        $where = " status = 1 AND id = ".$mId;
        return  $this->where($where)->save(array("status"=>'0'));
    }

}