<?php
/**
 * 动作类
 * 初始化应用程序层插件系列
 * 根据配置参数，提供实例化视图的操作
 *
 * 所有的Action必须从这个Action继承
 *
 * @category   Leb
 * @package    Leb_Bootstrap
 * @author     liuxp
 * @version    $Id: action.php 39624 2013-03-19 10:08:47Z ziyuan $
 * @copyright
 * @license
 */
require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'plugin/broker/abstract.php');
class Leb_Action extends Leb_Plugin_Broker_Abstract
{
    /**
     * 视图对象
     *
     * @var Leb_View
     */
    private $_viewer = null;

    /**
     * 要不要渲染
     *
     * @var boolean
     */
    private $_needRender = true;

    /**
     * 以去除public $infor
     *　不合法的命名
     *
     * @var Leb_Infor
     */
    protected $_infor = null;

    /**
     * 去除public $validator
     *
     * @var Leb_Validator
     */
    protected $_validator = null;

    /**
     * 授权对象
     *
     * @var Leb_Auth
     */
    protected $_auth  = null;

    /**
     * 存取权限
     *
     * @var Leb_Access
     */
    protected $_access = null;

    /**
     * 助手对象
     *
     * @var Leb_Helper
     */
    private $_helper = null;

    protected $_action;
    protected $_controller;
    protected $_application;

    /**
     * 加载函数文件
     */
    private $_fnPath = array();

    /**
     * 子类派送可以覆盖此函数
     */
    public function init()
    {
    }

    /**
     * 构造函数
     *
     * @param
     * 传递进来一个分发对象，可以通过分发对象获得Request及Router等对象
     */
    public function __construct($plugins=null)
    {
    	
        parent::__construct();
        $this->setRunner( $plugins);

        // define 几个常用常量
        $this->_action = $this->getRouter()->getAction();

        $GLOBALS['ACTION'] = $this->_action;

        $this->_controller = $this->getRouter()->getController();
        $GLOBALS['CONTROLLER'] = $this->_controller;

        $this->_application = $this->getRouter()->getApp();
        $GLOBALS['APPLICATION'] = $this->_application;

        $timeZone = $this->getEnv('timeZone');
        if($timeZone && function_exists('date_default_timezone_set'))
        {
            date_default_timezone_set($timeZone);
        }

        if($encoding = $this->getEnv('encode'))
        {
            mb_internal_encoding($encoding);
        }

        //加载应用函数
        $appFnPath = _APP_.'_function/functions.php';
        if(!in_array($appFnPath, $this->_fnPath) && file_exists($appFnPath))
        {
            include_once($appFnPath);
            $this->_fnPath[] = $appFnPath;
        }

        //加载模块函数
        $appPath = _APP_.$this->getRouter()->getApp().'/function/functions.php';
        if(!in_array($appPath, $this->_fnPath) && file_exists($appPath))
        {
            include_once($appPath);
            $this->_fnPath[] = $appPath;
        }

        //初始化应用配置目录
        $configDir = array();
        $configDir[] = _APP_ . $this->_application . '/config/';
        $configDir[] = _APP_ . '_config/';
        $configDir[] = _ROOT_ . 'config/';
        Leb_Plugin_Abstract::addConfigSearchPath($configDir);

        //注册应用参数的模板变量
        $this->assign('APP_PARAMS', array(
            'action'     => $this->_action,
            'controller' => $this->_controller,
            'app'        => $this->_application,
            'url'        => $this->buildUrl(
                $this->_action,
                $this->_controller,
                $this->_application
            )
        ));
    }

    /**
     * 获得分发器
     *
     * @return Leb_Dispatcher
     */
    public function getDispatcher()
    {
        return $this->Dispatcher;
    }

    /**
     * 获得_validator对象
     *
     * @return Leb_Validator
     */
    public function getValidator()
    {
        if (empty($this->_validator)) {
            $this->_validator = Leb_Validator::getInstance();
        }
        return $this->_validator;
    }

    /**
     * 获得Leb_Auth对象
     * @return Leb_Auth
     */
    public function getAuth()
    {
        return $this->_auth;
    }

    /**
     * 获得请求对象
     *
     * @return Leb_Request
     */
    public function getRequest()
    {
        return $this->getDispatcher()->Router->getRequest();
    }

    /**
     * 返回GET指定值,KEY空则返回整个GET数组
     * @return mix
     */
    public function getVar($key='', $def='')
    {
        return $this->getDispatcher()->Router->getRequest()->getQuery($key, $def);
    }

    /**
     * 返回POST指定值,KEY空则返回整个POST数组
     * @return mix
     */
    public function postVar($key='', $def='')
    {
        return $this->getDispatcher()->Router->getRequest()->getPost($key, $def);
    }

