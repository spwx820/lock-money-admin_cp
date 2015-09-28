<?php
/**
 * 存储器（含内存存储及文件存储）
 *
 * 由配置文件设定选择何种方式
 *
 * @category   Leb
 * @package    Leb_Cache
 * @author     guangzhao@leju.sina.com.cn.com
 * @version    $Id: redis.php 8418 2012-11-07 01:36:22Z guangzhao $
 * @copyright
 * @license
 */

/**
 * @method bool set(string $key, mixed $value) Set the string value in argument as value of the key
 * @method string|bool get(string $key)  Gets a value stored at key. If the key doesn't exist, FALSE is returned
 * @method bool setex(string $key, int $ttl, mixed $value) Set the string value in argument as value of the key, with a time to live
 * @method bool setnx(string $key, mixed $value) Set the string value in argument as value of the key if the key doesn't already exist in the database
 * @method int del(string $key) Remove specified keys
 * @method int delete(string $key) Remove specified keys (alias for del)
 * @-method RedisProxy multi(int $mode = Redis::MULTI) Enter transactional mode
 * @-method void exec() Executes a transaction
 * @-method void discard() Cancels a transaction
 * @method void watch(string $key) Watches a key for modifications by another client. If the key is modified between WATCH and EXEC, the MULTI/EXEC transaction will fail (return FALSE)
 * @-method void unwatch() Cancels all the watching of all keys by this client
 * @-method void subscribe(string $channels, string $callback) Subscribe to channels.
 * @-method void publish(string $channels, string $message) Publish messages to channels.
 * @method bool exists(string $key) Verify if the specified key exists
 * @method int incr(string $key) Increment the number stored at key by one. If the key does not exist it's value is initialized to be 0 first
 * @method int incrBy(string $key, int $value) Increment the number stored at key by the specified value. If the key does not exist it's value is initialized to be 0 first
 * @method int decr(string $key) Decrement the number stored at key by one. If the key does not exist it's value is initialized to be 0 first
 * @method int decrBy(string $key, int $value) Decrement the number stored at key by the specified value. If the key does not exist it's value is initialized to be 0 first
 * @method array getMultiple(array $keys) Get the values of all the specified keys. If one or more keys dont exist, the array will contain FALSE at the position of the key
 * @method int|bool lPush(string $key, mixed $value) Adds the string value to the head (left) of the list. Creates the list if the key didn't exist. If the key exists and is not a list, FALSE is returned
 * @method int|bool rPush(string $key, mixed $value) Adds the string value to the tail (right) of the list. Creates the list if the key didn't exist. If the key exists and is not a list, FALSE is returned
 * @method int|bool lPushx(string $key, mixed $value) Adds the string value to the head (left) of the list if the list exists
 * @method int|bool ePushx(string $key, mixed $value) Adds the string value to the tail (right) of the list if the list exists
 * @method string|bool lPop(string $key) Return and remove the first element of the list
 * @method string|bool rPop(string $key) Return and remove the last element of the list
 * @method array blPop(string $keys, int $timeout) Is a blocking lPop primitive. If at least one of the lists contains at least one element, the element will be popped from the head of the list and returned to the caller. If all the list identified by the keys passed in arguments are empty, blPop will block during the specified timeout until an element is pushed to one of those lists. This element will be popped
 * @method array brPop(string $keys, int $timeout) Is a blocking rPop primitive. If at least one of the lists contains at least one element, the element will be
 * @method int|bool lSize(string $key) Returns the size of a list identified by Key. If the list didn't exist or is empty, the command returns 0. If
 * the data type identified by Key is not a list, the command return FALSE
 * @method string|bool lIndex(string $key, int $index) Return the specified element of the list stored at the specified key. 0 the first element, 1 the second ... -1
 * the last element, -2 the penultimate ... Return FALSE in case of a bad index or a key that doesn't point to a
 * list
 * @method string|bool lGet(string $key, int $index) Return the specified element of the list stored at the specified key. 0 the first element, 1 the second ... -1
 * the last element, -2 the penultimate ... Return FALSE in case of a bad index or a key that doesn't point to a
 * list (alias of lIndex)
 * @method bool lSet(string $key, int $index, mixed $value) Set the list at index with the new value
 * @method array lRange(string $key, int $start, int $end) Returns the specified elements of the list stored at the specified key in the range [start, end]. start and
 * stop are interpreted as indices: 0 the first element, 1 the second ... -1 the last element, -2 the
 * penultimate ...
 * @method array lGetRange(string $key, int $start, int $end) Returns the specified elements of the list stored at the specified key in the range [start, end]. start and
 * stop are interpreted as indices: 0 the first element, 1 the second ... -1 the last element, -2 the
 * penultimate ... (alias of lRange)
 * @method bool lTrim(string $key, int $start, int $end) Trims an existing list so that it will contain only a specified range of elements
 * @method bool listTrim(string $key, int $start, int $end) Trims an existing list so that it will contain only a specified range of elements (alias of lTrim)
 * @method int|bool lRem(string $key, mixed $value, int $count) Removes the first count occurences of the value element from the list. If count is zero, all the matching
 * elements are removed. If count is negative, elements are removed from tail to head
 * @method int|bool lRemove(string $key, mixed $value, int $count) Removes the first count occurences of the value element from the list. If count is zero, all the matching
 * elements are removed. If count is negative, elements are removed from tail to head (alias of lRem)
 * @method int lInsert(string $key, string $position, string $pivot, mixed $value) Insert value in the list before or after the pivot value. the parameter options specify the position of the
 * insert (before or after). If the list didn't exists, or the pivot didn't exists, the value is not inserted
 * @method bool sAdd(string $key, mixed $value) Adds a value to the set value stored at key. If this value is already in the set, FALSE is returned
 * @method bool sRem(string $key, string $member) Removes the specified member from the set value stored at key
 * @method bool sRemove(string $key, string $member) Removes the specified member from the set value stored at key (alias for sRem)
 * @method bool sMove(string $srcKey, string $dstKey, string $member) Moves the specified member from the set at srcKey to the set at dstKey
 * @method bool sIsMember(string $key, mixed $value) Checks if value is a member of the set stored at the key key
 * @method bool sContains(string $key, mixed $value) Checks if value is a member of the set stored at the key key (alias for sIsMember)
 * @method int sCard(string $key) Returns the cardinality of the set identified by key
 * @method int sSize(string $key) Returns the cardinality of the set identified by key (alias for sCard)
 * @method string|bool sPop(string $key) Removes and returns a random element from the set value at key
 * @method string|bool sRandMember(string $key) Returns a random element from the set value at Key, without removing it
 * @-method array|bool sInter(string $key1, string $key2) Returns the members of a set resulting from the intersection of all the sets held at the specified keys. If
 * just a single key is specified, then this command produces the members of this set. If one of the keys is
 * missing, FALSE is returned
 * @-method int|bool sInterStore($dstKey, string $key1, string $key2) Performs a sInter command and stores the result in a new set
 * @-method array sUnion(string $key1, string $key2) Performs the union between N sets and returns it
 * @-method array sUnionStore(string $dstKey, string $key1, string $key2) Performs the same action as sUnion, but stores the result in the first key
 * @-method array sDiff(string $key1, string $key2) Performs the difference between N sets and returns it
 * @-method array sDiffStore($dstKey, string $key1, string $key2) Performs the same action as sDiff, but stores the result in the first key
 * @method array sMembers(string $key) Returns the contents of a set
 * @method array sGetMembers(string $key) Returns the contents of a set (alias for sMembers)
 * @method string getSet(string $key, mixed $value) Sets a value and returns the previous entry at that key
 * @-method string randomKey() Returns a random key
 * @-method bool select(int $dbIndex) Switches to a given database
 * @-method bool move(string $key, int $dbIndex) Moves a key to a different database
 * @-method bool rename(string $srcKey, string $dstKey) Renames a key
 * @-method bool renameNx(string $srcKey, $string dstKey) Same as rename, but will not replace a key if the destination already exists. This is the same behaviour as
 * setNx
 * @method bool setTimeout(string $key, int $ttl) Sets an expiration date (a timeout) on an item
 * @method bool expire(string $key, int $ttl) Sets an expiration date (a timeout) on an item (alias for setTimeout)
 * @method bool expireAt(string $key, int $timestamp) Sets an expiration date (a timestamp) on an item
 * @-method array keys(string $pattern) Returns the keys that match a certain pattern
 * @-method array getKeys(string $pattern) Returns the keys that match a certain pattern (alias for keys)
 * @-method int dbSize() Return the current database's size
 * @-method bool auth(string $password) Authenticate the connection using a password. Warning: The password is sent in plain-text over the network
 * @-method bool bgrewriteaof() Starts the background rewrite of AOF (Append-Only File)
 * @-method bool slaveof(string $host=null, int $port=6379) Change the slave status for the current host
 * @-method string|int|bool object($info, $key) Describes the object pointed to by a key ????
 * @-method bool save() Performs a synchronous save
 * @-method bool bgsave() Performs a background save
 * @-method int lastSave() Returns the timestamp of the last disk save
 * @method int type(string $key) Returns the type of data pointed by a given key
 * @method int append(string $key, string $value) Append specified string to the string stored in specified key
 * @method string getRange(string $key, int $start, int $end) Return a substring of a larger string
 * @method int setRange(string $key, int $offset, string $value) Changes a substring of a larger string
 * @method void strlen(string $key) Get the length of a string value
 * @method int getBit(string $key, int $offset) Return a single bit out of a larger string
 * @method int setBit(string $key, int $offset, int $value) Changes a single bit of a string
 * @method array ttl(string $key) Returns the time to live left for a given key, in seconds. If the key doesn't exist, FALSE is returned
 * @method array sort(string $key, array $options = array()) Sort a set and return the sorted members
 * @method bool persist(string $key) Remove the expiration timer from a key
 * @method bool mset(array $pairs) Sets multiple key-value pairs in one atomic command
 * @method bool msetnx(array $pairs) Sets multiple key-value pairs in one atomic command, setting only keys that did not exist
 * @method string|bool rpoplpush(string $srcKey, string $dstKey) Pops a value from the tail of a list, and pushes it to the front of another list. Also return this value
 * @method string|bool brpoplpush(string $srcKey, string $dstKey, int $timeout = 0.0) A blocking version of rpoplpush, with an integral timeout in the third parameter
 * @method int zAdd(string $key, float $score, mixed $value) Adds the specified member with a given score to the sorted set stored at key
 * @method array zRange(string $key, float $start, float $end, bool $withScores = false) Returns a range of elements from the ordered set stored at the specified key, with values in the range
 * [start, end]. start and stop are interpreted as zero-based indices: 0 the first element, 1 the second ...
 * -1 the last element, -2 the penultimate ...
 * @method int zDelete(string $key, string $member) Deletes a specified member from the ordered set
 * @method int zRem(string $key, string $member) Deletes a specified member from the ordered set (alias of zDelete)
 * @method array zRevRange(string $key, int $start, int $end, bool $withScores = false) Returns the elements of the sorted set stored at the specified key in the range [start, end] in reverse order.
 * start and stop are interpretated as zero-based indices: 0 the first element, 1 the second ... -1 the last
 * element, -2 the penultimate ...
 * @method int|bool zCount(string $key, float $start, float $end) Returns the number of elements of the sorted set stored at the specified key which have scores in the range
 * [start,end]. Adding a parenthesis before start or end excludes it from the range. +inf and -inf are also valid
 * limits
 * @method array zRangeByScore(string $key, float $start, float $end, $options = array()) Returns the elements of the sorted set stored at the specified key which have scores in the range [start, end].
 * Adding a parenthesis before start or end excludes it from the range. +inf and -inf are also valid limits
 *
 * @method int zRemRangeByScore(string $key, float $start, float $end) Deletes the elements of the sorted set stored at the specified key which have scores in the range [start, end]
 * @method int zDeleteRangeByScore(string $key, float $start, float $end) Deletes the elements of the sorted set stored at the specified key which have scores in the range [start, end]
 * (alias for zRemRangeByScore)
 * @method int|array zRemRangeByRank(string $key, float $start, float $end, array $options = array()) Deletes the elements of the sorted set stored at the specified key which have rank in the range [start, end]
 * @method int|array zDeleteRangeByRank(string $key, float $start, float $end, array $options = array()) Deletes the elements of the sorted set stored at the specified key which have rank in the range [start, end] (alias of zRemRangeByRank)
 * @method int|array zSize(string $key) Returns the cardinality of an ordered set
 * @method int zCard(string $key) Returns the cardinality of an ordered set (alias for zSize)
 * @method float zScore(string $key, string $member) Returns the score of a given member in the specified sorted set
 * @method float zRank(string $key, string $member) Returns the rank of a given member in the specified sorted set, starting at 0 for the item with the smallest score.
 * @method float zRevRank(string $key, string $member) Returns the rank of a given member in the specified sorted set in reverse order
 * @method float zIncrBy(string $key, mixed $value, string $member) Increments the score of a member from a sorted set by a given amount
 * @-method int zUnion(string $keyOutput, array $zSetKeys, array $weights = array(), string $function) Creates an union of sorted sets given in second argument. The result of the union will be stored in the sorted
 * @-method int zInter(string $keyOutput, array $zSetKeys, array $weights = array(), string $function) Creates an intersection of sorted sets given in second argument. The result of the union will be stored in the
 * sorted set defined by the first argument. The third optionnel argument defines weights to apply to the sorted
 * sets in input. In this case, the weights will be multiplied by the score of each element in the sorted set
 * before applying the aggregation. The forth argument defines the AGGREGATE option which specify how the results
 * of the union are aggregated
 * @method int|bool hSet(string $key, string $hashKey, mixed $value) Adds a value to the hash stored at key. If this value is already in the hash, FALSE is returned
 * @method bool hSetNx(string $key, string $hashKey, mixed $value) Adds a value to the hash stored at key only if this field isn't already in the hash
 * @method string|bool hGet(string $key, string $hashKey) Gets a value from the hash stored at key. If the hash table doesn't exist, or the key doesn't exist, FALSE is
 * returned
 * @method int|bool hLen(string $key) Returns the length of a hash, in number of items
 * @method bool hDel(string $key, string $hashKey) Removes a value from the hash stored at key. If the hash table doesn't exist, or the key doesn't exist, FALSE
 * is returned
 * @method array hKeys(string $key) Returns the keys in a hash, as an array of strings
 * @method array hVals(string $key) Returns the values in a hash, as an array of strings
 * @method array hGetAll(string $key) Returns the whole hash, as an array of strings indexed by strings
 * @method bool hExists(string $key, string $memberKey) Verify if the specified member exists in a key
 * @method int hIncrBy(string $key, string $member, mixed $value) Increments the value of a member from a hash by a given amount
 * @method bool hMset(string $key, array $members) Fills in a whole hash. Non-string values are converted to string, using the standard (string)cast. NULL
 * values are stored as empty strings
 * @method array hMget(string $key, array $memberKeys) Retrieve the values associated to the specified fields in the hash
 */

  /**
   * 公开类
   */
