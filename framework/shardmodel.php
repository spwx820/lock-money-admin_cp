<?php
/**
 * 处理分库业务关系及数据关系的模型
 *
 *
 * @category   Leb
 * @package    Leb_ShardModel
 * @author     liuguangzhao
 * @version    $Id: shardmodel.php 52564 2013-05-24 09:23:09Z guangzhao $
 * @copyright
 * @license
 */

require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'model.php');

class Leb_ShardModel extends Leb_Model
{
    // shard 时可能需要连接多个db
    protected $_daoHash = array(); // vsid => dao
    // 物理shard列表
    protected $_phyShards = array(); // config/physhard.php
    // 虚拟shard列表, TODO global cached between xxxmodels
    private static $_virtShards = array(); // cached in redis now, key=$infokey
    // 虚拟shard数据源配置
    protected $_vsconfig = array();
    
    // 当前使用的虚拟shard编号
    protected $_virtShardId = '';
    // 是否开启全局序列ID自动生成
    protected $_globalIdMode = true;

    // 内部使用生成全局序列ID的常量值
    private $_shardAutoSerialKey = '';
    private $_shardAutoSerialIntKey = '';

    public function __construct($name='',$dbConfig='')
    {
        // 模型初始化
        $this->_initialize();
        $this->_shardAutoSerialKey = 'global_serial_generator_' . (isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : '127.0.0.1');
        $this->_shardAutoSerialIntKey = crc32($this->_shardAutoSerialKey);

        // 获取模型名称

        //使用默认配置
        if (empty($dbConfig)) {
            $dbConfig = require(_CONFIG_ . 'db.php');
        }
		
        $this->_phyShards = require(_CONFIG_ . 'physhard.php');
        // self::$_virtShards = require(_CONFIG_ . 'virtshard.php'); // cached now

        // 默认数据库初始化操作
        // 获取数据库操作对象
        // 当前模型有独立的数据库连接信息
        $this->_dao = Leb_Dao_Abstract::getInstance($dbConfig);
        $this->_virtShardId = 0;
        $this->_daoHash[0] = $this->_dao;
        $this->_dao->setGlobalIdMode($this->_globalIdMode);
        //如果使用index_mem_data 结构，则自动创建表结构,表大小默认为500W
        /** 暂时取消分表方案，如果分表，可以采用一索引对应一个数据表,或者采用表分区
        if($this->_daoType){
            $this->_dao->createHashTable($tablename="",$size="");
        }
         *
         */
        if (isset($dbConfig['debug'])) {
            self::$debug = $dbConfig['debug'];
            $this->_dao->debug = $dbConfig['debug'];
        }

        //表单令牌验证
        defined('_TOKEN_ON_') && self::$tokenOn = _TOKEN_ON_;
        defined('_TOKEN_NAME_') && self::$tokenName = _TOKEN_NAME_;

        isset($dbConfig['dbFieldtypeCheck']) && self::$dbFieldtypeCheck = $dbConfig['dbFieldtypeCheck'];

        // 设置表前缀，
        // TODO 需要更精确的检测，在扩展model中的设置应该优先于config/db.php中的设置
        // 目前，如果在扩展model中设置为''，则设置无效
        $modelName = get_class($this);
        $refc = new ReflectionClass($modelName);
        $tpprop = $refc->getProperty('_tablePrefix');
        $decl = $tpprop->getDeclaringClass();
        // if ($decl->name == $modelName) { // linux only
        if ($tpprop->class == $modelName) { // linux && window
            // 这种情况以扩展model优先，不需要把config/db.php中的设置覆盖过来
        } else {
            $this->_tablePrefix = $this->_tablePrefix?$this->_tablePrefix:@$dbConfig['tablePrefix'];
            $this->_tableSuffix = $this->_tableSuffix?$this->_tableSuffix:@$dbConfig['tableSuffix'];
        }
        $refc = null;

        //初始化数据库名
        if ('' == $this->_dbName) {
            if (isset($dbConfig['write']['dbname'])) {
                $this->_dbName = $dbConfig['write']['dbname'];
            } else {
                isset($dbConfig['dbname']) &&  $this->_dbName = $dbConfig['dbname'];
            }
        }

        $this->_dbConfig = $dbConfig;
        unset($dbConfig);

        // 字段检测
        if(!empty($this->_tableName) && $this->_autoCheckFields)
        {
            // $this->_checkTableInfo();
        }
    }

