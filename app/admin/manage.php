<?php
/**
 * 后台管理员信息
 *
 * @category   Leb
 * @package    Leb_Action
 * @author     lihui
 * @version    $Id: manage.php 2014-09-03 9:58:00 lihui
 * @copyright (c) 2014 dianjoy.com
 * @license
 */
class manageController extends Application
{
    private $adminModel;
    private $codeModel;
    private $configModel;

    public function  execute($plugins)
    {
        $this->adminModel = $this->loadAppModel('Admin');
        $this->codeModel = $this->loadAppModel('Login_code');
        $this->configModel = C('global.php');
    }

    public function indexAction()
    {
        $page = (int)$this->reqVar('page', 1);

        $pageUrl = "/admin/manage/";
        $adminSet['condition'] = " AND id >0";
        $adminList = $this->adminModel->getAdminList($adminSet, $page, 20);
        $adminCount = $this->adminModel->getAdminCount($adminSet);
        $adminPages = pages($adminCount, $page, 20, $pageUrl, $array = array());

        $this->assign('adminList', $adminList);
        $this->assign('adminPages', $adminPages);
        $this->assign("adminStatus", $this->configModel['admin_status']);
        $this->assign('page', $page);

        $this->getViewer()->needLayout(false);
        $this->render('admin_list');
    }

    public function addAction()
    {
        $dosubmit = daddslashes($this->postVar('dosubmit', ''));
        $adminAdd['email'] = daddslashes($this->postVar('email', ''));
        $adminAdd['truename'] = daddslashes($this->postVar('username', ''));
        $adminAdd['password'] = daddslashes($this->postVar('password', ''));
        $adminAdd['last_time'] = date("Y-m-d H:i:s", time());
        $adminAdd['createtime'] = time();
        if (!empty($dosubmit) && !empty($adminAdd['email']) && !empty($adminAdd['password']))
        {
            preg_match("#(?=^.*?[a-z])(?=^.*?[A-Z])(?=^.*?\d)^(.{10,16})$#", $adminAdd['password'], $matches);
            if (empty($matches))
            {
                $this->redirect('密码必须为有小写、大写、数字且10-16位字符!', '', 3);
                die();
            }
            $this->adminModel->createAdmin($adminAdd);

            $permission = $this->adminModel->query("SELECT permission FROM a_user WHERE id = 18;")[0]['permission'];

            $uid = $this->adminModel->query("SELECT id FROM a_user ORDER BY id DESC limit 1;")[0]['id'];
            $permission = '"' . $uid . substr($permission, 3, strlen($permission));
//            var_dump($permission);die();

            $this->adminModel->query("UPDATE a_user SET permission = '{$permission}' WHERE id = $uid");
            $this->clearRedis();

            $this->redirect('', '/admin/manage/', 0);
        }
//        ADMIN_PERMISSION_SET_KEY

        $this->getViewer()->needLayout(false);
        $this->render('admin_add');
    }

    public function editAction()
    {
        $dosubmit = daddslashes($this->postVar('dosubmit', ''));
        $uid = (int)$this->reqVar('uid', 0);
        $password = daddslashes($this->postVar('admin_password', ''));
        $page = (int)$this->reqVar('page', 1);
        if (!empty($uid))
        {
            $adminRe = $this->adminModel->getAdmin(array("id" => $uid));
            if (!empty($dosubmit))
            {
                preg_match("#(?=^.*?[a-z])(?=^.*?[A-Z])(?=^.*?\d)^(.{10,16})$#", $password, $matches);
                if (empty($matches))
                {
                    $this->redirect('密码必须为有小写、大写、数字且10-16位字符!', '', 3);
                    die();
                }
                $password = md5($adminRe['salt'] . $password);
                $this->adminModel->updatePassword($uid, $password);
                $this->redirect('', '/admin/manage/?page=' . $page, 0);
            }
            $this->assign('admin_truename', $adminRe['truename']);
            $this->assign('admin_uid', $adminRe['id']);
            $this->assign('page', $page);
        }
        $this->getViewer()->needLayout(false);
        $this->render('admin_edit');
    }

    public function deleteAction()
    {
        $uid = (int)$this->getVar('id', 0);
        $page = (int)$this->reqVar('page', 1);

        $this->adminModel->removeUser($uid);

        $this->redirect('', '/admin/manage/?page=' . $page, 0);
    }

    public function recoverAction()
    {
        $uid = (int)$this->getVar('uid', 0);
        $page = (int)$this->reqVar('page', 1);

        $adminSet['id'] = $uid;
        $adminRe = $this->adminModel->getAdmin($adminSet);
        if ($adminRe)
        {
            $this->adminModel->recoverUser($uid);
            if (!empty($adminRe['email']))
            {
                $this->codeModel->delCode($adminRe['email']);
            }
        }

        $this->redirect('', '/admin/manage/?page=' . $page, 0);
    }

