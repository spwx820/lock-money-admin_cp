<?php
/**
 * 请求类
 *
 * 直接获取HTTP参数并解析成各个需要的参数
 *
 * @category   Leb
 * @package    Leb_Bootstrap
 * @version    $Id: request.php 38731 2013-03-15 10:36:14Z ziyuan $
 * @copyright
 * @license
 */
class Leb_Request extends Leb_Plugin_Abstract
{
	/**
	 * 单例
	 *
	 * @var Leb_Request
	 */
	static protected $_instance = null;

	/**
	 * 是否在调试
	 *
	 * @var boolean
	 */
	protected $_debug = true;

	/**
	 * 网址
	 *
	 * @var string
	 */
	private $_uri = null;

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
	 * 获得系统的相关参数
	 */
	function __construct()
	{
		parent::__construct();
		// filter $_GET
		$this->filterDatas($_GET);
		$this->filterDatas($_POST);
		$this->filterDatas($_COOKIE);
		$this->filterDatas($_SESSION);
	}

	/**
	 * 获得系统定义的全局级变量
	 *
	 * @param string $key
	 * @return string
	 */
    public function __get($key)
    {
        switch (true) {
            case isset($this->_params->$key):
                return $this->_params->$key;
            case isset($_GET[$key]):
                return $_GET[$key];
            case isset($_POST[$key]):
                return $_POST[$key];
            case isset($_COOKIE[$key]):
                return $_COOKIE[$key];
            case isset($_SERVER[$key]):
                return $_SERVER[$key];
            case isset($_ENV[$key]):
                return $_ENV[$key];
            case ($key == 'REQUEST_URI'):
                return $this->getUri();
            default:
                return null;
        }
    }

    /**
     * 返回$key对应的值
     *
     * @param string $key
     */
   public function get($key)
    {
    	return $this->__get($key);
    }

    /**
     * 设置URI
     *
     * @param string $requestUri
     * @return string
     */
    public function setUri($requestUri = null)
    {
        if ($requestUri === null) {
            if (isset($_SERVER['HTTP_X_REWRITE_URL'])) { // check this first so IIS will catch
                $requestUri = $_SERVER['HTTP_X_REWRITE_URL'];
            } else if (isset($_SERVER['PATH_INFO'])) {
                $requestUri = $_SERVER['PATH_INFO'];
                $requestUri .= empty($_SERVER['QUERY_STRING']) ? '' : ('?' . $_SERVER['QUERY_STRING']);
            } elseif (isset($_SERVER['REQUEST_URI'])) {
                $requestUri = $_SERVER['REQUEST_URI'];
            } elseif (isset($_SERVER['ORIG_PATH_INFO'])) { // IIS 5.0, PHP as CGI
                $requestUri = $_SERVER['ORIG_PATH_INFO'];
                $requestUri .= empty($_SERVER['QUERY_STRING']) ? '' : ('?' . $_SERVER['QUERY_STRING']);
            } else {
                return $this;
            }
        } elseif (!is_string($requestUri)) {
            return $this;
        } else {
            // Set GET items, if available
            $_GET = array();
            if (false !== ($pos = strpos($requestUri, '?'))) {
                // Get key => value pairs and set $_GET
                $query = substr($requestUri, $pos + 1);
                parse_str($query, $vars);
                $_GET = $vars;
            }
        }

        $req = explode('/', trim($requestUri, ' /'));
        if(1 == count($req) && $req)
        {
            $requestUri = rtrim($requestUri, ' /').'/';
        }
        $this->_uri = $requestUri;
        return $this;
    }

    /**
     * 获得除域名外的网址
     *
     * @return string
     */
    public function getUri()
    {
        if (empty($this->_uri)) {
            $this->setUri();
        }
        return $this->_uri;
    }

    /**
     * 获得网址如www.lebwork.org/default/index/
     * @return string
     */
    public function getUrl()
    {
    	$url = $this->get('HTTP_HOST') . $this->getUri();
    	return $url;
    }

    /**
     * 获得请求提交数据
     * @param string $key
     * @return array
     */
    public function getPost($key='', $def='')
    {
        if ('' == $key) {
            return $_POST;
        } elseif (isset($_POST[$key])) {
    		return $_POST[$key];
    	}
    	return $def;
    }

    /**
     * 获取请求数据，包含POST和/或GET
     *
     * @param string $key
     * @return array
     */
    public function getReq($key = '', $def='')
    {
        if ('' == $key) {
            if ($this->isPost()) {
                return $_POST;
            } else {
                return $_GET;
            }
        } else {
            if ($this->isPost()) {
                if (isset($_POST[$key])) {
                    return $_POST[$key];
                }
            } else {
                if (isset($_GET[$key])) {
                    return $_GET[$key];
                }
            }
        }
        
        return $def;
    }

