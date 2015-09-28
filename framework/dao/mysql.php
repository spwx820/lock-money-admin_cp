<?php
/**
 * Mysql数据库驱动类
 *
 *
 * @category   Leb
 * @package    Leb_Db
 * @author     liuxp
 * @version    $Id: mysql.php 4501 2012-06-01 08:33:19Z guangzhao $
 * @copyright
 * @license
 */

define('CLIENT_MULTI_RESULTS', 131072);
require_once('abstract.php');
class Leb_Dao_Mysql extends Leb_Dao_Abstract
{

    /**
     *
     * 架构函数 读取数据库配置信息
     *
     * @access public
     *
     * @param array $config 数据库配置数组
     *
     */
    public function __construct($config='')
    {
        if ( !extension_loaded('mysql') ) {
            throw new Leb_Exception('系统未安装相应PHP扩展:mysql');
        }
        if(!empty($config)) {
            $this->config = $config;
        }
    }

    /**
     *
     * 连接数据库方法
     *
     * @access public
     *
     * @throws Leb_Exception
     *
     */
    public function connect($config='',$linkNum=0)
    {
        if ( !isset($this->linkID[$linkNum]) || !$this->myPing($this->linkID[$linkNum],'mysql') ) {
            if(empty($config)) {
                $config =   $this->config;
            }
            // 处理不带端口号的socket连接情况
            $host = $config['host'].($config['port']?":{$config['port']}":'');
            if($this->pconnect) {
                $this->linkID[$linkNum] = mysql_pconnect( $host, $config['username'], $config['password'],CLIENT_MULTI_RESULTS);
            }else{
                $this->linkID[$linkNum] = mysql_connect( $host, $config['username'], $config['password'],true,CLIENT_MULTI_RESULTS);
            }
            if ( !$this->linkID[$linkNum] || (!empty($config['dbname']) && !mysql_select_db($config['dbname'], $this->linkID[$linkNum])) ) {
                 throw new Leb_Exception(mysql_error());
            }
            $dbVersion = mysql_get_server_info($this->linkID[$linkNum]);
            if ($dbVersion >= "4.1") {
                //使用UTF8存取数据库 需要mysql 4.1.0以上支持
                mysql_query("SET NAMES '" . $config['charset'] . "'", $this->linkID[$linkNum]);
            }
            //设置 sql_model
            if($dbVersion >'5.0.1'){
                mysql_query("SET sql_mode=''",$this->linkID[$linkNum]);
            }
            // 标记连接成功
            $this->connected = true;
            // 注销数据库连接配置信息
            unset($this->config);
        }
        return $this->linkID[$linkNum];
    }

    /**
     *
     * 释放查询结果
     *
     * @access public
     *
     */
    public function free()
    {
        @mysql_free_result($this->queryID);
        $this->queryID = 0;
    }

    /**
     *
     * 执行查询 返回数据集
     *
     * @access public
     *
     * @param string $str  sql指令
     * @param  string $type   返回数据类型，默认为带下标的二数组
     * @return mixed
     * @throws Leb_Exception
     *
     */
    public function query($str, $type = 'assoc')
    {
        $this->initConnect(false);
        if ( !$this->_linkID ) {
            return false;
        }
        $this->queryStr = $str;
        //释放前次的查询结果
        if ( $this->queryID ) {
            $this->free();
        }
        $this->Q(1);
        $this->queryID = mysql_query($str, $this->_linkID);
        $this->debug();
        if ( false === $this->queryID ) {
            return false;
        } else {
            $this->numRows = mysql_num_rows($this->queryID);
            return $this->getAll($type);
        }
    }

    /**
     *
     * 执行语句
     *
     * @access public
     * @param string $str  sql指令
     * @return integer
     * @throws Leb_Exception
     *
     */
    public function execute($str)
    {
        $this->initConnect(true);
        if ( !$this->_linkID ) {
            return false;
        }
        $this->queryStr = $str;
        //释放前次的查询结果
        if ( $this->queryID ) {
        	$this->free();
        }
        $result = mysql_query($str, $this->_linkID) ;
        $this->debug();
        if ( false === $result) {
            return false;
        } else {
            $this->numRows = mysql_affected_rows($this->_linkID);
            $this->lastInsID = mysql_insert_id($this->_linkID);
            return $this->numRows;
        }
    }

    /**
     *
     * 启动事务
     *
     * @access public
     *
     * @return void
     *
     * @throws Leb_Exception
     *
     */
    public function startTrans()
    {
        $this->initConnect(true);
        if ( !$this->_linkID ) {
            return false;
        }
        //数据rollback 支持
        if ($this->transTimes == 0) {
            mysql_query('START TRANSACTION', $this->_linkID);
        }
        $this->transTimes++;
        return ;
    }

    /**
     *
     * 用于非自动提交状态下面的查询提交
     *
     * @access public
     * @return boolen
     * @throws Leb_Exception
     *
     */
    public function commit()
    {
        if ($this->transTimes > 0) {
            $result = mysql_query('COMMIT', $this->_linkID);
            $this->transTimes = 0;
            if(!$result){
                 throw new Leb_Exception($this->error());
            }
        }
        return true;
    }

