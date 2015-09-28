<?php
/**
 * 应用程序类
 *
 * 这个类是本框架自创的，由于平时发现要实现很多重复的开发功能，
 * 没有一个应用程序接口是不行的
 * 本类基本没有实现什么，只不过是为程序员与框架的一个接口
 *
 * 所有的Action必须从这个Action继承
 *
 * @category   Leb
 * @package    Leb_Bootstrap
 * @author 	lihui
 * @version    $Id: application.php 5396 2012-09-04 13:45:15
 * @copyright
 * @license
 */

class Application extends Leb_Action
{
    /**
     * 构造函数
     *
     * @param
     * 传递进来一个分发对象，可以通过分发对象获得Request及Router等对象
     */
    public function __construct($plugins = null)
    {
        parent::__construct($plugins);
        $this->_init();

        //权限配置

    }


    /**
     * 环境变量设置
     *
     */
    protected function _init()
    {
        // 初始化用户应用级共用数据
        $appPath = _APP_ . $this->getRouter()->getApp();
        $application = realpath($appPath . '/_application.php');
        if ($application && file_exists($application))
        {
            include_once($application);
            $myApplication = $this->_application . 'Application';
            $myApplication = new $myApplication();
            $myApplication->init($this);
        }

        // 需要登录访问的 $this->_application
        if (!in_array($this->_application, array('admin', 'tools')))
        {
            return;
        }

        $model = $this->loadAppModel('admin');
        $menuModel = $this->loadModel('menu', array(), 'admin');
        $isLogin = $model->isLogin();

        app()->user->setName('游客');
        if ($isLogin)
        {
            app()->user->setName($model->uname);
            app()->user->setId($model->uid);
            define('UNAME', app()->user->getName());
            define('UID', app()->user->getId());
            define('LAST_LOGIN', $model->last_time);
            $userAllMenum = array();
            $topMenuWhere = "parent_id=0 AND status=1 AND is_conceal=0";

//////////////////////////////////////////////////////////////////////////////// 后台权限更新代码

////////////////////////////////////////////////////////////////////////////// 后台权限更新代码

            $permission_key = "ADMIN_PERMISSION_SET_KEY";

            $redis = Leb_Dao_Redis::getInstance();
            $permission_str = $redis->get($permission_key);

            if(empty($permission_str))
            {
                $permission = $model->query("SELECT permission FROM a_user ORDER BY id ASC ");
                $permission_list =[];
                foreach($permission as $var)
                {
                    if(!empty($var['permission']))
                    {
                        $permission_list[] = $var['permission'];
                    }
                }
                $permission_str = '{' . join(',', $permission_list) . '}';

                $redis->setex($permission_key, 60 * 60 * 10, $permission_str);
            }
            $registerUser = json_decode($permission_str, true);

//            var_dump($registerUser);
//            die();
            $loginRegister = $registerUser[UID];

//            var_dump($loginRegister);die();

            if ($loginRegister && !empty($loginRegister['limit']))
            {
                $userTopMenu = array();
                foreach ($loginRegister['limit'] as $key => $val)
                {
                    $userTopMenu[] = $key;
                    $userAllMenum[$key] = $val;
                }
                if (!empty($userTopMenu))
                {
                    $topMenuStr = implode(",", $userTopMenu);
                    $topMenuWhere .= " AND id in($topMenuStr)";
                }
            }

            //获取菜单
            $defaultRightUrl = '';
            $defaultMenuModel = $this->loadModel('menu', array(), 'admin');
            $topMenu = $menuModel->query("SELECT * FROM a_menus WHERE $topMenuWhere");
            if ($topMenu && !empty($topMenu[0]['id']))
            {
                if (!empty($loginRegister['default']))
                {
                    $rightMenu = $defaultMenuModel->getMenu(array("id" => $loginRegister['default']));
                    if ($rightMenu)
                    {
                        $defaultRightUrl = '/' . $rightMenu['app'] . '/' . $rightMenu['controller'] . '/' . $rightMenu['action'];
                    }
                }
            }
            $this->assign('topMenu', $topMenu);
            $this->assign('defaultRightUrl', $defaultRightUrl);
            $this->assign('defaultUrl', '/admin/default/');

        }
        define('IP', getClientIp());
        if (!$isLogin)
        {
//		    $url = $this->buildUrl('default', 'login', 'index');
            if (in_array($this->_application, array('admin')) && !in_array($this->_controller, array('default')))
            {
//                echo '<script type="text/javascript">parent.location="/default/login/";</script>';
//                die();
            } else
            {
//                $this->redirect('', '/default/login/', 0);
            }
        } elseif ($isLogin)
        {
            //临时权限验证
            if ($this->_controller != 'default' && $this->_application == 'admin')
            {
                $userMenuWhere = " app='$this->_application' AND controller = '$this->_controller'";
                $userMenumRe = $menuModel->query("SELECT * FROM a_menus WHERE $userMenuWhere");
                if (!$userMenumRe || empty($userMenumRe[0]['id']))
                {
//                    die("无权限访问");
                }

                $menumSucceed = 0;
                if ($userAllMenum)
                {
                    foreach ($userAllMenum as $ukey => $uval)
                    {
                        if ($userMenumRe[0]['id'] > 0 && $userMenumRe[0]['id'] == $ukey)
                        {
                            $menumSucceed = 1;
                        }
                        if ($userMenumRe[0]['id'] > 0 && (in_array($userMenumRe[0]['id'], $uval) || in_array($userMenumRe[0]['id'], $uval)))
                        {
                            $menumSucceed = 1;
                        }
                    }
                }
                if (empty($menumSucceed))
                {
//                    die("无权限访问.");
                }
            }
        }
    }

