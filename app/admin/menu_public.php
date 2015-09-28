<?php
/**
 * 后台菜单显示
 *
 * @category   Leb
 * @package    Leb_Action
 * @author     lihui
 * @version    $Id: menu_public.php 2014-10-22 13:58:00 lihui
 * @copyright (c) 2014 dianjoy.com
 * @license
 */
class menu_publicController extends Application
{
    private $menuModel;

    public function  execute($plugins)
    {
        $this->menuModel = $this->loadModel('Menu');
    }

    public function indexAction()
    {
        $menuId = (int)$this->reqVar('menu_id', 1);
        $getMenu = $this->menuModel->getMenu(array("id" => $menuId));

        //获取用户权限配置
        $limitConfig = C('register_user.php');

//////////////////////////////////////////////////////////////////////////////// 后台权限更新代码

        //用户权限配置
//            $registerUser = C('register_user.php');
//            $str = json_encode($registerUser);
//            $str = substr($str, 1, strlen($str) - 2);
//
//            $pieces = explode('"},"', $str);
//            $pieces_c = [];
//            for($ii = 0; $ii < count($pieces); $ii++)
//            {
//                if ($ii == 0)
//                    $pieces_c[$ii + 1] = $pieces[0] . '"}';
//                else if ($ii == count($pieces) - 1)
//                    $pieces_c[$ii + 1] = '"' . $pieces[$ii];
//                else
//                    $pieces_c[$ii + 1] = '"' . $pieces[$ii] . '"}';
//            }
//
//            for($ii = 1; $ii <= count($pieces_c); $ii++)
//            {
//                $model->query("UPDATE a_user SET permission = '{$pieces_c[$ii]}' WHERE id = $ii");
//            }
//            $permission_str = '{' . join(',', $pieces_c) . '}';
//            $registerUser = json_decode($permission_str, true);
        //////////////////////var_dump($registerUser);die();
////////////////////////////////////////////////////////////////////////////// 后台权限更新代码

        $permission_key = "ADMIN_PERMISSION_SET_KEY";

        $redis = Leb_Dao_Redis::getInstance();
        $permission_str = $redis->get($permission_key);

        if(empty($permission_str))
        {
            $permission = $this->menuModel->query("SELECT permission FROM a_user ORDER BY id ASC ");
            $permission_list =[];
            foreach($permission as $var)
            {
                if(!empty($var['permission']))
                {
                    $permission_list[] = $var['permission'];
                }
            }
            $permission_str = '{' . join(',', $permission_list) . '}';

            $redis->setex($permission_key, 60*60*10, $permission_str);
        }
        $limitConfig = json_decode($permission_str, true);

        $loginLimit = $limitConfig[UID];

        //显示运营菜单
        $menuList = array();
        $menuSet['status'] = 1;
        $menuSet['parent_id'] = $menuId;
        $menuSet['orderby'] = "id asc";
        $menuListRe = $this->menuModel->getMenuList($menuSet, 1, 50);
        if ($menuListRe && !empty($loginLimit))
        {
            foreach ($menuListRe as $key => $val)
            {
                if (in_array($val['id'], $loginLimit['limit'][$menuId]))
                {
                    $menuList[$key] = $val;
                }
            }
        }

        $this->assign('getMenu', $getMenu);
        $this->assign('menuList', $menuList);
        $this->getViewer()->needLayout(false);
        $this->render('menu_public');
    }

    public function currentAction()
    {
        $menuId = (int)$this->reqVar('menu_id', 1);

        $menuSet['id'] = $menuId;
        $getMenu = $this->menuModel->getMenu($menuSet);
        if ($getMenu)
        {
            echo $getMenu['name'] . " >";
        } else
        {
            echo '首页 >';
        }
    }



}