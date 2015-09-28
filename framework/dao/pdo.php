<?php
/**
 * Pdo数据库驱动类
 *
 *
 * @category   Leb
 * @package    Leb_Db
 * @author     liuxp
 * @version    $Id: pdo.php 54006 2013-06-04 06:09:08Z ziyuan $
 * @copyright
 * @license
 */
require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR .'abstract.php');
class Leb_Dao_Pdo extends Leb_Dao_Abstract
{
    protected $PDOStatement = null;
    private   $table        = '';

    /**
     * 架构函数 读取数据库配置信息
     *
     * @access public
     * @param array $config 数据库配置数组
     */
    public function __construct($config='')
    {
        if(!class_exists('PDO'))
        {
            throw new Leb_Exception('系统未安装相应的PHP扩展:Pdo');
        }

        if(!empty($config))
        {
            $this->config = $config;
            if(empty($this->config[self::DB_CFG_PARAMS]))
            {
                $this->config[self::DB_CFG_PARAMS] = array();
            }
        }
    }

    /**
     * 连接数据库方法
     *
     * @throws Leb_Exception
     */
    public function connect($config='',$linkNum=0)
    {
        $btime = microtime(true);
        if (!isset($this->linkID[$linkNum])) {
            if(empty($config)) {
                $config = $this->config;
            }

            $poolKey = crc32(serialize($config));
            if(!isset(self::$daoPool[$poolKey]))
            {
                $this->getDbLink($poolKey, $config, $linkNum);
            }
            $this->linkID[$linkNum] = self::$daoPool[$poolKey];
        }
        $etime = microtime(true);
        $obj = FirePHP::getInstance(true);
        // $obj->info('DBconnect:'.($etime-$btime) . ", {$linkNum}");

        return $this->linkID[$linkNum];
    }

    private function getDbLink($poolKey, $config, $linkNum=0)
    {
        if($this->pconnect)
        {
            $config['params'][PDO::ATTR_PERSISTENT] = true;
        }

        try
        {
            if('ODBC' == $this->dbType)
            {
                $this->linkID[$linkNum] = new PDO('odbc:Driver={SQL Server}; '
                    .'Server='.$config[self::DB_CFG_HOST].'; '
                    .'Uid='.$config[self::DB_CFG_USERNAME].'; '
                    .'Pwd='.$config[self::DB_CFG_PASSWORD].'; '
                    .'Database='.$config[self::DB_CFG_DBNAME].';'
                );
            }
            else
            {
                $dsn = strtolower($this->dbType)
                    .':dbname='.$config[self::DB_CFG_DBNAME]
                    .';host='.$config[self::DB_CFG_HOST]
                    .';port='.$config[self::DB_CFG_PORT];

                self::$daoPool[$poolKey] = new ReconnectingPDO($dsn,
                    $config[self::DB_CFG_USERNAME],
                    $config[self::DB_CFG_PASSWORD],
                    $config[self::DB_CFG_CHARSET],
                    $config[self::DB_CFG_PARAMS]);
            }
        }catch (PDOException $e) {
            $this->error = $e->getMessage();
            throw new Leb_Exception($e->getMessage());
        }

        $this->connected = true;
    }

    /**
     * 释放查询结果
     */
    public function free()
    {
        $this->PDOStatement = null;
    }

    /**
     * 执行查询 返回数据集
     *
     * @param string $str  sql指令
     * @param  string $type   返回数据类型，默认为带下标的二数组
     * @return mixed
     * @throws Leb_Exception
     */
    public function query($str, $type='assoc', $param=array())
    {

        $this->initConnect(true);
        if ( !$this->_linkID ) {
            return false;
        }
        $this->queryStr = $str;
        //释放前次的查询结果
        if ( !empty($this->PDOStatement) ) {
            $this->free();
        }
        $this->Q(1);

        $btime = microtime(true);
        $this->PDOStatement = $this->_linkID->prepare($str);

        if(false === $this->PDOStatement) {
            throw new Leb_Exception($this->error());
        }

        $result = $this->PDOStatement->execute($param);
        $etime = microtime(true);
        $obj = FirePHP::getInstance(true);
        $obj->info('查询:'.($etime-$btime).' SQL:'.$str);

        $this->debug();
        if ( false === $result ) {
            $this->error();
            return false;
        } else {
            return $this->getAll($type);
        }
    }

