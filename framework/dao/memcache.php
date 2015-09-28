<?php
/**
 * 存储器（含内存存储及文件存储）
 *
 * 由配置文件设定选择何种方式
 *
 * @category   Leb
 * @package    Leb_Cache
 * @version    $Id: memcache.php 50943 2013-05-18 15:18:12Z guangzhao $
 * @copyright
 * @license
 */

class Leb_Dao_Memcache
{
    /**
     * Memocache对象
     *
     * @var Memocache
     */
    static protected  $_instance = array(); // array(name => instance)
    protected $_cacher;
    protected $_name;

    /**
     *是否分组
     * @var <type>
     */
    protected $_isGroup = false;
    protected $_config = array();

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
     * 创建memcache对象
     *
     * @param array $options
     * @return memcache
     */

    protected function __construct($name = 'default', $options=array())
    {
        if (empty($this->_cacher)) {
            $this->_name = $name;

            $config = require(_CONFIG_.'/cache.memcache.php');
            $this->_config = $config;
            if (count($config['servers']) < 1) {
                throw new Leb_Exception('memcache server not configed , please config it at config/cache.memcache.php');
            }

            // 将非通用配置信息提取出来
            $spec_config = array();
            foreach ($config['servers'] as $tname => $tconfig) {
                if ($tname == 'local' || $tname == 'other') {
                } else {
                    $spec_config['servers'][$tname] = $tconfig;
                    unset($config['servers'][$tname]);
                }
            }

            if ($name == 'default') {
                if(count($config['servers'])>1){
                    $this->_isGroup = true;
                }
                if($this->_isGroup){
                    $cacheInstance['local'] = $this->addServer($config['servers']['local']);
                    $cacheInstance['other'] = $this->addServer($config['servers']['other']);
                }else{
                    $cacheInstance = $this->addServer($config['servers']['local']);
                }
                $this->_cacher = $cacheInstance;
            } else {
                if (isset($spec_config['servers'][$name])) {
                    $cacheInstance = $this->addServer($spec_config['servers'][$name]);
                } else {
                    $cacheInstance = null;
                }
                $this->_cacher = $cacheInstance;
            }
        }
    }

    /**
     * 添加server
     * @param <type> $servers
     * @return MemCache
     */
    public function addServer($servers)
    {
        $cacheInstance = new MemCache();
        foreach( $servers as $server )
        {
            $cacheInstance -> addServer(
                $server['host'],
                $server['port'],
                $server['lasting'],
                $server['weight'],
                $server['connectTime'],
                15,
                true,
                array('Leb_Dao_Memcache','failureCallback')
            );
        }
        return $cacheInstance;
    }

    /**
     * 取得该实例的server列表
     *
     * @return array
     */
    public function getServer()
    {
        return $this->_config;
    }

    /**
     * 由子方法实现的方法
     * @param string $key 键
     * @param mixed $value 值
     * @param array $options 设置条件，如什么时候过期，所在域等
     */
    public function set($key, $value, $options=array())
    {
        $return = true;
        $flag = empty($options['flag']) ? 0 :  $options['flag'];
        $expire = empty($options['expire']) ? 0 :  $options['expire'];
        if($this->_isGroup)
        {
            foreach($this->_cacher as $k => $cacher)
            {
                $result[$k] = $cacher->set($key, $value, $flag, $expire);
                $this->info('SET', $key, null!=$result[$k]);
            }

            if(isset($result))
            {
                foreach($result as $k => $v)
                {
                    if(false === $v)
                    {
                        return false;
                    }
                }
            }
            return  $return;
        }

        $btime = _DEBUG_ ? microtime(true) : 0;
        $return = $this->_cacher->set($key, $value, $flag, $expire);
        $this->info('SET:[t=' . (microtime(true)-$btime) . ']', $key, null!=$return);
        return $return;
    }

    /**
     * 获得值
     *
     * @param string $key
     */
    public function get($key)
    {
        $btime = _DEBUG_ ? microtime(true) : 0;

        $data = null;
        if($this->_isGroup)
        {
            $localCache = $this->_cacher['local'];
            $data = @$localCache->get($key);
        }
        else
        {
            $data = @$this->_cacher->get($key);
        }

        $this->info('GET:[' . (microtime(true)-$btime). ']', $key, null!=$data);
        return $data;
    }