    /**
     * 读配置文件里的baseJs并加载到view
     *
     */
    protected function _initJs()
    {
        $jses = array();
        $js = $this->getViewer()->getEnv('baseJs');
        if ($js)
        {
            $js = explode(',', $js);
            $jses += $js;
            $this->getViewer()->addJs($jses);
        }
    }

    /**
     * 根据application的配置文件及action获得相应的Css
     * 并加载到view
     *
     */
    protected function _initCss()
    {
        $csses = array();
        $css = $this->getViewer()->getEnv('baseCss');
        if ($css)
        {
            $css = explode(',', $css);
            $csses += $css;
        }
        $this->getViewer()->addCss($csses);
    }

    /**
     * 初始化网站名字
     *
     */
    protected function _initTitle()
    {
        $webName = $this->getEnv('webName');
        !$webName && $webName = 'lebwork';
        $this->getViewer()->webName = $webName;
    }

    /**
     * 同_getMyTheme
     *
     * @param string $theme
     */
    public function getMyTheme($theme)
    {
        return $this->_getCssBase() . 'theme/' . $theme . '/theme.css';
    }

    /**
     * 获得CSS根目录
     *
     */
    protected function _getCssBase()
    {
        $cssBase = $this->getViewer()->getEnv('cssBase');
        !$cssBase && $cssBase = "";
        return $cssBase;
    }

    /**
     * 同_getCssBase
     *
     */
    public function getCssBase()
    {
        return $this->_getCssBase();
    }

    /**
     * 获得Js根目录
     *
     */
    protected function _getJsBase()
    {
        $jsBase = $this->getViewer()->getEnv('jsBase');
        !$jsBase && $jsBase = "";
        return $jsBase;
    }

    /**
     * 设置页面导航
     *
     * @param array $pages
     * @example array('首页'=>'/', '二级页'=>'/second/')
     * @return string
     */
    public function setPageNavigator($pages)
    {
        $home = array('首页' => '/');
        $home += $pages;
        $pstr = array();
        $i = 1;
        $count = count($home);
        foreach ($home as $title => $url)
        {
            if ($count == $i)
            {
                $pstr[] = $title;
            } else
            {
                $pstr[] = '<a title="' . $title . ' " href="' . $url . '">' . $title . '</a>';
            }
            $i++;
        }

        $pstr = implode(' &gt; ', $pstr);
        $this->getViewer()->assign('pageNavigagor', $pstr);
    }

