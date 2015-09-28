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
 * @author     ziyuan
 * @version    $Id: action.php 10236 2012-11-14 10:03:04Z ziyuan $
 * @copyright
 * @license
 */

require_once(dirname(__FILE__).'/../plugin/broker/abstract.php');

class Command extends Leb_Plugin_Broker_Abstract
{
    protected $_helper = null;
    protected $_action;
    protected $_controller;
    protected $_application;
    private $_fnPath = array();

    /**
     * 构造函数
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
    }

    /**
     * 获得分发器
     * @return Leb_Dispatcher
     */
    public function getDispatcher()
    {
        return $this->Dispatcher;
    }

    /**
     * 获得请求对象
     * @return Leb_Request
     */
    public function getRequest()
    {
        return $this->getDispatcher()->Router->getRequest();
    }

    public function getVar($key='', $def='')
    {
        return $this->getDispatcher()->Router->getRequest()->getQuery($key, $def);
    }

    /**
     * 获得请求对象
     *
     * @return Leb_Router
     */
    public function getRouter($key=null)
    {
        if(!is_null($key))
        {
            return $this->getDispatcher()->Router->getQuerys($key);
        }
        else
        {
            return $this->getDispatcher()->Router;
        }
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
            $this->_helper = Leb_Helper::getInstance();
        }
        return $this->_helper;
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
        if('__' == substr($key, 0, 2))
        {
            $model = substr($key,2);
            return $this->loadAppModel($model);
        }
        else
        {
            $prefix = substr($key, 0, 1);
            if('_' == $prefix)
            {
                $model = substr($key,1);
                return $this->loadModel($model);
            }
            else
            {
                return parent::__get($key);
            }
        }
    }
}