    /**
     * 返回REQUEST指定值,KEY空则返回整个REQUEST数组
     * @return mix
     */
    public function reqVar($key = '', $def='')
    {
        return $this->getDispatcher()->Router->getRequest()->getReq($key, $def);
    }

    /**
     * 获得请求对象
     *
     * @return Leb_Router
     */
    public function getRouter($key=null)
    {
        if (!is_null($key)) {
            return $this->getDispatcher()->Router->getQuerys($key);
        } else {
            return $this->getDispatcher()->Router;
        }
    }

    /**
     * 设置本Action不可渲染
     *
     */
    public function disableRender()
    {
        $this->_needRender = false;
    }

    /**
     * 设置本Action可以渲染
     *
     */
    public function enableRender()
    {
        $this->_needRender = true;
    }

    /**
     * 获得渲染器
     *
     */
    public function getViewer()
    {
        if(is_null($this->_viewer))
        {
            $this->_viewer = Leb_View::getInstance();
            $this->setDefaultTemplate();
            $this->setDefaultLayoutBase();
        }
        return $this->_viewer;
    }

    /**
     * 获得消息处理器
     * @return Leb_Info
     */
    public function getInfor()
    {
        return $this->_infor;
    }

    /**
     * 获得ACL处理器
     * @return Leb_Access
     */
    public function getAccess()
    {
        return $this->_access;
    }

    /**
     * 设置默认的action对应的模板
     *
     */
    public function setDefaultTemplate()
    {
        // 设置模板基地址
        $base = $this->_application . '/template/' ;
        $this->getViewer()->setBase($base);

        // 设置模板
        $template = $this->_controller. '.' .$this->_action;

        $this->getViewer()->setTemplate($template);
    }

    /**
     * 设置系统自定义的布局路径
     * see Leb_View::setLayoutPath()
     *
     */
    public function setDefaultLayoutBase()
    {
        // 单个应用程序开始搜索到全局
        $paths = array($this->getDispatcher()->getApp() . '/template/', '_template/');
        $this->getViewer()->setLayoutPath($paths);
    }

    /**
     * 代理视图对象的渲染方法
     * @param string $template
     */
    public function render($template='', $param=array())
    {
        if(!$this->_needRender)
        {
            return;
        }

        if($template)
        {
            $this->getViewer()->setTemplate($template);
        }

        foreach($param as $k => $v)
        {
            $this->getViewer()->$k = $v;
        }

        $this->getViewer()->run();
    }

    /**
     * 渲染部分片段
     * @param string $template
     */
    public function renderPartial($template='', $param=array())
    {
        if(!$this->_needRender)
        {
            return;
        }

        if($template)
        {
            $this->getViewer()->setTemplate($template);
        }

        foreach($param as $k => $v)
        {
            $this->getViewer()->$k = $v;
        }

        $this->getViewer()->needLayout(false);
        $this->getViewer()->run();
    }

    /**
     * 渲染JSON数据
     *
     * @param array $data
     */
    public function renderJson($data=array())
    {
        $this->getViewer()->needLayout(false);
        header('Content-type: application/json');
        echo json_encode($data);
        exit();
    }

    /**
     * 渲染字符串方法
     *
     * @param string $str
     */
    public function renderText($str='')
    {
        $this->getViewer()->needLayout(false);
        header('Content-type: text/plain');
        echo $str;
        exit();
    }

    /**
     * 渲染Xml数据
     * @param mix $data
     */
    public function renderXml($data)
    {
        $this->getViewer()->needLayout(false);
        header("Content-Type: text/xml");
        echo $this->toXml($data);
        exit();
    }

    /**
     * 输出Xml
     * @return string
     */
    public function toXml($data, $dom=null, $item=null)
    {
        if(!$dom)
        {
            $dom = new DOMDocument("1.0", "utf-8");
        }

        if(!$item)
        {
            $item = $dom->createElement("root");
            $dom->appendChild($item);
        }

        if(!$data)
        {
            $data = array();
        }

        foreach($data as $key=>$val)
        {
            $itemx = $dom->createElement(is_string($key)?$key:'item');
            $item->appendChild($itemx);
            if(!is_array($val))
            {
                $text = $dom->createTextNode($val);
                $itemx->appendChild($text);
            }
            else
            {
                $this->toXml($data[$key], $dom, $itemx);
            }
        }

        return $dom->saveXML();
    }

    /**
     * 模板赋值
     */
    public function assign($name, $val)
    {
        $this->getViewer()->$name = $val;
    }

    /**
     * 加载应用程序级的model
     *
     * model的默认命名就就是$model.php
     *
     * @param string $modelName
     * @return Leb_Model
     */
    public function loadAppModel($modelName, $dbConfig=array())
    {
        return Leb_Helper::loadAppModel($modelName, $dbConfig);
    }