    /**
     * 切换虚拟shard，当前的数据库操作针对指定的shard
     *
     * 本方法根据配置文件(config/virtshard.php,config/physhard.php)自动对应到物理shard上
     * @param integer $vsid 虚拟shardID
     * @return true | false;
     */
    public function switchShard($vsid)
    {
        // 已经是当前shard了
        if ($this->_virtShardId == $vsid) {
            return true;
        }

        // 获取shard列表
        if (empty(self::$_virtShards)) {
            $this->getShardInfo();
        }

        if (!isset(self::$_virtShards['byid'])) {
            $this->_error = 'Invalid VirtShard info.';
            return false;
        }

        $idShards = self::$_virtShards['byid'];
        if (!isset($idShards[$vsid])) {
            $this->_error = "VirtShard:{$vsid} not exists.";
            return false;
        }

        // 设置当前shard信息，切换shard连接
        $psids = $idShards[$vsid];
        $read_psid = $psids['read_phy_shard_id'];
        $write_psid = $psids['write_phy_shard_id'];

        if (!isset($this->_phyShards[$read_psid]) || !isset($this->_phyShards[$write_psid])) {
            $this->_error = "PhyShard:{$read_psid},{$write_psid} not exists.";
            return false;
        }
            
        $psconfig = array('read' => $this->_phyShards[$read_psid],
                          'write' => $this->_phyShards[$write_psid]);

        if (isset($this->_daoHash[$vsid])) {
            $this->_dao = $this->_daoHash[$vsid];
            $this->_virtShardId = $vsid;
        } else {
            $dbConfig = $this->_dbConfig;

            foreach ($psconfig as $op => $tconfig) {
                $dsnps = explode(';', $tconfig);
                foreach ($dsnps as $idx => $pair) {
                    $epos = strpos($pair, '=');
                    $tkey = substr($pair, 0, $epos);
                    $tval = substr($pair, $epos + 1);
                    if ($tkey == 'dbuser') {
                        $dbConfig[$op]['username'] = $tval;
                    } else if ($tkey == 'dbpass') {
                        $dbConfig[$op]['password'] = $tval;
                    } else {
                        $dbConfig[$op][$tkey] = $tval;
                    }
                }
            }

            $this->_dao = Leb_Dao_Abstract::getInstance($dbConfig);

            if ($this->_dao) {
                $this->_dao->setGlobalIdMode($this->_globalIdMode);
                $this->_daoHash[$vsid] = $this->_dao;
                $this->_virtShardId = $vsid;
                if (isset($dbConfig['debug'])) {
                    self::$debug = $dbConfig['debug'];
                    $this->_dao->debug = $dbConfig['debug'];
                }
            } else {
                $this->_error = 'Can not abtain db instance.';
                return false;
            }
        }

        // 字段检测
        // TODO 切换可能很频繁，需要优化
        if(!empty($this->_tableName) && $this->_autoCheckFields) {
            $this->_checkTableInfo();
        }

        return true;
    }

    /**
     * 获取当前shard配置信息
     *
     */
    public function getCurrentShardInfo()
    {
        $shinfo = array('vsid' => $this->_virtShardId, 'dbconfig' => $this->_dbConfig);
        if ($this->_dao) {
            $shinfo['dbconfig'] = $this->_dao->getConfig();
        }

        return $shinfo;
    }