    /**
     * 执行语句
     *
     * @param string $str  sql指令
     * @return integer
     * @throws Leb_Exception
     */
    public function execute($str, $param=array())
    {
        $this->initConnect(true);
        if ( !$this->_linkID ) {
            return false;
        }
        $this->queryStr = $str;
        $flag = false;
        if($this->dbType == 'OCI')
        {
            if(preg_match("/^\s*(INSERT\s+INTO)\s+(\w+)\s+/i", $this->queryStr, $match)) {
                $this->table = C("DB_SEQUENCE_PREFIX").str_ireplace(C("DB_PREFIX"), "", $match[2]);
                $flag = (boolean)$this->query("SELECT * FROM user_sequences WHERE sequence_name='" . strtoupper($this->table) . "'");
            }
        }

        //释放前次的查询结果
        if ( !empty($this->PDOStatement) ) {
            $this->free();
        }

        $btime = microtime(true);
        $this->PDOStatement = $this->_linkID->prepare($str);
        if(false === $this->PDOStatement) {
            throw new Leb_Exception($this->error());
        }

        $result = $this->PDOStatement->execute($param);
        $etime = microtime(true);
        $obj = FirePHP::getInstance(true);
        $obj->info('执行:'.($etime-$btime).' SQL:'.$str);
        $this->debug();
        if ( false === $result) {
            $this->error();
            return false;
        } else {
            //$this->numRows = $result;
            $this->numRows = $this->PDOStatement->rowCount();
            if($flag || preg_match("/^\s*(INSERT\s+INTO|REPLACE\s+INTO)\s+/i", $str)) {
                $this->lastInsID = $this->getLastInsertId();
            }
            return $this->numRows;
        }
    }

    /**
     * 启动事务
     *
     * @param string $name 事务名称，每个model事务有自己的连接
     * @return void
     */
    public function startTrans($name)
    {
        //数据rollback 支持
        if ($this->transTimes == 0) {
            $this->_oldLinkID = $this->_linkID;
            $dbh = $this->initConnect(true, $name);
            if ( !$this->_linkID ) {
                return false;
            }

            $this->_linkID->beginTransaction();
        }
        $this->transTimes++;
        return true;
    }

    /**
     * 用于非自动提交状态下面的查询提交
     *
     * @param string $name 事务名称，每个model事务有自己的连接
     * @return boolen
     */
    public function commit($name)
    {
        if ($this->transTimes > 0) {
            $result = $this->_linkID->commit();
            $this->transTimes = 0;
            if(!$result){
                throw new Leb_Exception($this->error());
            }
            $this->_linkID = $this->_oldLinkID;
        }
        return true;
    }

    /**
     * 事务回滚
     *
     * @param string $name 事务名称，每个model事务有自己的连接
     * @return boolen
     * @throws Leb_Exception
     */
    public function rollback($name)
    {
        if ($this->transTimes > 0) {
            $result = $this->_linkID->rollback();
            $this->transTimes = 0;
            if(!$result){
                throw new Leb_Exception($this->error());
            }
            $this->_linkID = $this->_oldLinkID;
        }
        return true;
    }

    /**
     * 获得所有的查询数据
     *
     * @return array
     * @throws Leb_Exception
     */
    private function getAll($type = 'assoc')
    {
        //返回数据集
        if ('assoc' == $type) {
            $result = $this->PDOStatement->fetchAll(constant('PDO::FETCH_ASSOC'));
        } else {

            $result = $this->PDOStatement->fetchAll(constant('PDO::FETCH_NUM'));
        }

        $this->numRows = count( $result );
        return $result;
    }