    /**
     * 获得请求get数据
     *
     * @param string $key
     * @return array
     */
    public function getQuery($key='', $def='')
    {
        $data = _CLI_ ? $_SERVER['argv'] : $_GET;
    	if($key == ''){
    		return $data;
    	} elseif (isset($data[$key])) {
    		return $data[$key];
    	}else{
    		return $def;
    	}
    }

    /**
     * 过滤请求数据
     * @param mixed $value
     */
    protected function _filterData($value)
    {
    	if (!$this->_debug) {
			return trim($value);
    	}
    	return $value;
    }

    /**
     * 过滤数据
     *
     * @param mixed $data
     * @return mixed
     */
    public function filterDatas(&$data)
    {
    	if (is_array($data)) {
    	    foreach ($data as $key=>$value)
    		{
    			$data[$key] = $this->filterDatas($value);
    		}
		    return $data;
    	} elseif (is_string($data)) {
    		return $this->_filterData($data);
    	}
    }

    /**
     * 魔术方法
     * 是否（POST,GET,HEAD,DELETE,PUT）请求
     *
     * @param string $method
     * @param array $parms
     * @return boolean
     */
	public function __call($method,$parms)
	{
		$method = strtolower($method);
		if (in_array(strtolower($method),array('ispost','isget','ishead','isdelete','isput'))){
            return strtolower($_SERVER['REQUEST_METHOD']) == strtolower(substr($method,2));
		}
	}

	/**
	 * 是否AJAX请求, 参考zend frmaework
	 *
	 * @return boolean
	 */
	public function isXmlHttpRequest()
    {
        return ($this->getHeader('X_REQUESTED_WITH') == 'XMLHttpRequest');
    }

    
    /**
     * 是否flash请求, 参考zend frmaework
     *
     * @return boolean
     */
    public function isFlashRequest()
    {
        $header = strtolower($this->getHeader('USER_AGENT'));
        return (strstr($header, ' flash')) ? true : false;
    }

    /**
     * 获取客户端IP, 参考zend frmaework
     *
     * @param  boolean $checkProxy  是否检查代理
     * @return string
     */
    public function getClientIp($checkProxy = true)
    {
        if ($checkProxy && $this->getServer('HTTP_CLIENT_IP') != null) {
            $ip = $this->getServer('HTTP_CLIENT_IP');
        } else if ($checkProxy
        		&& $this->getServer('HTTP_X_FORWARDED_FOR') != null) {
            $ip = $this->getServer('HTTP_X_FORWARDED_FOR');
        } else {
            $ip = $this->getServer('REMOTE_ADDR');
        }

        return $ip;
    }

    /**
     * 获取头信息，参考zend frmaework
     *
     * @param string $header HTTP头信息名称
     * @return string|false  HTTP头信息, 或者头信息不存在返回false
     * @throws Leb_Exception
     */
    public function getHeader($header)
    {
        if (empty($header)) {
            throw new Leb_Exception('');
        }

        // Try to get it from the $_SERVER array first
        $temp = 'HTTP_' . strtoupper(str_replace('-', '_', $header));
        if (!empty($_SERVER[$temp])) {
            return $_SERVER[$temp];
        }

        // This seems to be the only way to get the Authorization header on
        // Apache
        if (function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
            if (!empty($headers[$header])) {
                return $headers[$header];
            }
        }

        return false;
    }

    /**
     * 获取$_SERVER中的信息
     * 如果不指定名称，则返回$_SERVER
     *
     * @param string $key
     * @param mixed $default 默认值
     * @return mixed 不存在返回null
     */
    public function getServer($key = null, $default = null)
    {
        if (null === $key) {
            return $_SERVER;
        }

        return (isset($_SERVER[$key])) ? $_SERVER[$key] : $default;
    }

    /**
     * 获取请示协议
     *
     * @return string
     */
    public function getScheme()
    {
        return ($this->getServer('HTTPS') == 'on') ? 'https' : 'http';
    }

    /**
     * 返回是否为ajax请求
     * @return boolean
     */
    public function isAjax()
    {
        return array_key_exists('HTTP_X_REQUESTED_WITH', $_SERVER);
    }

    /**
     * 返回是否为post表单
     * @return boolean
     */
    public function isPost()
    {
        return 'POST' == $_SERVER['REQUEST_METHOD'];
    }
}
