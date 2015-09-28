<?php
/**
 * 根据目前项目环境做的一些帮助性事务
 *
 * @category   Leb
 * @package    Leb_Bootstrap
 * @version    $Id: helper.php 26554 2013-01-21 01:54:18Z chenkai $
 * @copyright
 * @license
 */

class Leb_Helper extends Leb_Plugin_Abstract
{
    /**
     * 单例
     *
     * @var Leb_Request
     */
    static protected $_instance = null;

    /**
     * 数据关系模型
     *
     * @var Leb_Model
     */
    static public $_model = array();

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
     * 加载应用程序级的model
     *
     * model的默认命名就就是$model.php
     *
     * @param string $modelName
     * @return Leb_Model
     */
    static public function loadAppModel($modelName, $dbConfig = array())
    {
        $modelFile = strtolower($modelName) . '.php';


        $modelPath = _APP_ . '_model' . _DIR_SEPARATOR_ . $modelFile;


        if (!file_exists($modelPath)) {
            return false;
        }
        
        $model = ucwords($modelName);
        $hash = md5($modelName . serialize($dbConfig));
        if (empty(self::$_model[$hash])) {
            require_once($modelPath) ;
            self::$_model[$hash] = new $model($modelName,$dbConfig);
        }
        
        return  self::$_model[$hash];
    }

    /**
     * 传回模块级模板
     *
     * @param string $modelName
     * @return Leb_Model
     */
    static public function loadModel($modelName, $dbConfig, $application='')
    {
        $modelFile = strtolower($modelName) . '.php';

        if ('' == $application) {
            $application = $GLOBALS['APPLICATION'];
        }

        $modelDirectory = _APP_ . $application . _DIR_SEPARATOR_ . 'model' . _DIR_SEPARATOR_;

        $modelPath = $modelDirectory . $modelFile;
        $hash = md5($application . $modelName . serialize($dbConfig));
        if (!file_exists($modelPath)) {
            //尝试实例化应用级MODEL
            if($appModel = self::loadAppModel($modelName, $dbConfig))
            {
                return $appModel;
            }
            else
            {
                throw new Leb_Exception('Model ' . $modelName . ' not exists in Directory ' . $modelDirectory);
            }

            //应用级不存在时，直接实例化基础MODEL类
            //self::$_model[$hash] = new Leb_Model($modelName,$dbConfig);
            //self::$_model[$hash]->_application = $application;
        } else {
            if (empty(self::$_model[$hash])) {
                require_once($modelPath) ;
                $model = ucwords($modelName);
                self::$_model[$hash] = new $model($modelName,$dbConfig);
                self::$_model[$hash]->_application = $application;
            }
        }
        return  self::$_model[$hash];
    }
}
