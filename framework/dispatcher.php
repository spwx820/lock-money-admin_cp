<?php
/**
 * 分发器
 *
 * 由Request对象发送一个请求，本对象查询有没有相关的重写路由
 * 如果有重写路由则由重写路由分解出Controller及Action交给dispatcher
 * Dispatcher根据传送过来的数据调用相关Action及Controller，如果找不着
 * 则调用DefaultController及DefaultAction，可以设置DefaultController及DefaultAction
 *
 * 对应的对象完成后会抛出一个响应，可能是OK，也有可能是强制错误显示等
 *
 * @category   Leb
 * @package    Leb_Bootstrap
 * @author     lihui
 * @version    $Id: dispatcher.php  2013-06-27 02:28:30Z$
 * @copyright
 * @license
 */

require_once(dirname(__FILE__).'/plugin/broker/abstract.php');
require_once(dirname(__FILE__).'/router.php');
class Leb_Dispatcher extends Leb_Plugin_Broker_Abstract
{
    /**
     * 单例
     *
     * @var Leb_Request
     */
    static protected $_instance = null;

    /**
     * 控制器名字
     *
     * @var string
     */
    protected $_defaultControllerName = "error";

    /**
     * 动作名字
     *
     * @var string
     */
    protected $_defaultActionName = "index";

    /**
     * 路由器
     *
     * @var Leb_Router
     */
    protected $_router = null;

    /**
     * Action返回值
     *
     * @var mix
     */
    private $_act_return = null;