    /**
     * 生成一个新格式全局序列ID
     *
     * 序列类似MySQL的auto_increment。
     * @return 64-integer $nextval | false
     */
    /**
     * 格式：
     * (42B microtime) + (12B vsid) + (10B autoinc)
     */
    public function makeSerialId($vsid)
    {
        if (empty($vsid) || !is_numeric($vsid)) {
            return false;
        }

        if (!($vsid > 0 && $vsid <= 4096)) {
            return false;
        }
		
        $serial_key = $this->_shardAutoSerialKey;
        $auto_inc_sig = false;
        if (0) {
            $auto_inc_sig = $this->getNextValueByMemcache();
        } else {
            // $auto_inc_sig = $this->_getNextValueByLocalFile();
            $auto_inc_sig = $this->_getNextValueByShareMemory();
        }

        if (empty($auto_inc_sig)) {
            return false;
        }
		
        $ntime = microtime(true);
        $time_sig = intval($ntime * 1000);

        $serial_id = $time_sig << 12 | $vsid;
        $serial_id = $serial_id << 10 | ($auto_inc_sig % 1024);

        return $serial_id;
    }

    /**
     * 从新格式全局序列ID反解析出虚拟shard编号
     *
     * @param 64-integer $serialId 新格式全局序列ID
     * @return integer $vsid 虚拟shard编号，或者false
     */
    public function extractVirtShardId($serialId) 
    {
        if (empty($serialId) || !is_numeric($serialId)) {
            return false;
        }

        if ($this->isCompatSerialId($serialId)) {
            $oldId = 0;
            $flag = 0;
            $vsid = 0;

            if (!$this->extractCompatSerialInfo($serialId, $oldId, $flag, $vsid)) {
                return false;
            } else {
                return $vsid;
            }
        } else if ($this->isGlobalSerialId($serialId)) {
            $vsid = $serialId >> 10 & (0xFFF);
        } else {
            return false;
        }

        return $vsid;
    }

    /**
     * 判断是否是新格式的新格式的全局序列id
     */
    public function isGlobalSerialId($serialId)
    {
        $high28b = $serialId >> 36;
        if ($high28b == 0) {
            return false;
        }
        $high4b = $serialId >> 60 & 0xF; // 最高4位的值
        return $high4b != 0;
    }

    /**
     * 生成一个兼容老序列的新格式全局序列ID
     *
     * 序列类似MySQL的auto_increment。
     * @param integer $flag 原ID所属表编号，防止新兼容ID冲突
     * @return 64-integer $nextval | false
     */
    /**
     * 格式：
     * (4B 0) + (12B flag) + (12B vsid) + (36B old id)
     */
    public function makeCompatSerialId($oldId, $flag, $vsid)
    {
        if (empty($vsid) || !is_numeric($vsid)) {
            return false;
        }

        if (empty($flag) || !is_numeric($flag)) {
            return false;
        }

        if (!($vsid > 0 && $vsid <= 4096)) {
            return false;
        }

        if (!($flag > 0 && $vsid <= 4096)) {
            return false;
        }

        $serial_id = $flag << 12 | $vsid;
        $serial_id = $serial_id << 36 | $oldId;

        return $serial_id;
    }

    /**
     * 是否是兼容格式全局序列ID
     *
     * @param integer $serialId
     * @return true | false
     */
    public function isCompatSerialId($serialId)
    {
        $high28b = $serialId >> 36;
        if ($high28b == 0) {
            return false;
        }
        $high4b = $serialId >> 60 & 0xF; // 最高4位的值
        return $high4b == 0;
    }

    /**
     * 解析是兼容格式全局序列ID获取对应的信息
     *
     * @param integer $serialId
     * @param integer $oldId  老式36-integer
     * @param integer $flag   老式12-integer ID的类型标识
     * @param integer @vsid   该ID记录的虚拟shard编号,12-integer
     * (4B 0) + (12B flag) + (12B vsid) + (36B old id)
     * @return true | false
     */
    public function extractCompatSerialInfo($serialId, &$oldId, &$flag, &$vsid)
    {
        if (!$this->isCompatSerialId($serialId)) {
            return false;
        }

        $oldId = $serialId & 0xFFFFFFFFF;
        $vsid = $serialId >> 36 & 0xFFF;
        $flag = $serialId >> 48 & 0xFFF;

        return true;
    }

