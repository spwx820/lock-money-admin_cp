<?php
/**
 * 后台菜单
 *
 * @category   Leb
 * @package    Leb_Action
 * @author     lihui
 * @version    $Id: menu.php 2014-10-22 13:58:00 lihui
 * @copyright (c) 2014 dianjoy.com
 * @license
 */
class menuController extends Application
{
    private $menuModel;
    private $configModel;

    public function  execute($plugins)
    {
        $this->menuModel = $this->loadModel('Menu');
        $this->configModel = C('global.php');
    }

    public function indexAction()
    {
        $page = (int)$this->reqVar('page', 1);

        $menuSet = array();
        $menuList = $this->menuModel->getMenuList($menuSet, $page, 100);
        if ($menuList)
        {
            foreach ($menuList as $key => $val)
            {
                if (empty($val['parent_id']))
                {
                    $menuList[$key]['parent_name'] = '一级菜单';
                    continue;
                }
                $menuParent = $this->menuModel->getMenu(array('id' => $val['parent_id']));
                if ($menuParent)
                {
                    $menuList[$key]['parent_name'] = $menuParent['name'];
                } else
                {
                    $menuList[$key]['parent_name'] = '';
                }
            }
        }
        $menuCount = $this->menuModel->getMenuCount($menuSet);
        $menuPages = pages($menuCount, $page, 100, '', $array = array());

        $this->assign('menuList', $menuList);
        $this->assign("menuConceal", $this->configModel['menu_conceal']);
        $this->assign("menuStatus", $this->configModel['menu_status']);
        $this->assign('menuCount', $menuCount);
        $this->assign('menuPages', $menuPages);
        $this->assign('page', $page);

        $this->getViewer()->needLayout(false);
        $this->render('menu_list');
    }

    public function addAction()
    {
        $dosubmit = daddslashes($this->postVar('dosubmit', ''));
        $menuAdd['name'] = daddslashes($this->postVar('menu_name', ''));
        $menuAdd['parent_id'] = (int)$this->postVar('parent_id', 0);
        $menuAdd['app'] = daddslashes($this->postVar('app', ''));
        $menuAdd['controller'] = daddslashes($this->postVar('controller', ''));
        $menuAdd['action'] = daddslashes($this->postVar('action', ''));
        $menuAdd['is_conceal'] = (int)$this->postVar('is_conceal', 0);
        if (!empty($dosubmit))
        {
            if (empty($menuAdd['app']) || empty($menuAdd['controller']) || empty($menuAdd['action']))
            {
                $this->redirect('操作参数不能为空', '', 3);
                die();
            } else
            {
                $menuSet['app'] = $menuAdd['app'];
                $menuSet['controller'] = $menuAdd['controller'];
                $menuSet['action'] = $menuAdd['action'];
                if ($this->menuModel->getMenu($menuSet) && !empty($menuAdd['parent_id']))
                {
                    $this->redirect('操作动作已存在', '', 3);
                    die();
                }
            }
            $menuAdd['operat'] = UNAME;
            $menuAdd['status'] = 1;
            $this->menuModel->addMenu($menuAdd);
            $this->redirect('', '/admin/menu/', 0);
        }

        $parentMenuSet['parent_id'] = 0;
        $parentMenuSelect = $this->menuModel->getMenuList($parentMenuSet, 1, 100);
        $this->assign('parentMenuSelect', $parentMenuSelect);

        $this->getViewer()->needLayout(false);
        $this->render('menu_add');
    }

    public function editAction()
    {
        $page = (int)$this->reqVar('page', 1);
        $dosubmit = daddslashes($this->postVar('dosubmit', ''));

        $menuId = (int)$this->reqVar('menu_id', 0);
        $menuSave['name'] = daddslashes($this->postVar('menu_name', ''));
        $menuSave['id'] = daddslashes($this->postVar('id', ''));

        $menuSave['parent_id'] = (int)$this->postVar('parent_id', 0);
        $menuSave['app'] = daddslashes($this->postVar('app', ''));
        $menuSave['controller'] = daddslashes($this->postVar('controller', ''));
        $menuSave['action'] = daddslashes($this->postVar('action', ''));
        $menuSave['is_conceal'] = (int)$this->postVar('is_conceal', 0);

        $getMenu = $this->menuModel->getMenu(array("id" => $menuId));
        if (empty($getMenu))
        {
            $this->redirect('参数不能为空', '', 0);
            die();
        }
        if (!empty($dosubmit))
        {
            if (empty($menuSave['app']) || empty($menuSave['controller']) || empty($menuSave['action']))
            {
                $this->redirect('操作参数不能为空', '', 3);
                die();
            } else
            {
                $menuSet['app'] = $menuSave['app'];
                $menuSet['controller'] = $menuSave['controller'];
                $menuSet['action'] = $menuSave['action'];
                $menuSet['condition'] = " AND id != '{$menuId}'";
                if ($this->menuModel->getMenu($menuSet) && !empty($menuSave['parent_id']))
                {
                    $this->redirect('操作动作已存在', '', 3);
                    die();
                }
            }
            $menuSave['operat'] = UNAME;
            $menuSave['status'] = 1;
            $menuSave['operatetime'] = date("Y-m-d H:i:s", time());
            $this->menuModel->saveMenu($menuId, $menuSave);
            $this->clearRedis();

            $this->redirect('', '/admin/menu/?page=' . $page, 0);
        }

        $parentMenuSet['parent_id'] = 0;
        $parentMenuSet['condition'] = " AND id != '{$menuId}'";
        $parentMenuSelect = $this->menuModel->getMenuList($parentMenuSet, 1, 100);

        $this->assign('menuId', $menuId);
        $this->assign('getMenu', $getMenu);
        $this->assign('parentMenuSelect', $parentMenuSelect);
        $this->assign('page', $page);
        $this->getViewer()->needLayout(false);
        $this->render('menu_edit');
    }

    private function clearRedis()
    {
        $key = "ADMIN_PERMISSION_SET_KEY";
        $redis = Leb_Dao_Redis::getInstance();
        $res = $redis->get($key);
        if ($res)
            $redis->del($key);
    }
    public function openAction()
    {
        $mId = (int)$this->reqVar('id', 0);
        if ($mId > 0)
        {
            $this->menuModel->menuValidate($mId);
        }
        $this->redirect('', '/admin/menu/', 0);
    }

    public function shutAction()
    {
        $mId = (int)$this->reqVar('id', 0);
        if ($mId > 0)
        {
            $this->menuModel->menuDisable($mId);
        }
        $this->redirect('', '/admin/menu/', 0);
    }

}