    public function disableAction()
    {
        $uid = (int)$this->getVar('uid', 0);
        $page = (int)$this->reqVar('page', 1);

        $this->adminModel->disableUser($uid);

        $this->redirect('', '/admin/manage/?page=' . $page, 0);
    }

    public function passwordAction()
    {
        $dosubmit = daddslashes($this->postVar('dosubmit', ''));
        $password = daddslashes($this->postVar('password', ''));
        $newpassword = daddslashes($this->postVar('newpassword', ''));
        $newpassword2 = daddslashes($this->postVar('newpassword2', ''));

        if (!empty($dosubmit))
        {
            preg_match("#(?=^.*?[a-z])(?=^.*?[A-Z])(?=^.*?\d)^(.{10,16})$#", $newpassword, $matches);
            if (empty($matches))
            {
                $this->redirect('密码必须为有小写、大写、数字且10-16位字符!', '', 3);
                die();
            } elseif ($newpassword != $newpassword2)
            {
                $this->redirect('两次密码不同!', '', 3);
                die();
            } else
            {
                $info = $this->adminModel->getUserById(UID);
                if (!$this->adminModel->validatePassword($password, $info['password'], $info['salt']))
                {
                    $this->redirect('原密码错误!', '', 3);
                    die();
                } else
                {
                    $newpassword = md5($info['salt'] . $newpassword);
                    $this->adminModel->updatePassword(UID, $newpassword);
                    $this->redirect('修改成功!', '', 3);
                    die();
                }
            }
            $this->redirect('', '/admin/manage/password', 0);
        }
        $this->getViewer()->needLayout(false);
        $this->render('password');
    }

    public function ajaxemailAction()
    {
        $email = daddslashes($this->getVar('email', ''));
        if (!empty($email))
        {
            $adminRe = $this->adminModel->getAdmin(array("email" => $email));
            if ($adminRe)
            {
                exit("0");
            }
        }
        exit("1");
    }

    public function permissionAction()
    {
        $uid = (int)$this->getVar('uid', 0);
        $dosubmit = daddslashes($this->postVar('dosubmit', ''));
        $permission_1 = $_POST['permission_1'];
        $permission_2 = $_POST['permission_2'];
        $permission_3 = $_POST['permission_3'];
        $permission_100 = $_POST['permission_100'];
        $permission_39 = $_POST['permission_39'];
//        $permission_6 = $_POST['permission_6'];
//        $permission_7 = $_POST['permission_7'];

        $res = $this->adminModel->query("SELECT permission, truename FROM a_user WHERE id = $uid");
        $permission_list = [$res[0]['permission']];
        $permission_str = '{' . join(',', $permission_list) . '}';
        $permission_value = json_decode($permission_str, true);

        $get_permission = [];
        foreach($permission_value as $var)
        {
            foreach($var['limit'] as $var_1)
                $get_permission = array_merge($get_permission, $var_1);
        }

        $get_menu = $this->adminModel->query("SELECT * FROM a_menus");
        $get_menus = [];
        $get_menu_name = [];
        foreach($get_menu as $var)
        {
            $get_menus[$var['parent_id']][] = $var['id'];
            $get_menu_name[$var['id']] = $var['name'];
        }
        if (!empty($dosubmit))
        {
            $limit = [];

            if ($permission_1)
                foreach ($permission_1 as $var)
                    $limit[1][] = intval($var);
            foreach ($permission_2 as $var)
                $limit[2][] = intval($var);
            foreach ($permission_3 as $var)
                $limit[3][] = intval($var);
            foreach ($permission_100 as $var)
                $limit[100][] = intval($var);
            if($uid == 1)
                $limit[100][] = 21;


            foreach ($permission_39 as $var)
                $limit[39][] = intval($var);
//            foreach ($permission_6 as $var)
//                $limit[6][] = intval($var);
//            foreach ($permission_7 as $var)
//                $limit[7][] = intval($var);

            foreach ($permission_value as &$var)
            {
                $var['limit'] = $limit;
            }
            $permission_str_c = json_encode($permission_value);
            $permission_str_c = substr($permission_str_c, 1, strlen($permission_str_c) - 2);

            $this->adminModel->query("UPDATE a_user SET permission = '{$permission_str_c}' WHERE id = $uid");
            $this->clearRedis();

            $this->redirect('权限修改成功', '/admin/manage', 0);

        }

        $this->assign('get_permission', $get_permission);
//        var_dump($get_menus);

        foreach($get_menus as $key => $val)
        {
            $this->assign("get_menus_$key", $val);
        }

        $this->assign('get_menu_name', $get_menu_name);
        $this->assign('admin_uid', $uid);
        $this->assign('truename', $res[0]['truename']);

        $this->getViewer()->needLayout(false);
        $this->render('admin_edit_1');
    }

    private function clearRedis()
    {
        $key = "ADMIN_PERMISSION_SET_KEY";
        $redis = Leb_Dao_Redis::getInstance();
        $res = $redis->get($key);
        if ($res)
            $redis->del($key);
    }

}