    /**
     *
     * 事务回滚
     *
     * @access public
     * @return boolen
     * @throws Leb_Exception
     *
     */
    public function rollback()
    {
        if ($this->transTimes > 0) {
            $result = mysql_query('ROLLBACK', $this->_linkID);
            $this->transTimes = 0;
            if(!$result){
                 throw new Leb_Exception($this->error());
            }
        }
        return true;
    }

    /**
     *
     * 获得所有的查询数据
     *
     * @access private
     * @param  string $type   返回数据类型，默认为带下标的二数组
     * @return array
     * @throws Leb_Exception
     */
    private function getAll($type = 'assoc')
    {
        //返回数据集
        $result = array();
        if($this->numRows >0) {
            if ($type == 'assoc') {
                while($row = mysql_fetch_assoc($this->queryID)){
                    $result[]   =   $row;
                }
            } else {
                while($row = mysql_fetch_row($this->queryID)){
                    $result[]   =   $row;
                }

            }
            mysql_data_seek($this->queryID,0);
        }
        return $result;
    }

    /**
     *
     * 取得数据表的字段信息
     *
     * @access public
     *
     */
    public function getFields($tableName)
    {
        $result = $this->query('SHOW COLUMNS FROM '.$tableName);
        $info = array();
        if($result) {
            foreach ($result as $key => $val) {
                $info[$val['Field']] = array(
                    'name'    => $val['Field'],
                    'type'    => $val['Type'],
                    'notnull' => (bool) ($val['Null'] === ''), // not null is empty, null is yes
                    'default' => $val['Default'],
                    'primary' => (strtolower($val['Key']) == 'pri'),
                    'autoinc' => (strtolower($val['Extra']) == 'auto_increment'),
                );
            }
        }
        return $info;
    }

    /**
     *
     * 取得数据库的表信息
     *
     * @access public
     *
     */
    public function getTables($dbName='')
    {
        if(!empty($dbName)) {
           $sql = 'SHOW TABLES FROM '.$dbName;
        }else{
           $sql = 'SHOW TABLES ';
        }
        $result = $this->query($sql);
        $info   = array();
        foreach ($result as $key => $val) {
            $info[$key] = current($val);
        }
        return $info;
    }

    /**
     *
     * 替换记录
     *
     * @access public
     *
     * @param mixed $data 数据
     * @param array $options 参数表达式
     * @return false | integer
     *
     */
    public function replace($data,$options=array())
    {
        foreach ($data as $key=>$val){
            $value = $this->parseValue($val);
            if(is_scalar($value)) { // 过滤非标量数据
                $values[] = $value;
                $fields[] = $this->addSpecialChar($key);
            }
        }
        $sql = 'REPLACE INTO ' . $this->parseTable($options['table'])
                . ' ('.implode(',', $fields).') VALUES ('
                . implode(',', $values).')';
        return $this->execute($sql);
    }

    /**
     *
     * 插入记录
     *
     * @access public
     * @param mixed $datas 数据
     * @param array $options 参数表达式
     * @return false | integer
     *
     */
    public function insertAll($datas,$options=array())
    {
        if(!is_array($datas[0])) {
            return false;
        }
        $fields = array_keys($datas[0]);
        array_walk($fields, array($this, 'addSpecialChar'));
        $values = array();
        foreach ($datas as $data){
            $value = array();
            foreach ($data as $key=>$val){
                $val = $this->parseValue($val);
                if(is_scalar($val)) { // 过滤非标量数据
                    $value[] = $val;
                }
            }
            $values[] = '('.implode(',', $value).')';
        }
        $sql = 'INSERT INTO '.$this->parseTable($options['table'])
               . ' ('.implode(',', $fields).') VALUES '
               . implode(',',$values);
        return $this->execute($sql);
    }

    /**
     *
     * 关闭数据库
     *
     * @access public
     *
     * @throws Leb_Exception
     *
     */
    public function close()
    {
        if (!empty($this->queryID)){
            mysql_free_result($this->queryID);
        }

        if ($this->_linkID && !mysql_close($this->_linkID)){
            throw new Leb_Exception($this->error());
        }
        $this->_linkID = 0;
    }

    /**
     *
     * 数据库错误信息
     * 并显示当前的SQL语句
     *
     * @access public
     * @return string
     *
     */
    public function error()
    {
        $this->error = mysql_error($this->_linkID);
        return $this->error;
    }

    /**
     *
     * SQL指令安全过滤
     *
     * @access public
     *
     * @param string $str  SQL字符串
     *
     * @return string
     *
     */
    public function escape_string($str)
    {
        return mysql_escape_string($str);
    }

   /**
     *
     * 析构方法
     *
     * @access public
     *
     */
    public function __destruct()
    {
        // 关闭连接
        $this->close();
    }
}//类定义结束