    /**
     * 加载Model
     *
     * @param string $modelName
     * @param array  $dbconfig
     * @param string $application
     * @return Leb_Model
     */
    public function loadModel($modelName, $dbConfig = array(), $application='')
    {
        return Leb_Helper::loadModel($modelName, $dbConfig, $application, $this->_controller, $this->_action);
    }

    /**
     * 获得帮助对象
     * @return Leb_Helper
     */
    public function getHelper()
    {
        if(is_null($this->_helper))
        {
            require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'helper.php');
            $this->_helper = Leb_Helper::getInstance();
        }

        return $this->_helper;
    }

    /**
     * 跳转到新的动作中处理
     *
     * @param string $action       动作名
     * @param string $controller   控制器名
     * @param string $application  模块名
     * @param array $params        传递的参数 ，跳转后在新的动作中可以
     *                             通过$this->getParam('下标名')来读取;
     *
     */
    public function forward($action, $controller='', $application='', $params=array())
    {
        $dispatcher  = $this->getDispatcher();
        if ('' == $controller) {
            $controller = $this->_controller;
        } else {
            $dispatcher->setControllerName($controller);

        }
        if ('' == $application) {
            $application = $this->getDispatcher()->getAppName();
        } else {
            $dispatcher->setAppName($application);
        }

        $this->_action = $action;

        $dispatcher->setActionName($action);
        $GLOBALS['ISFORWARD'] = true;
        //shiling 计划任务forward的返回值
        return $this->getDispatcher()->dispatch($application, $controller, $action, $params, $dispatcher);
    }

    /**
     * 显示提示信息后页面跳转
     *
     * @param string $msg　　提示信息
     * @param string $url　　跳转URL
     * @param int $time     页面停留时间，单位秒，默认为3秒；0：页面不跳转
     */
    public function redirect($msg='', $url='' , $time=3, $type='success')
    {
        //$msg = $this->language($msg);//转义，暂时去掉，不影响以前程序
        if(!$msg && !$time && $url)
        {
            $this->location($url);
        }
        else
        {
            Leb_Response::redirect($msg, $url, $time, $type);
        }
    }

    /**
     * 重定向
     */
    public function location($url)
    {
        //ob_end_clean();
        $this->getViewer()->needLayout(false);
        header("Location: ".$url);
        die();
    }

    /**
     * 生成URL
     *
     * @param string $action       动作名
     * @param string $controller   控制器名 ，可选，默认与当前控制器同名
     * @param string $application  模块名   ，可选，默认与当前模块名相同
     * @param array  $params       传递的参数，参数将以GET方法传递
     * @return string
     */
    public function buildUrl($action, $controller='', $application='', $param=array())
    {
        return build_url($action, $controller, $application, $param);
    }
    
    /**
     * 代理从View继承的方法
     *
     * @param string $methodName
     * @param array $arguments
     */
    public function __call($methodName, $arguments)
    {
        if($this->_needRender && method_exists($this->getViewer(), $methodName))
        {
            return call_user_func_array(
                array($this->getViewer(), $methodName),
                $arguments
            );
        }
    }

    /**
     * 动态获得model 以便于不用显式加载model
     * $key对应相应的Model类
     *
     * @example
     * $this->__task表示获得是应用级的model
     * $this->_task表示模块级的model
     *
     * @param string $key
     */
    public function __get($key)
    {
        if ('__' == substr($key, 0, 2)) {
            $model = substr($key,2);
            return $this->loadAppModel($model);
        } else {
            $prefix = substr($key, 0, 1);
            if ('_' == $prefix) {
                $model = substr($key,1);
                return $this->loadModel($model);
            } else {
                return parent::__get($key);
            }
        }
    }

     /**
     * 多语言
     *
     * @param <type> $lan   要转义的字符串
     */
    public function language($lan)
    {
        $return = language('controller', $lan, get_gvar('APPLICATION'), get_gvar('CONTROLLER'), get_gvar('ACTION'));
        return $return;
    }

    public function alert($msg, $url)
    {
        echo
        '<link rel="stylesheet" href="/bootstrap/css/bootstrap.min.css" />
<link rel="stylesheet" href="/bootstrap/css/bootstrap-dialog.min.css" />
  <script src="/plugins/jQuery/jQuery-2.1.4.min.js"></script>
 <script src="/bootstrap/js/bootstrap.min.js"></script>
  <script src="/plugins/bootstrap-dialog.min.js"></script>
    <script>

        $(document).ready(function(){
        BootstrapDialog.show({
            title: "消息提示",
            message: "' . $msg . '",
            buttons: [{
                label: "确定",
                action: function(dialog) {
                    window.location.href = "' . $url . '";
                }
            }]
        });

        })

    </script>
';die();
    }
}
