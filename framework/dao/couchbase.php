<?php
/**
 * 存储器（含内存存储及文件存储）
 *
 * 由配置文件设定选择何种方式
 *
 * @category   Leb
 * @package    Leb_Cache
 * @author     guangzhao1@leju.sina.com.cn
 * @version    $Id: couchbase.php 25656 2013-01-15 09:05:34Z guangzhao $
 * @copyright
 * @license
 */

class Leb_Dao_Couchbase extends Leb_Dao_Memcache
{
	/**
	 * Couchbase对象
	 *
	 * @var Couchbase
	 */
    static protected  $_instance = array();
    // protected $_cacher;
    /**
     *是否分组
     * @var <type>
     */
    // protected $_isGroup = false;

    /**
	 * 实例化本程序
	 * @param $args = func_get_args();
     * @return object of this class
	 */
	static public function getInstance($name = 'default')
	{
		if (!isset(self::$_instance[$name])) {
			self::$_instance[$name] = new self($name);
		}
		return self::$_instance[$name];
	}

	/**
	 * 创建couchbase对象
	 *
	 * @param array $options
	 * @return couchbase
	 */

	protected function __construct($name = 'default', $options=array())
	{
        parent::__construct($name, $options);
	}
    /**
     * 添加server
     * @param <type> $servers
     * @return Couchbase
     */
    public function addServer($servers)
    {
        // example code
        // $cacheInstance = new Couchbase("10.207.0.202:8091", 'username', 'password', 'default');
        // $cacheInstance = new Couchbase("10.207.0.202:8091", null, null, $this->_name);
        // $this->_cacher = $cacheInstance; 

        $cacheInstance = null;
        foreach ($servers as $idx => $server ) {
            if ($idx > 0) {
                $cacheInstance->addServer($server['host'], 8091);
            } else {
                $persistent = false;
                $cacheInstance = new Couchbase($server['host'] . ':8091', null, null, $this->_name, $persistent);
            }
        }
        

        return $cacheInstance;
    }

    /**
     * 获取错误代码
     *
     */
    public function getErrno()
    {
        $c = $this->_cacher;
        if ($this->_isGroup) {
            $c = $this->_cacher['local'];
        }
        return $c->getResultCode();    
    }

    /** 
     * 获取错误信息
     *
     */
    public function getError()
    {
        $c = $this->_cacher;
        if ($this->_isGroup) {
            $c = $this->_cacher['local'];
        }
        return $c->getResultMessage();        
    }


	/**
	 * 由子方法实现的方法
	 * @param string $key 键
	 * @param mixed $value 值
	 * @param array $options 设置条件，如什么时候过期，所在域等
	 */
    public function set($key, $value, $options = array())
    {
        $c = $this->_cacher;
        if ($this->_isGroup) {
            $c = $this->_cacher['local'];
        }

        $flag = empty($options['flag']) ? 0 :  $options['flag']; 
        $expire = empty($options['expire']) ? 0 :  $options['expire']; 

        if (is_array($key)) {
            $kvs = array();
            foreach ($key as $idx => $kn) {
                $kvs[$kn] = $value[$idx];
            }

            return $c->setMulti($kvs, $expire);
        }

        return $c->set($key, $value, $expire);
    }


	/**
	 * 获得值
	 *
	 * @param string $key
	 */
    public function get($key)
    {
        if (is_array($key)) {
            $c = $this->_cacher;
            if ($this->_isGroup) {
                $c = $this->_cacher['local'];
            }
            return $c->getMulti($key);
        }
        return parent::get($key);
   }

    /**
     * @status testing
     * 异步获取值
     * 
     * @param string $key
     */
    // public function getDelayed($key) {} 
    // public function fetch($key) {} 
    // public function fetchAll($key, $keyn = null) {}

    /**
     * compare-and-set 
     */
    public function cas($ucas, $key, $value, $expire = 0)
    {
        $c = $this->_isGroup ? $this->_cacher['local'] : $this->_cacher;
        return $c->cas($ucas, $key, $value, $expire);
    }

    /**
     * get-and-lock
     *
     * @return mixed $ucas
     * @return mixed return(x)
     */
    public function getAndLock($key, &$ucas, $lock_expire = 30)
    {
        $c = $this->_isGroup ? $this->_cacher['local'] : $this->_cacher;
        return $c->getAndLock($key, $ucas, $lock_expire);
    }

    /**
     * unlock the locked key
     */
    public function unlock($key, $ucas)
    {
        $c = $this->_isGroup ? $this->_cacher['local'] : $this->_cacher;
        return $c->unlock($key, $ucas);
    }

	/**
	 * 过期所有的变量
	 *
	 * @return bool
	 */
	/* public function flush() {} */

	/**
	 * 对保存的某个key中的值进行减法操作
	 *
	 * @param string $key
	 * @param int $value
	 * @return int
	 */
	/* public function dec($key, $value=1) {}*/