    // shardinfos
    public function getShardInfoByName($shardName, $shardValue)
    {
        $vsinfos = $this->getShardInfo();

        if (empty($vsinfos)) {
            return false;
        }

        $bynameKey = "{$shardName}_{$shardValue}";
        if (isset($vsinfos['byname']) && isset($vsinfos['byname'][$bynameKey])) {
            return $vsinfos['byname'][$bynameKey];
        }
        
        return false;        
    }

    public function getShardInfoById($shardId)
    {
        $vsinfos = $this->getShardInfo();
        if (empty($vsinfos)) {
            return false;
        }

        if (isset($vsinfos['byid'][$shardId])) {
            return $vsinfos['byid'][$shardId];
        }
        
        return false;
    }

    public function getShardInfo()
    {	
        if (empty(self::$_virtShards)) {
            $vsconfig = require(_CONFIG_ . DIRECTORY_SEPARATOR . 'shard.php');

            $this->_vsconfig = $vsconfig;
            $infokey = $vsconfig['infokey'];
            $info_uptime_key = $infokey . '_uptime';
            $redis = Leb_Dao_Redis::getInstance($vsconfig['redis']);
            // TODO 远程读取redis缓存的速度，和$value的大小关系非常大。
            // 所以还可以考虑把$value值压缩后存储，能从redis读取的时候快些。
            // 存储在本地磁盘还是快些，可以考虑再加一层缓存，
            // 通过本地更新时间与redis更新时间确定是否需要拿数据库新值。

            $vsinfos = array();
            if (($gz_jvsdata = $redis->get($infokey)) !== false) {
                $jvsdata = gzuncompress($gz_jvsdata);
                if ($jvsdata === false) {
                    $vsinfos = json_decode($gz_jvsdata, true);
                    if ($vsinfos) {
                        $redis->set($infokey, gzcompress($gz_jvsdata));
                    } else {
                        $this->_error = 'Unknown shard info cache format, no json, no gzjson.';


                    }
                } else {
                    $vsinfos = json_decode($jvsdata, true);
                }
            } else {
                $dbConfig = $vsconfig['database'];
                $dbh = Leb_Dao_Abstract::getInstance($dbConfig);
                if (!$dbh) {
                    return false;
                } else {
                    $sql = "SELECT * FROM `{$vsconfig['tablename']}` LIMIT 4096";
                    $vsrecords = $dbh->query($sql);
                    if (!$vsrecords) {
                        $dbh = null;
                        return false;
                    } else {
                        foreach ($vsrecords as $idx => $record) {
                            $vsinfos['byid'][$record['vsid']] = $record;
                            $bynameKey = "{$record['shard_name']}_{$record['shard_value']}";
                            $vsinfos['byname'][$bynameKey][$record['vsid']] = $record;
                            $vsinfos['uptime'] = time();
                        }
                        $bret = $redis->set($infokey, gzcompress(json_encode($vsinfos)));
                        $bret = $redis->set($info_uptime_key, $vsinfos['uptime']);
                    }
                }
            }

            if (!empty($vsinfos)) {
                self::$_virtShards = $vsinfos;
            }
        }

        return self::$_virtShards;
    }

    public function flushShardInfoCache()
    {
        if (empty($this->_vsconfig)) {
            $this->_vsconfig = $vsconfig = require(_CONFIG_ . DIRECTORY_SEPARATOR . 'shard.php');
        } else {
            $vsconfig = $this->_vsconfig;
        }

        $infokey = $vsconfig['infokey'];
        $redis = Leb_Dao_Redis::getInstance($vsconfig['redis']);
        $bret = $redis->delete($infokey);
        if (!$bret) {
            return false;
        }

        $oldVirtShards = self::$_virtShards;
        self::$_virtShards = array();
        self::$_virtShards = $this->getShardInfo();
        if (empty(self::$_virtShards)) {
            self::$_virtShards = $oldVirtShards;
            return false;
        }
        return true;
    }