    /**
     * 登陆判断
     */
    public function isLogin()
    {
        return !app()->user->isGuest();
    }

    /**
     * 设置权限系统
     */
    public function setPrivilege()
    {
        $this->getViewer()->needLayout(false);
        $this->display('login');
        die;
    }



    public function get_side_bar_menu()
    {
        $model = $this->loadAppModel('admin');

        $redis = Leb_Dao_Redis::getInstance();

        //获取用户权限配置

        $user_permission_key = "ADMIN_PERMISSION_SET_KEY" . UID;
        $uid_permission_str = $redis->get($user_permission_key);


        if(empty($uid_permission_str))
        {
            $permission_key = "ADMIN_PERMISSION_SET_KEY";

            $permission_str = $redis->get($permission_key);

            if (empty($permission_str))
            {
                $permission = $model->query("SELECT permission FROM a_user ORDER BY id ASC ");
                $permission_list = [];
                foreach ($permission as $var)
                {
                    if (!empty($var['permission']))
                    {
                        $permission_list[] = $var['permission'];
                    }
                }
                $permission_str = '{' . join(',', $permission_list) . '}';

                $redis->setex($permission_key, 60 * 60 * 10, $permission_str);
            }
            $limitConfig = json_decode($permission_str, true);

            define(UID, 1);

            $loginLimit = $limitConfig[UID];
            $menu_id_list = $loginLimit['limit'];
            ksort($menu_id_list );
            $menu_id_str = '';

            $temp = [];


            foreach($menu_id_list as $key => $var)
            {
                $menu_id_str .= "$key, ";
                $menu_id_str .= "".join(',',  $var);
                $menu_id_str .= ",";
                asort($var);
                $temp[$key] = $var;
            }
            $menu_id_str .= "0";

            $menu_id_list = $temp;


            $menuListRe = $model->query("SELECT * FROM a_menus where id in ($menu_id_str)");
            $temp = [];


            foreach($menuListRe as $var)
            {
                $var['url'] = "/{$var['app']}/{$var['controller']}/{$var['action']}";
                $temp[intval($var['id'])] = $var;
            }
            $menuListRe = $temp;

            $side_bar_menu = '<aside class="main-sidebar">
   <!-- sidebar: style can be found in sidebar.less -->
   <section class="sidebar" style="height: auto;">
    <!-- Sidebar user panel -->
    <div class="user-panel">
     <div class="pull-left image">
     </div>
     <div class="pull-left info">
      <p>' . UNAME . '</p>
      <a href="#"><i class="fa fa-circle text-success active"></i>Online</a>
     </div>
    </div>
    <div>
     <ul class="sidebar-menu" id="main_menu">
      <!-- content in sidebar menu will be set in js code -->
      <li class="header">MAIN NAVIGATION</li>';

//            var_dump($menuListRe['1']);

            foreach($menu_id_list as $key=>$val)
            {
                $side_bar_menu .= '<li class="treeview"> <a href="#"><i class="fa fa-files-o"></i>  <span>' . $menuListRe[$key]['name'] . '</span> </a>   <ul class="treeview-menu">';
                foreach($val as $k=>$v)
                {
                    $side_bar_menu .=' <li><a href="' . $menuListRe[$v]['url'] . '"><i class="fa fa-circle-o"></i>' . $menuListRe[$v]['name'] . '</a></li>';
                }
                $side_bar_menu .= '</ul> </li> ';
            }
            $side_bar_menu .= ' </div>
                               </section>
                               <!-- /.sidebar -->
                              </aside>';

            $redis->setex($user_permission_key, 60 * 60 * 10, $side_bar_menu);

            return $side_bar_menu;
        }
        else
        {
            return $uid_permission_str;
        }
    }

}