	/**
	 * 对保存的某个key中的值进行加法操作
	 *
	 * @param string $key
	 * @param int $value
	 * @return int
	 */
	/* public function inc($key, $value=1) {} */

	/**
	 * 对保存的某个key中的值进行替换操作
	 *
	 * @param string $key
	 * @param int $value
	 * @param array $options(flag,expire)
	 * @return boolean
	 */
	/* public function replace($key, $var, $options=array()) {} */

	/**
	 * 获得couchbase服务器失败后回调
	 *
	 * @param string $ip
	 * @param string $port
	 */
	/* static public function failureCallback($ip, $port) */
	/* { */
	/* 	if (_DEBUG_) { */
	/* 		var_dump( "$ip:$port" ); */
	/* 	} */
	/* } */

    public function append($key, $value)
    {
        $c = $this->_cacher;
        if ($this->_isGroup) {
            $c = $this->_cacher['local'];
        }

        return $c->append($key, $value);
    }

    public function prepend($key, $value)
    {
        $c = $this->_cacher;
        if ($this->_isGroup) {
            $c = $this->_cacher['local'];
        }

        return $c->prepend($key, $value);
    }

    /**
     * 执行view请求
     */
    public function view($doc_name, $view_name, $options)
    {
        $c = $this->_cacher;
        if ($this->_isGroup) {
            $c = $this->_cacher['local'];
        }

        return $c->view($doc_name, $view_name, $options);
    }

    /**
     * 生成view请求，但不执行
     */
    public function viewGenQuery($doc_name, $view_name, $options)
    {
        $c = $this->_cacher;
        if ($this->_isGroup) {
            $c = $this->_cacher['local'];
        }

        return $c->viewGenQuery($doc_name, $view_name, $options);
    }

    public function getStats()
    {
        $c = $this->_cacher;
        if ($this->_isGroup) {
            $c = $this->_cacher['local'];
        }
        return $c->getStats();
    }

    public function sversion()
    {
        $c = $this->_cacher;
        if ($this->_isGroup) {
            $c = $this->_cacher['local'];
        }

        return $c->getVersion();
    }

    public function cversion()
    {
        $c = $this->_cacher;
        if ($this->_isGroup) {
            $c = $this->_cacher['local'];
        }

        return $c->getClientVersion();
    }

    ///// rest api info
    public static function getRestAPIBase()
    {
        // curl http://10.207.0.203:8091/pools/default/ | json_reformat
        //      http://10.207.0.203:8091/pools/default/
		$config = require('config/cache.memcache.php');
        $node_server = $config['servers']['local'][0]['host'];
        $uri = "http://${node_server}:8091/pools";
        return $uri;
    }

    public static function getClusterInfo() 
    {
        // such as : http://10.207.0.203:8091/pools
        $rauri = self::getRestAPIBase();

        $jres = file_get_contents($rauri);
        $pres = Util::jsonDecode($jres);
        
        return $pres;
    }

    public static function getBucketOperation()
    {
        // such as : http://10.207.0.203:8091/pools/default/
        $request = '/default/';
        $rauri = self::getRestAPIBase() . $request;
        // echo $rauri;

        $jres = file_get_contents($rauri);
        $pres = Util::jsonDecode($jres);
        
        return $pres;
    }

    public static function getNodeInfo()
    {
        // such as : http://10.207.0.203:8091/pools/nodes/
        $request = '/nodes/';
        $rauri = self::getRestAPIBase() . $request;
        // echo $rauri;

        $jres = file_get_contents($rauri);
        $pres = Util::jsonDecode($jres);
        
        return $pres;        
    }


    public static function getBucketInfo($bucket_name = '')
    {
        // such as : http://10.207.0.203:8091/pools/default/buckets/[bucketxx]
        $request = "/default/buckets/${bucket_name}";
        $rauri = self::getRestAPIBase() . $request;
        // echo $rauri;

        $jres = file_get_contents($rauri);
        $pres = Util::jsonDecode($jres);
        
        return $pres;
    }

    
    public static function getBucketStats($bucket_name)
    {
        // such as : http://10.207.0.203:8091/pools/default/buckets/bucketxx
        $request = '/default/buckets/${bucket_name}/stats';
        $rauri = self::getRestAPIBase() . $request;
        // echo $rauri;

        $jres = file_get_contents($rauri);
        $pres = Util::jsonDecode($jres);
        
        return $pres;
    }
    
    public static function getRebanlanceProgress()
    {
        // such as : http://10.207.0.203:8091/pools/default/rebalanceProgress
        $request = '/default/rebalanceProgress';
        $rauri = self::getRestAPIBase() . $request;
        // echo $rauri;

        $jres = file_get_contents($rauri);
        $pres = Util::jsonDecode($jres);
        
        return $pres;
    }
}