    /**
     * 实例化本程序
     * @param $args = func_get_args();
     * @return object of this class
     */
    static public function getInstance()
    {
        if (!isset(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * 设置控制器名字
     *
     * @param string $name
     */
    public function setControllerName($name)
    {
        $this->_router->setController($name);
    }

    /**
     * 得到控制器名字
     * @return string
     */
    public function getControllerName()
    {
        return $this->_router->getController();
    }

    /**
     * alias getControllerName
     *
     * @return string
     */
    public function getController()
    {
        return $this->getControllerName();
    }

    /**
     * 设置应用程序名字
     *
     * @param string $name
     */
    public function setAppName($name)
    {
        $this->_router->setApp($name);
    }

    /**
     * 获得应用程序名字
     * @return string
     */
    public function getAppName()
    {
        return $this->_router->getApp();
    }

    /**
     * alias getAppName
     *
     * @return string
     */
    public function getApp()
    {
        return $this->getAppName();
    }

    /**
     * 设置动作名字
     *
     * @param string $name
     */
    public function setActionName($name)
    {
        $this->_router->setAction($name);
    }

    /**
     * 获得动作名字
     * @return string
     */
    public function getActionName()
    {
        return $this->_router->getAction();
    }

    /**
     * alias getActionName()
     *
     * @return string
     */
    public function getAction()
    {
        return $this->getActionName();
    }

    /**
     * 设置默认的控制器名字
     * @param string $controllerName 应用程序名字
     */
    public function setDefaultController($controllerName)
    {
        $this->_defaultControllerName = $controllerName;
    }

    /**
     * 获得当前控制器名字，对控制器文件
     * @return string 应用程序完整地址
     */
    public function getDefaultController()
    {
        return $this->_defaultControllerName;
    }

    /**
     * 设置默认的动作名字
     * @param string $actionName 动作名字
     */
    public function setDefaultAction($actionName)
    {
        $this->_defaultActionName = $actionName;
    }

    /**
     * 获得默认的动作名，当程序没有传递ActionName时，获得此默认Action
     * @return string 应用程序完整地址
     */
    public function getDefaultAction()
    {
        return $this->_defaultActionName;
    }

    /**
     * 分发运行
     * @param array $plugins
     */
    public function execute($plugins)
    {
        $this->_router = Leb_Router::getInstance();
        $this->_router->run($plugins); // 手动运行Router对象以获得Request对象

        // 手动设计运行对象
        $this->setRunner($this->_router);
        $this->setAppName($this->_router->getApp());
        $this->setControllerName($this->_router->getController());
        $this->setActionName($this->_router->getAction());

        return $this;
    }

    /**
     * 实现分发
     * 为了分发前能执行其他操作，所以把要分发的几个变量进行修改
     * 如可以实现分发前重定向，所以你要写几个后插插件
     * 如权限控制插件需写成后分发插件
     *
     * @param Leb_Plugin_Abstract $plugins 通常是$this
     * @return Leb_Plugin_Abstract
     */
    protected function _afterLaunch($plugins=null)
    {
        parent::_afterLaunch($plugins);
        try {
            $appPath = $plugins->getAppName();
            $controllerName = $plugins->getControllerName();
            $actionName = $plugins->getActionName();
            $this->dispatch($appPath, $controllerName, $actionName, array(), $plugins);
        } catch (Leb_Exception $e) {
            throw $e;
        }

        return $plugins;
    }

    /**
     * 分发过程
     *
     * 可供程序直接跳转
     *
     * @param string $appName
     * @param string $controllerName
     * @param string $actionName
     * @param array  $params 查询参数，相当于post 或 get过来的参数值
     */
    public function dispatch($appName, $controllerName, $actionName, $params=array(), $plugins=null)
    {
        if (empty($plugins)) {
            $plugins = self::getInstance();
        }

        // 找到相关程序并执行
        $classPath = (_CLI_ ? _CMD_ : _APP_) . $appName . "/" . $controllerName . ".php";
        if (!file_exists($classPath)) {//如果不存在，则按默认执行
            $page_path = "{$appName}/{$controllerName}/{$actionName}";
            $controllerName = $plugins->getDefaultController();
            $actionName = $plugins->getDefaultAction();
            $classPath = _APP_ . $appName . "/" . $controllerName . ".php";
            if (!file_exists($classPath)) {
                $page_path = "{$page_path}|{$appName}/{$controllerName}/{$actionName}";
                throw new Leb_Exception('Error 404, error page not found!' . $page_path, 404);
            }
            // 转发
            $this->setAppName($appName);
            $this->setControllerName($controllerName);
            $this->setActionName($actionName);
        } else {
            try {
                require_once($classPath);
            } catch (Leb_Exception $e) {
                throw $e;
            }
        }

        // 默认动作如果动作为空
        $controllerClassName = $controllerName . (_CLI_ ? 'Command' : 'Controller');
        $actionFunctionName = $actionName . 'Action';

        // 新建控制器


        $controller = new $controllerClassName($plugins);
        if (!empty($params)) {
            $controller->setParam($params);
        }

        if (!method_exists($controller, $actionFunctionName)) {
            $refc = new ReflectionClass($controllerClassName);
            $mtdc = $refc->getMethod('__call');
            $decl = $mtdc->getDeclaringClass();

            // if ((new ReflectionClass($controllerClassName))->getMethod('__call')->getDeclaringClass()->name == $controllerClassName)
            if ($decl->name == $controllerClassName) {
                // $controller->$actionFunctionName();
                // goon 继续下面的调用，因为我们知道该controller中对此有处理。
            } else {
                throw new Leb_Exception('Page <strong>[' . $actionFunctionName . ']</strong>'.
                'not found! The reason cause this error may be Method not exist'.
                'in the Controller <strong>[' . $controllerClassName . ']</strong>');
            }
        }

        // 运行类的run方法，主要是用于加载一些共用操作，如layout数据
        $controller->run();

        defined('__FRM_ACTION_BEGIN__') or define('__FRM_ACTION_BEGIN__', microtime(true));
        $beforeMethod = $actionName . 'Before';
        if (method_exists($controller, $beforeMethod)) {
            $controller->$beforeMethod();
        }

        $method = new ReflectionMethod($controllerClassName, $actionFunctionName);
        if($method->getNumberOfParameters() > 0)
        {
            $this->_act_return = $response = $this->runWithParams($controller, $method, _CLI_ ? $_SERVER['argv'] : $_GET);
        }
        else
        {
            $this->_act_return = $response = $controller->$actionFunctionName();
        }

        $afterMethod = $actionName . 'After';

        if (method_exists($controller, $afterMethod)) {
            $controller->$afterMethod();
        }

        defined('__FRM_ACTION_END__') or define('__FRM_ACTION_END__', microtime(true));
        //显示调试信息
        $request = Leb_Request::getInstance();
        if (!$request->isXmlHttpRequest() && !$request->isFlashRequest) {
            Leb_Debuger::showVar();
        }

        $obj = FirePHP::getInstance(true);
        $obj->info('Response:'.(__FRM_ACTION_END__ - __FRM_ACTION_BEGIN__));
        $obj->info('Framework:'.(__FRM_ACTION_BEGIN__ - __FRM_BEGIN__));
        if (!empty($response) && ($response instanceof Leb_Response)) {
            $response->run();
        }else{
            //shiling 计划任务forward的返回值
            return $response;
        }
    }

    /**
     * 自动填充参数并执行
     *
     * @param object $contrller
     * @param object $method
     * @param mix    $params
     */
    protected function runWithParams($controller, $method, $params)
    {
        $ar = array();
        $func_param = $method->getParameters();
        foreach($func_param as $param_item)
        {
            $name = $param_item->getName();
            if(isset($params[$name]))
            {
                if($param_item->isArray())
                    $ar[] = is_array($params[$name]) ? $params[$name] : array($params[$name]);
                elseif($val = json_decode($params[$name], true))//try decode json string
                $ar[] = $val;
                else
                    $ar[] = $params[$name];
            }
            else if($param_item->isDefaultValueAvailable())
            {
                $ar[] = $param_item->getDefaultValue();
            }
            else
            {
                throw new Leb_Exception('page error!'.
                ' The reason cause this error may be param <strong style="color:#F00;">'.$name.'</strong> can not be null');
            }
        }

        return $method->invokeArgs($controller, $ar);
    }

    /**
     * 获取Action返回值
     * @return mix
     */
    public function getActionReturn()
    {
        return $this->_act_return;
    }
}