    /**
     * 取得数据表的字段信息
     *
     * @throws Leb_Exception
     */
    public function getFields($tableName)
    {
        $this->initConnect(true);
        switch($this->dbType) {
            case 'MSSQL':
            case 'ODBC' :
            case 'DBLIB':
                $sql = "SELECT   column_name as 'Name',   data_type as 'Type',   column_default as 'Default',   is_nullable as 'Null'
                          FROM    information_schema.tables AS t
                          JOIN    information_schema.columns AS c
                          ON  t.table_catalog = c.table_catalog
                          AND t.table_schema  = c.table_schema
                          AND t.table_name    = c.table_name
                          WHERE   t.table_name = '$tableName'";
                break;
            case 'SQLITE':
                $sql = 'PRAGMA table_info ('.$tableName.') ';
                break;
            case 'ORACLE':
            case 'OCI':
                $sql = "SELECT a.column_name \"Name\",data_type \"Type\",decode(nullable,'Y',0,1) notnull,data_default \"Default\",decode(a.column_name,b.column_name,1,0) \"pk\" "
                  ."FROM user_tab_columns a,(SELECT column_name FROM user_constraints c,user_cons_columns col "
                  ."WHERE c.constraint_name=col.constraint_name AND c.constraint_type='P' and c.table_name='".strtoupper($tableName)
                  ."') b where table_name='".strtoupper($tableName)."' and a.column_name=b.column_name(+)";
                break;
            case 'PGSQL':
                $sql = 'select fields_name as "Field",fields_type as "Type",fields_not_null as "Null",fields_key_name as "Key",fields_default as "Default",fields_default as "Extra" from table_msg('.$tableName.');';
                break;
            case 'IBASE':
                break;
            case 'MYSQL':
            default:
                $sql = 'DESCRIBE '.$tableName;
        }
        $result = $this->query($sql);
        $info = array();
        if($result) {
            foreach ($result as $key => $val) {
                $name= strtolower(isset($val['Field'])?$val['Field']:$val['Name']);
                $info[$name] = array(
                    'name'    => $name ,
                    'type'    => $val['Type'],
                    'notnull' => (bool)(((isset($val['Null'])) && ($val['Null'] === '')) || ((isset($val['notnull'])) && ($val['notnull'] === ''))),
                    'default' => isset($val['Default'])? $val['Default'] :(isset($val['dflt_value'])?$val['dflt_value']:""),
                    'primary' => isset($val['Key'])?strtolower($val['Key']) == 'pri':(isset($val['pk'])?$val['pk']:false),
                    'autoinc' => isset($val['Extra'])?strtolower($val['Extra']) == 'auto_increment':(isset($val['Key'])?$val['Key']:false),
                    'extra'   => isset($val['Extra']) ? $val['Extra'] : false
                );
            }
        }

        return $info;
    }

    /**
     * 取得数据库的表信息
     *
     * @throws Leb_Exception
     */
    public function getTables($dbName='')
    {

        switch($this->dbType) {
            case 'ORACLE':
            case 'OCI':
                $sql = 'SELECT table_name FROM user_tables';
                break;
            case 'MSSQL':
                $sql = "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE = 'BASE TABLE'";
                break;
            case 'PGSQL':
                $sql = "select tablename as Tables_in_test from pg_tables where schemaname ='public'";
                break;
            case 'IBASE':
                // 暂时不支持
                throw new Leb_Exception('系统暂不持'.':IBASE');
                break;
            case 'SQLITE':
                $sql = "SELECT name FROM sqlite_master WHERE type='table' "
                       . "UNION ALL SELECT name FROM sqlite_temp_master "
                       . "WHERE type='table' ORDER BY name";
                 break;
            case 'MYSQL':
            default:
                if(!empty($dbName)) {
                   $sql    = 'SHOW TABLES FROM '.$dbName;
                } else {
                   $sql    = 'SHOW TABLES ';
                }
        }

        $result = $this->query($sql);
        $info = array();
        foreach ($result as $key => $val) {
            $info[$key] = current($val);
        }
        return $info;
    }

    /**
     * limit分析
     *
     * @param mixed $lmit
     * @return string
     */
    protected function parseLimit($limit)
    {
        $limitStr = '';
        if(!empty($limit)) {
            switch($this->dbType){
                case 'PGSQL':
                case 'SQLITE':
                    $limit = explode(',',$limit);
                    if(count($limit)>1) {
                        $limitStr .= ' LIMIT '.$limit[1].' OFFSET '.$limit[0].' ';
                    }else{
                        $limitStr .= ' LIMIT '.$limit[0].' ';
                    }
                    break;
                case 'MSSQL':
                case 'ODBC' :
                case 'DBLIB':
                    return '';
                    break;
                case 'IBASE':
                    // 暂时不支持
                    break;
                case 'ORACLE':
                case 'OCI':
                    break;
                case 'MYSQL':
                default:
                    $limitStr .= ' LIMIT '.$limit.' ';
            }
        }
        return $limitStr;
    }


