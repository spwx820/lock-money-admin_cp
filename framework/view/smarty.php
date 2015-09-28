<?php
/**
 * Smarty渲染插件
 *
 *
 * @category   Leb
 * @package    Leb_View
 * @author 	   liuxp
 * @author 	   guangzhao1@leju.sina.com.cn
 * @version    $Id: smarty.php 37459 2013-03-11 04:45:08Z ziyuan $
 * @copyright
 * @license
 */

  // require_once ('view/Smarty/Smarty.class.php');
require_once (dirname(__FILE__) . DIRECTORY_SEPARATOR . 'abstract.php');
class Leb_View_Smarty extends Leb_View_Abstract
{
    /**
     * Smarty object
     * @var Smarty
     */
    static protected $_smarty;
    
    /**
     * smarty插件
     *
     * @var array
     */
    protected $_smartyPlugins;
    
    /**
     * 设置模板缓存路径
     *
     * @param string $dir
     * @return void
     */
    public function setCompilePath($dir) {
        self::$_smarty->compile_dir = $dir;
    }
    
    /**
     * 获得渲染结果
     *
     * @param string $name the template
     * @return void
     */
    public function render($template='') {
        foreach ( $this->_vars as $key => $value ) {
            self::$_smarty->assign ( $key, $value );
        }
        
        if ($template) {
            $this->setTemplate($template);
        }
        
        $file = $this->getTemplatePath();
        try {
            $this->_renderContent = self::$_smarty->fetch ( $file );
        } catch (SmartyCompilerException $se) {
            $e = new Leb_Exception($se->getMessage(), $se->getCode());
            $e = $se;
            throw $e;
        } catch (Leb_Exception  $e) {
            throw $e;
        } catch (Exception $e) {
            throw $e;
        }
        
        return $this->_renderContent;
        //process the template (and filter the output)
    }
    
    /**
     * 初始化环境变量
     * @param array $plugins
     * @return Leb_View_Smarty
     */
    protected function execute($template)
    {
        //init env
        $this->_initConfig();
        if (empty(self::$_smarty)) {
            require_once (dirname(__FILE__) . DIRECTORY_SEPARATOR . 'Smarty/Smarty.class.php');
            self::$_smarty = new Smarty();
            // self::$_smarty->error_reporting = error_reporting() & ~E_NOTICE & ~E_WARNING;
            self::$_smarty->error_reporting = error_reporting() & ~E_NOTICE;
            //
            //compile dir must be set
            self::$_smarty->debugging = _DEBUG_;
            
            // delimiter
            $leftDelimiter = $this->getEnv('leftDelimiter');
            if (!isset($leftDelimiter)) {
                $leftDelimiter = "{{";
            }
            $rightDelimiter = $this->getEnv('rightDelimiter');
            if (!isset($rightDelimiter)) {
                $rightDelimiter = "}}";
            }
            self::$_smarty->left_delimiter = $leftDelimiter; // 设置左边符号
            self::$_smarty->right_delimiter = $rightDelimiter; // 设置友边符号
        }
        
        // compiled dir
        if ($compileDir = $this->getEnv('compileDir')) {
            self::$_smarty->compile_dir = $compileDir;
        } else {
            self::$_smarty->compile_dir = _APP_ . $this->getBase() . 'compiled';
        }
        
        //临时创建目录，正常使用时，可去掉
        if (!is_dir(self::$_smarty->compile_dir)) {
            mkdir(self::$_smarty->compile_dir);
        }
        
        // config dir
        if (!($configDirName = $this->getEnv('configDir'))) {
            $configDirName = 'config';
        }
        self::$_smarty->config_dir = _APP_ . $this->getBase() . $configDirName . '/';
        // print_r( self::$_smarty->config_dir) . "\n";
        // self::$_smarty->config_dir = null;
        
        // plugins dir
        if (!($pluginDirName = $this->getEnv('pluginDir'))) {
            $pluginDirName = 'plugins';
        }
        $pluginsDir = _APP_ . $this->getBase() . $pluginDirName . '/';
        $pluginDirApp = _APP_ . '_template/' . $pluginDirName . '/';
        // self::$_smarty->plugins_dir = array("plugins", $pluginDirApp, $pluginsDir);
        self::$_smarty->addPluginsDir($pluginDirApp);
        self::$_smarty->addPluginsDir($pluginsDir);
        // print_r(self::$_smarty->getPluginsDir());
        
        self::$_smarty->template_dir = array(_APP_ . $this->getBase(),
                                             _APP_ . '_template' . _DIR_SEPARATOR_) ;
        
        return $this->render($template);
    }
}