    /**
     * 通过memcache来生成一个auto_increment序列，
     *
     * 序列类似MySQL的auto_increment。
     * @return integer $nextval | false
     */
    private function _getNextValueByMemcache()
    {
        $serial_key = $this->_shardAutoSerialKey;
        $cacher = Leb_Dao_Memcache::getInstance();
        $auto_inc_sig = $cacher->inc($serial_key);
        if ($auto_inc_sig == false) {
            $cacher->set($serial_key, 1);
            $auto_inc_sig = $cacher->inc($serial_key);
        }
        return $auto_inc_sig;
    }

    /**
     * 通过本地文件来生成一个auto_increment序列，
     *
     * 序列类似MySQL的auto_increment。
     * @return integer $nextval | false
     */
    private function _getNextValueByLocalFile()
    {
        $serial_key = $this->_shardAutoSerialKey;
        $autoinc_state_file = '';
        if (defined('_CACHE_DIR_')) {
            $autoinc_state_file = _CACHE_DIR_ . DIRECTORY_SEPARATOR . $serial_key;
        } else {
            $autoinc_state_file = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $serial_key;
        }

        $next_value = 0;
        $fp = fopen($autoinc_state_file, "c+");
        if (!$fp) {
            $this->_error = 'Can not create counter file.';
            return false;
        }

        fseek($fp, 0, SEEK_SET);
        if (flock($fp, LOCK_EX)) {
            $next_value = fread($fp, 32);
            fseek($fp, 0, SEEK_SET);
            if (empty($next_value)) {
                $next_value = 1;
                fwrite($fp, $next_value);
            } else {
                $next_value = intval($next_value) + 1;
                $nvl = strlen($next_value);
                $bret = fwrite($fp, "{$next_value}", $nvl);
                ftruncate($fp, $nvl);
            }
        } else {
            fclose($fp);
            return false;
        }

        fclose($fp);

        return $next_value;
    }

    /**
     * 通过本机共享内存件来生成一个auto_increment序列，
     *
     * 序列类似MySQL的auto_increment。
     * @return integer $nextval | false
     */
    private function _getNextValueByShareMemory()
    {
        $serial_key = $this->_shardAutoSerialIntKey;

        if (empty($serial_key)) {
            $this->_error = 'Invalid serial key' . $this->_shardAutoSerialKey . 'abc';
            return false;
        }
        
        $sem = $shm = null;
        $retry_times = 1;
        do {
            $sem = sem_get($serial_key, 1, 0777);
            $shm = shm_attach($serial_key, 128, 0777);

            if (is_resource($sem) && is_resource($shm)) {
                break;
            }

            $cmd = "ipcrm -M 0x00000000; ipcrm -S 0x00000000; ipcrm -M {$serial_key} ; ipcrm -S {$serial_key}";
            $last_line = exec($cmd, $output, $retval);

            // var_dump($last_line, $cmd, $output, $retval);

            if ($retval !== 0) {
                $this->_error = 'Can not create sem/shm resource.';
            }
        } while ($retry_times-- > 0);

        if (!sem_acquire($sem)) {
            $this->_error = 'System sem error.';
            return false;
        }

        $next_value = false;
        if (shm_has_var($shm, $serial_key)) {
            $next_value = shm_get_var($shm, $serial_key) + 1;
            shm_put_var($shm, $serial_key, $next_value);
        } else {
            $next_value = 1;
            shm_put_var($shm, $serial_key, $next_value);
        }

        shm_detach($shm);
        sem_release($sem);

        return $next_value;
    }
    
};