    /**
     * mssql limit 分析
     *
     * @param string $sql
     * @param string $limit
     * @return string
     */
    protected function mssqlLimit($sql, $limit)
    {
        if ( empty($limit) || !in_array($this->dbType, array('DBLIB', 'ODBC', 'MSSQL'))) {
            return $sql;
        }

        $limit = explode(',',$limit);
        if(count($limit)>1) {
            $count = $limit[1];
            $offset = $limit[0];
        }else{
            $count = $limit[0];
            $offset = 0;
        }

        $count = intval($count);
        if ($count <= 0) {
            throw new Leb_Exception("LIMIT argument count=$count is not valid");
        }

        $offset = intval($offset);
        if ($offset < 0) {
            throw new Leb_Exception("LIMIT argument offset=$offset is not valid");
        }

        $sql = preg_replace(
            '/^SELECT\s+(DISTINCT\s)?/i',
            'SELECT $1TOP ' . ($count+$offset) . ' ',
            $sql
            );

        if ($offset > 0) {
            $orderby = stristr($sql, 'ORDER BY');

            if ($orderby !== false) {
                $orderParts = explode(',', substr($orderby, 8));
                $pregReplaceCount = null;
                $orderbyInverseParts = array();
                foreach ($orderParts as $orderPart) {
                    $orderPart = rtrim($orderPart);
                    $inv = preg_replace('/\s+desc$/i', ' ASC', $orderPart, 1, $pregReplaceCount);
                    if ($pregReplaceCount) {
                        $orderbyInverseParts[] = $inv;
                        continue;
                    }
                    $inv = preg_replace('/\s+asc$/i', ' DESC', $orderPart, 1, $pregReplaceCount);
                    if ($pregReplaceCount) {
                        $orderbyInverseParts[] = $inv;
                        continue;
                    } else {
                        $orderbyInverseParts[] = $orderPart . ' DESC';
                    }
                }

                $orderbyInverse = 'ORDER BY ' . implode(', ', $orderbyInverseParts);
            }

            $sql = 'SELECT * FROM (SELECT TOP ' . $count . ' * FROM (' . $sql . ') AS inner_tbl';
            if ($orderby !== false) {
                $sql .= ' ' . $orderbyInverse . ' ';
            }
            $sql .= ') AS outer_tbl';
            if ($orderby !== false) {
                $sql .= ' ' . $orderby;
            }
        }

        return $sql;
    }


    /**
     * 关闭数据库
     */
    public function close()
    {
        $this->_linkID = null;
    }

    /**
     * 数据库错误信息
     * 并显示当前的SQL语句
     *
     * @return string
     */
    public function error()
    {
        if($this->PDOStatement) {
            $error = $this->PDOStatement->errorInfo();
            if(isset($error[2])) $this->error = $error[2];
        } else if ($this->_linkID) {
            $errinfo = $this->_linkID->errorInfo();
            $this->error = implode(';', $errinfo);
        }

        return $this->error;
    }

    /**
     * SQL指令安全过滤
     *
     * @access public
     * @param string $str  SQL指令
     * @return string
     */
    public function escape_string($str)
    {
         switch($this->dbType)
         {
            case 'SQLITE':
            case 'ORACLE':
            case 'OCI':
                return str_ireplace("'", "''", $str);
            case 'PGSQL':
            case 'MSSQL':
            case 'IBASE':
            case 'DBLIB':
            case 'ODBC' :
            case 'MYSQL':
            default :
//                if (_MAGIC_QUOTES_GPC_) {
//                    return $str;
//                } else {
                    return addslashes($str);
//                }
        }
    }

    /**
     * 获取最后插入id
     *
     * @access public
     * @return integer
     */
    public function getLastInsertId()
    {
         switch($this->dbType)
         {
            case 'PGSQL':
            case 'SQLITE':
            case 'MSSQL':
            case 'IBASE':
            case 'MYSQL':
                return $this->_linkID->lastInsertId();
            case 'ORACLE':
            case 'OCI':
                $sequenceName = $this->table;
                $vo = $this->query("SELECT {$sequenceName}.currval currval FROM dual");
                return $vo?$vo[0]["currval"]:0;
        }
    }

    public function createTable($tableName='')
    {
        switch($this->dbType) {
            case 'ORACLE':
            case 'OCI':
            case 'MSSQL':
            case 'PGSQL':
            case 'IBASE':
            case 'SQLITE':
            case 'MYSQL':
            default:
                   $sql = 'CREATE TABLE `'.$tableName.'` (`key` char(128) COMMENT "数据键",`value` text COMMENT "数据值")
                        ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT="资源索引表"';

        }

        $result = $this->query($sql);
        return $result;
    }

   /**
     * 析构方法
     *
     * @access public
     */
    public function __destruct()
    {
        // 关闭连接
        $this->close();
    }
}//类定义结束

// reconnect if gone away
class ReconnectingPDO
{
    private $_estate = '';
    private $_ecode = '';
    private $_emsg = '';
    protected $dsn, $username, $password, $pdo, $driver_options;
    const REQUERY_TIMES = 1;
    private $_pdo_refcount = 0;

