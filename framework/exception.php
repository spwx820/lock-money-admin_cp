<?php
/**
 * 异常处理
 *
 * @category   Leb
 * @package    Leb_Bootstrap
 * @author 	   liuxp
 * @version    $Id: exception.php 31767 2013-02-20 09:03:42Z guangzhao $
 * @copyright
 * @license
 */
require_once('loader.php');
Leb_Loader::setAutoLoad();
require_once('view.php');

class Leb_Exception extends Exception
{
	/**
	 * 错误文件模板
	 *
	 * @var string
	 */
	protected $_exceptionFile = 'alert';

	/**
	 * 渲染器
	 *
	 * @var Leb_View
	 */
	protected $_viewer = null;

	/**
	 * 错误列表显示
	 *
	 * @param string $message
	 * @param code   $code
	 */
    public function __construct($message=0, $code = null)
    {
        if(_CLI_)
        {
			$file = $this->getFile();
			$line = $this->getLine();
            $now = date('Y-m-d H:i:s');
            $out =<<<EOF
==================================================================================
--                         Uncaught exception!
--时间：{$now}
--信息：{$message}
--代码：{$code}
--文件：{$file}
--行数：{$line}
==================================================================================

EOF;
            echo $out;
        }
        elseif(_DEV_)
	 	{
	 		$this->_viewer = Leb_View::getInstance();
		 	$this->_viewer->setLayoutPath('_template/layout/');
		 	$this->_viewer->setLayout('exception');
		 	$this->_viewer->setTemplate($this->_exceptionFile);
	 		$this->_viewer->title = '出错了!';

		 	$time = date('Y-m-d H:i:s', time());
		 	$this->_viewer->time = $time;

		 	$this->_viewer->message = $message;
		 	$this->_viewer->code = $code;
			$this->_viewer->file = $this->getFile();
			$this->_viewer->line = $this->getLine();
		 	$this->_viewer->run();
	        Leb_Debuger::showVar();
	 	}
        elseif(defined('_ER_PAGE_'))
        {
	 		$this->_viewer = Leb_View::getInstance();
		 	$this->_viewer->setLayoutPath('_template/layout/');
		 	$this->_viewer->setLayout('exception');
		 	$this->_viewer->setTemplate(_ER_PAGE_);
	 		$this->_viewer->title = '出错了!';

		 	$time = date('Y-m-d H:i:s', time());
		 	$this->_viewer->time = $time;

		 	$this->_viewer->message = $message;
		 	$this->_viewer->code = $code;
			$this->_viewer->file = $this->getFile();
			$this->_viewer->line = $this->getLine();
		 	$this->_viewer->run();
	        Leb_Debuger::showVar();
        }

        if(_RUNTIME_)
        {
            $now = time();
            $file = _RUNTIME_ . date('-Y-m-d', $now);
            $line = date('H:i:s')."\t".getClientIp()."\r\n";
        }
    }
}