class Leb_Dao_Redis
{
    /**
	 * ExRedis对象
	 *
	 * @var ExRedis
	 */
	static protected  $_instance;
    protected $_cacher;
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
	static public function getInstance($config = array(), $options=array())
	{
		if (!isset(self::$_instance)) {
			self::$_instance = new self($config, $options);
		}
		return self::$_instance;
	}

	/**
	 * 创建CRedis对象
	 *
	 * @param array $options
	 * @return ExRedis
	 */
	protected function __construct($config = array(), $options=array())
	{
		if (empty($this->_cacher)) {
            if (empty($config)) {
                $config = require(_CONFIG_.'cache.redis.php');
            }
            $this->_config = $config;
			if (count($config['servers']) < 1) {
				throw new Leb_Exception('redis server not configed , please config it at config/cache.redis.php');
			}
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
		}
	}

    /**
     * 添加server
     * @param <type> $servers
     * @return CRedis
     */
    public function addServer($servers)
    {
        $cacheInstance = new Leb_Consistent_Redis();
        foreach ($servers as $server) {
            $cacheInstance -> addServer($server['host'], $server['port'], 6, $server['weight']);
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


	public function __call($method, $args)
    {
        $method = strtolower($method);
		if (!method_exists('Redis', $method)) {
			throw new Exception("Method '$method' does not exist for phpredis");
		}

        static $nonkey_methods = array('setoption', 'dbsize', 'randomkey', 'select', 
                                       'mget', 'mset', 'msetnx', 'getmultiple',
                                       'exec', 'discard', 'unwatch', 'multi', 'flushall',
                                );
        if (in_array($method, $nonkey_methods)) {
            // 这种方法也要转发到$redis上执行
        } else {
            if (!isset($args[0]) || !is_string($args[0])) {
                throw new Exception("Only methods with \$key as first parameter can be overloaded");
            }
        }

		if (empty($this->_cacher)) {
			return false;
		}

        if($this->_isGroup){
            foreach($this->_cacher as $k => $cacher){
                $result[$k] = call_user_func_array(array($cacher, $method), $args);
            }
            if(isset($result)){
                foreach($result as $k => $v){
                    if(false === $v){
                        return false;
                    }
                }
            }
            return  $return;
        }
        
        return call_user_func_array(array($this->_cacher, $method), $args);
	}

    
};

// 私有类
// 模拟memcache的addserver
// 自动通过 consistent hash方式定义操作所在节点
class Leb_Consistent_Redis
{
	protected $_pool = array(); // 'host:port' => $redis
    protected $_hash = null;

    public function __construct()
    {
        $this->_hash = new Flexihash(null, 16);
    }

	public function addServer($host = '127.0.0.1', $port = 6379, $dbIndex = 6, $weight = 1) {
		$redis = new Redis();
        $rhost = $host . ':' . $port;
		$connection_string = $rhost . '/' . $dbIndex;

        $redis_ext_refc = new ReflectionExtension('redis');
        if (version_compare($redis_ext_refc->getVersion(), '2.1.0') <= 0) {
            $bret = $redis->pconnect($host, $port, 0);
        } else {
            $bret = $redis->pconnect($host, $port, 0, $connection_string);
        }

        if ($bret) {
			$redis->select($dbIndex); // redis-proxy and high redis not support this anymore
            $this->_hash->addTarget($rhost);
            $this->_pool[$rhost] = $redis;
            return true;
        }
		unset($redis);
        return false;
    }

	public function __call($method, $args) {

		if (!method_exists('Redis', $method)) {
			throw new Exception("Method '$method' does not exist for phpredis");
		}
		if (!isset($args[0]) || !is_string($args[0])) {
			throw new Exception("Only methods with \$key as first parameter can be overloaded");
		}
		if (empty($this->_pool)) {
			return false;
		}

		$redis = $this->_pool[$this->_hash->lookup($args[0])];

        // 无参数方法
        static $trans_methods = array('exec', 'discard', 'unwatch', 'multi');
        if (in_array($method, $trans_methods)) {
            return call_user_func_array(array($redis, $method), array());
        } else {
            return call_user_func_array(array($redis, $method), $args);
        }
	}

    public function setOption($opname, $opvalue)
    {
        $bret = true;
        if (!empty($this->_pool)) {
            foreach ($this->_pool as $pn => $pc) {
                $breta = $pc->setOption($opname, $opvalue);
                $bret = $bret || $breta;
            }
        } else {
            return false;
        }
        return $bret;
    }

    /**
     * 清除所有redis缓存数据
     */
    public function flushAll()
    {
        $bret = true;
        if (!empty($this->_pool)) {
            foreach ($this->_pool as $pn => $pc) {
                $breta = $pc->flushAll();
                $bret = $bret || $breta;
            }
        } else {
            return false;
        }
        return $bret;
    }
};


/**
 * A simple consistent hashing implementation with pluggable hash algorithms.
 *
 * @author Paul Annesley
 * @package Flexihash
 * @licence http://www.opensource.org/licenses/mit-license.php
 */
class Flexihash
{

	/**
	 * The number of positions to hash each target to.
	 *
	 * @var int
	 */
	private $_replicas = 64;

	/**
	 * The hash algorithm, encapsulated in a Flexihash_Hasher implementation.
	 * @var object Flexihash_Hasher
	 */
	private $_hasher;

	/**
	 * Internal counter for current number of targets.
	 * @var int
	 */
	private $_targetCount = 0;

	/**
	 * Internal map of positions (hash outputs) to targets
	 * @var array { position => target, ... }
	 */
	private $_positionToTarget = array();

	/**
	 * Internal map of targets to lists of positions that target is hashed to.
	 * @var array { target => [ position, position, ... ], ... }
	 */
	private $_targetToPositions = array();

	/**
	 * Whether the internal map of positions to targets is already sorted.
	 * @var boolean
	 */
	private $_positionToTargetSorted = false;

	/**
	 * Constructor
	 * @param object $hasher Flexihash_Hasher
	 * @param int $replicas Amount of positions to hash each target to.
	 */
	public function __construct(Flexihash_Hasher $hasher = null, $replicas = null)
	{
		$this->_hasher = $hasher ? $hasher : new Flexihash_Crc32Hasher();
		if (!empty($replicas)) $this->_replicas = $replicas;
	}

	/**
	 * Add a target.
	 * @param string $target
         * @param float $weight
	 * @chainable
	 */
	public function addTarget($target, $weight=1)
	{
		if (isset($this->_targetToPositions[$target]))
		{
			throw new Flexihash_Exception("Target '$target' already exists.");
		}

		$this->_targetToPositions[$target] = array();

		// hash the target into multiple positions
		for ($i = 0; $i < round($this->_replicas*$weight); $i++)
		{
			$position = $this->_hasher->hash($target . $i);
			$this->_positionToTarget[$position] = $target; // lookup
			$this->_targetToPositions[$target] []= $position; // target removal
		}

		$this->_positionToTargetSorted = false;
		$this->_targetCount++;

		return $this;
	}

	/**
	 * Add a list of targets.
	 * @param array $targets
         * @param float $weight
	 * @chainable
	 */
	public function addTargets($targets, $weight=1)
	{
		foreach ($targets as $target)
		{
			$this->addTarget($target,$weight);
		}

		return $this;
	}

	/**
	 * Remove a target.
	 * @param string $target
	 * @chainable
	 */
	public function removeTarget($target)
	{
		if (!isset($this->_targetToPositions[$target]))
		{
			throw new Flexihash_Exception("Target '$target' does not exist.");
		}

		foreach ($this->_targetToPositions[$target] as $position)
		{
			unset($this->_positionToTarget[$position]);
		}

		unset($this->_targetToPositions[$target]);

		$this->_targetCount--;

		return $this;
	}

	/**
	 * A list of all potential targets
	 * @return array
	 */
	public function getAllTargets()
	{
		return array_keys($this->_targetToPositions);
	}

	/**
	 * Looks up the target for the given resource.
	 * @param string $resource
	 * @return string
	 */
	public function lookup($resource)
	{
		$targets = $this->lookupList($resource, 1);
		if (empty($targets)) throw new Flexihash_Exception('No targets exist');
		return $targets[0];
	}

	/**
	 * Get a list of targets for the resource, in order of precedence.
	 * Up to $requestedCount targets are returned, less if there are fewer in total.
	 *
	 * @param string $resource
	 * @param int $requestedCount The length of the list to return
	 * @return array List of targets
	 */
	public function lookupList($resource, $requestedCount)
	{
		if (!$requestedCount)
			throw new Flexihash_Exception('Invalid count requested');

		// handle no targets
		if (empty($this->_positionToTarget))
			return array();

		// optimize single target
		if ($this->_targetCount == 1)
			return array_unique(array_values($this->_positionToTarget));

		// hash resource to a position
		$resourcePosition = $this->_hasher->hash($resource);

		$results = array();
		$collect = false;

		$this->_sortPositionTargets();

		// search values above the resourcePosition
		foreach ($this->_positionToTarget as $key => $value)
		{
			// start collecting targets after passing resource position
			if (!$collect && $key > $resourcePosition)
			{
				$collect = true;
			}

			// only collect the first instance of any target
			if ($collect && !in_array($value, $results))
			{
				$results []= $value;
			}

			// return when enough results, or list exhausted
			if (count($results) == $requestedCount || count($results) == $this->_targetCount)
			{
				return $results;
			}
		}

		// loop to start - search values below the resourcePosition
		foreach ($this->_positionToTarget as $key => $value)
		{
			if (!in_array($value, $results))
			{
				$results []= $value;
			}

			// return when enough results, or list exhausted
			if (count($results) == $requestedCount || count($results) == $this->_targetCount)
			{
				return $results;
			}
		}

		// return results after iterating through both "parts"
		return $results;
	}

	public function __toString()
	{
		return sprintf(
			'%s{targets:[%s]}',
			get_class($this),
			implode(',', $this->getAllTargets())
		);
	}

	// ----------------------------------------
	// private methods

	/**
	 * Sorts the internal mapping (positions to targets) by position
	 */
	private function _sortPositionTargets()
	{
		// sort by key (position) if not already
		if (!$this->_positionToTargetSorted)
		{
			ksort($this->_positionToTarget, SORT_REGULAR);
			$this->_positionToTargetSorted = true;
		}
	}

}


/**
 * Hashes given values into a sortable fixed size address space.
 *
 * @author Paul Annesley
 * @package Flexihash
 * @licence http://www.opensource.org/licenses/mit-license.php
 */
interface Flexihash_Hasher
{

	/**
	 * Hashes the given string into a 32bit address space.
	 *
	 * Note that the output may be more than 32bits of raw data, for example
	 * hexidecimal characters representing a 32bit value.
	 *
	 * The data must have 0xFFFFFFFF possible values, and be sortable by
	 * PHP sort functions using SORT_REGULAR.
	 *
	 * @param string
	 * @return mixed A sortable format with 0xFFFFFFFF possible values
	 */
	public function hash($string);

}


/**
 * Uses CRC32 to hash a value into a signed 32bit int address space.
 * Under 32bit PHP this (safely) overflows into negatives ints.
 *
 * @author Paul Annesley
 * @package Flexihash
 * @licence http://www.opensource.org/licenses/mit-license.php
 */
class Flexihash_Crc32Hasher
	implements Flexihash_Hasher
{

	/* (non-phpdoc)
	 * @see Flexihash_Hasher::hash()
	 */
	public function hash($string)
	{
		return crc32($string);
	}

}


/**
 * Uses MD5 to hash a value into a 32bit binary string data address space.
 *
 * @author Paul Annesley
 * @package Flexihash
 * @licence http://www.opensource.org/licenses/mit-license.php
 */
class Flexihash_Md5Hasher
	implements Flexihash_Hasher
{

	/* (non-phpdoc)
	 * @see Flexihash_Hasher::hash()
	 */
	public function hash($string)
	{
		return substr(md5($string), 0, 8); // 8 hexits = 32bit

		// 4 bytes of binary md5 data could also be used, but
		// performance seems to be the same.
	}

}


/**
 * An exception thrown by Flexihash.
 *
 * @author Paul Annesley
 * @package Flexihash
 * @licence http://www.opensource.org/licenses/mit-license.php
 */
class Flexihash_Exception extends Exception
{
}