    public function __construct($dsn, $username = '', $password = '', $charset='UTF8', $driver_options = array())
    {
        $this->dsn = $dsn;
        $this->username = $username;
        $this->password = $password;
        $this->driver_options = $driver_options;
        $this->driver_options[PDO::ATTR_ERRMODE] = PDO::ERRMODE_EXCEPTION;
        // $this->driver_options[PDO::ATTR_PERSISTENT] = false;
        if(!empty($charset))
        {
            $this->driver_options[PDO::MYSQL_ATTR_INIT_COMMAND] = 'SET NAMES '.$charset;
        }
    }

    public function __destruct()
    {
        $last_pdo = $this->pdo;
        $this->pdo = null;

        $this->_pdo_refcount = Util::refcount($last_pdo, $refs);
        if ($this->_pdo_refcount == 1) {
            // 应该会被php引擎回收了
        }
        $obj = FirePHP::getInstance(true);
        $obj->info('DB:pdo:des:ref:'."{$this->_pdo_refcount}, {$refs}");
    }

    public function __call($name, array $arguments)
    {
        /*
          // performance is slow
        try {
            $this->connection()->query("SHOW STATUS;")->execute();
        } catch(\PDOException $e) {
            if($e->getCode() != 'HY000' || !stristr($e->getMessage(), 'server has gone away')) {
                throw $e;
            }

            $this->reconnect();
        }
        1234
        */

        $result = false;
        $cnter = self::REQUERY_TIMES;
        do {
            try {
                $this->_estate = $this->_ecode = $this->_emsg = '';
                $result = call_user_func_array(array($this->connection(), $name), $arguments);
                if ($result instanceof PDOStatement) {
                    $result = new ReconnectingPDOStatement($result);
                }
                break;
            } catch(PDOException $e) {
                $this->_estate = $e->getCode();
                $this->_ecode = $e->getCode();
                $this->_emsg = $e->getMessage();
                if($e->getCode() != 'HY000' || !stristr($e->getMessage(), 'server has gone away')) {
                    // throw $e;
                    return false;
                }

                $this->reconnect();
            }
        } while($cnter-- > 0);

        return $result;
    }

    protected function connection()
    {
        return $this->pdo instanceof PDO ? $this->pdo : $this->connect();
    }

    protected function connect()
    {
        $dsn = $this->dsn.';'.time()%5;
        if($this->pdo)
        {
            $brk_pdo = $this->pdo;
            $this->pdo = null;
            //让php先回收已断开长连接资源
            $brk_pdo->setAttribute(PDO::ATTR_PERSISTENT, false);
            $this->_pdo_refcount = Util::refcount($brk_pdo, $refs);
            if ($this->_pdo_refcount == 1) {
                // 应该会被php引擎回收了
            }
            $obj = FirePHP::getInstance(true);
            $obj->info('DB:pdoref:'.", {$this->_pdo_refcount}, {$refs}");

            $this->pdo = new PDO($dsn, $this->username, $this->password, (array) $this->driver_options);
            // $this->pdo->setAttribute(PDO::ATTR_TIMEOUT, 3600);
            $this->pdo->setAttribute(PDO::ATTR_PERSISTENT, true);
        }
        else
        {
            $this->pdo = new PDO($dsn, $this->username, $this->password, (array) $this->driver_options);
            // $this->pdo->setAttribute(PDO::ATTR_TIMEOUT, 3600);
        }

        return $this->pdo;
    }

    protected function reconnect()
    {
        return $this->connect();
    }

    public function errorInfo()
    {
        if ($this->pdo) {
            $err = $this->pdo->errorInfo();
        } else {
            $err = array($this->_estate, $this->_ecode, $this->_emsg);
        }
        return $err;
    }
}

class ReconnectingPDOStatement
{
    private $_estate = '';
    private $_ecode = '';
    private $_emsg = '';

    private $_stmt = null;

    public function __construct($stmt) {
        $this->_stmt = $stmt;
    }

    public function __call($name, array $arguments) {
        $result = false;
        try {
            $this->_estate = $this->_ecode = $this->_emsg = '';
            $result = call_user_func_array(array($this->_stmt, $name), $arguments);
        } catch (PDOException $e) {
            $this->_estate = $e->getCode();
            $this->_ecode = $e->getCode();
            $this->_emsg = $e->getMessage();
            // var_dump($e);
        }
        return $result;
    }

    public function errorInfo()
    {
        if ($this->_stmt) {
            $err = $this->_stmt->errorInfo();
        } else {
            $err = array($this->_estate, $this->_ecode, $this->_emsg);
        }
        return $err;
    }
}