    /**
     * 删除键
     *
     * @param string $key
     * @param string $timeout
     */
    public function del($keys, $timeout=0)
    {
        $return = true;
        //如果有多组
        if($this->_isGroup)
        {
            foreach($this->_cacher as $k => $cacher)
            {
                if (is_array($keys))
                {
                    foreach ($keys as $key)
                    {
                        $result[$k] = $cacher->delete($key, $timeout);
                        $this->info('DEL', $key, null!=$result[$k]);
                    }
                }
                else
                {
                    $result[$k] = $cacher->delete($keys, $timeout);
                    $this->info('DEL', $key, null!=$result[$k]);
                }
            }

            //获取结果
            if(isset($result))
            {
                foreach($result as $k => $v)
                {
                    if(false === $v)
                    {
                        return false;
                    }
                }
            }
            return  $return;
        }

        //如果只有一组
        if (is_array($keys))
        {
            foreach ($keys as $key)
            {
                $ret = $this->_cacher->delete($key, $timeout);
                $this->info('DEL', $key, null!=$ret);
            }
        }
        else
        {
            $return = $this->_cacher->delete($keys, $timeout);
            $this->info('DEL', $keys, null!=$return);
        }

        return $return;
    }

    /**
     * 过期所有的变量
     *
     * @return bool
     */
    public function flush()
    {
        $return = true;
        if($this->_isGroup){
            foreach($this->_cacher as $k => $cacher){
                $result[$k] = $cacher->flush();
            }
            //获取结果
            if(isset($result)){
                foreach($result as $k => $v){
                    if(false === $v){
                        return false;
                    }
                }
            }
            return $return;
        }
        return $this->_cacher->flush();
    }

    /**
     * 对保存的某个key中的值进行减法操作
     *
     * @param string $key
     * @param int $value
     * @return int
     */
    public function dec($key, $value=1)
    {
        if($this->_isGroup){
          foreach($this->_cacher as $k => $cacher){
              $result[$k] = $cacher->decrement($key,$value);
          }
          //获取结果
            if(isset($result)){
                foreach($result as $k => $v){
                    if(false === $v){
                        return false;
                    }
                }
            }
          return array_shift($result);
        }
        return $this->_cacher->decrement($key, $value);
    }


    /**
     * 对保存的某个key中的值进行加法操作
     *
     * @param string $key
     * @param int $value
     * @return int
     */
    public function inc($key, $value=1)
    {
        if($this->_isGroup){
          foreach($this->_cacher as $k => $cacher){
              $result[$k] = $cacher->increment($key,$value);
          }
          //获取结果
            if(isset($result)){
                foreach($result as $k => $v){
                    if(false === $v){
                        return false;
                    }
                }
            }

          return array_shift($result);
        }
        return $this->_cacher->increment($key, $value);
    }

    /**
     * 对保存的某个key中的值进行替换操作
     *
     * @param string $key
     * @param int $value
     * @param array $options(flag,expire)
     * @return boolean
     */
    public function replace($key, $var, $options=array())
    {
        $return =true;
        $flag = empty($options['flag']) ? 0 :  $options['flag'];
        $expire = empty($options['expire']) ? 0 :  $options['expire'];

        if($this->_isGroup){
            foreach($this->_cacher as $k => $cacher){
                $result[$k] = $cacher->replace($key, $var, $flag, $expire);
            }
            //获取结果
            if(isset($result)){
                foreach($result as $k => $v){
                    if(false === $v){
                        return false;
                    }
                }
            }
            return $return;
        }
        return $this->_cacher->replace($key, $var, $flag, $expire);
    }

    /**
     * 获得memcache服务器失败后回调
     *
     * @param string $ip
     * @param string $port
     */
    static public function failureCallback($ip, $port)
    {
        if (_DEBUG_) {
            var_dump( "$ip:$port" );
        }
    }

    /**
     * 输出log到http头中
     */
    private function info($op, $key, $exists=false, $host='', $port='')
    {
        if(defined('APP_TRACE'))
        {
            $obj = FirePHP::getInstance(true);
            $server = $host && $port ? '['.$host.':'.$port.']' : '';
            $k = is_array($key) ? implode(' ', $key) : $key;
            // $obj->info('Memcache:'.$server.'【'.$op.'】'.($exists ? 'OK ' : 'ER ').$k);
        }
    }
}

