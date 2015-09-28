<?php
/**
 * Mysqli数据库驱动类
 *
 *
 * @category   Leb
 * @package    Leb_Db
 * @author     liuxp
 * @version    $Id: mysqli.php 4501 2012-06-01 08:33:19Z guangzhao $
 * @copyright
 * @license
 */
require_once('abstract.php');
class Leb_Dao_Mysqli extends Leb_Dao_Abstract
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
        if ( !extension_loaded('mysqli') ) {
            throw new Leb_Exception('系统未安装相应PHP扩展:mysqli');
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
        if ( !isset($this->linkID[$linkNum]) ) {
            if(empty($config))  $config = $this->config;
            $this->linkID[$linkNum] = new mysqli($config['host'],$config['username'],$config['password'],$config['dbname'],$config['port']);
            if (mysqli_connect_errno()) {
            	throw new Leb_Exception(mysqli_connect_error());
            }
            $dbVersion = $this->linkID[$linkNum]->server_version;
            if ($dbVersion >= "4.1") {
                // 设置数据库编码 需要mysql 4.1.0以上支持
                $this->linkID[$linkNum]->query("SET NAMES '".$config['charset']."'");
            }
            //设置 sql_model
            if($dbVersion >'5.0.1'){
                $this->linkID[$linkNum]->query("SET sql_mode=''");
            }
            // 标记连接成功
            $this->connected = true;

            //注销数据库安全信息
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
        mysqli_free_result($this->queryID);
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
     *
     * @throws Leb_Exception
     *
     */
    public function query($str, $type='assoc')
    {

        $this->initConnect(true);
        if ( !$this->_linkID ) return false;
        $this->queryStr = $str;
        //释放前次的查询结果
        if ( $this->queryID ) $this->free();
        $this->Q(1);
        $this->queryID = $this->_linkID->query($str);
        $this->debug();
        if ( false === $this->queryID ) {
            return false;
        } else {
            $this->numRows  = $this->queryID->num_rows;
            $this->numCols    = $this->queryID->field_count;
            return $this->getAll($type);
        }
    }

    /**
     *
     * 执行语句
     *
     * @access public
     *
     * @param string $str  sql指令
     *
     * @return integer
     *
     * @throws Leb_Exception
     *
     */
    public function execute($str)
    {
        $this->initConnect(true);
        if ( !$this->_linkID ) return false;
        $this->queryStr = $str;
        //释放前次的查询结果
        if ( $this->queryID ) $this->free();
        $this->W(1);
        $result =   $this->_linkID->query($str);
        $this->debug();
        if ( false === $result ) {
            return false;
        } else {
            $this->numRows = $this->_linkID->affected_rows;
            $this->lastInsID = $this->_linkID->insert_id;
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
        //数据rollback 支持
        if ($this->transTimes == 0) {
            $this->_linkID->autocommit(false);
        }
        $this->transTimes++;
        return ;
    }

    /**
     *
     * 用于非自动提交状态下面的查询提交
     *
     * @access public
     *
     * @return boolen
     *
     * @throws Leb_Exception
     *
     */
    public function commit()
    {
        if ($this->transTimes > 0) {
            $result = $this->_linkID->commit();
            $this->_linkID->autocommit( true);
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
     *
     * @return boolen
     *
     * @throws Leb_Exception
     *
     */
    public function rollback()
    {
        if ($this->transTimes > 0) {
            $result = $this->_linkID->rollback();
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
     *
     * @param  string $type   返回数据类型，默认为带下标的二数组
     *
     * @return array
     *
     */
    private function getAll($type) {
        //返回数据集
        $result = array();
        if($this->numRows>0) {
            //返回数据集
            if ('assoc' == $type) {
                for($i=0;$i<$this->numRows ;$i++ ){
                    $result[$i] = $this->queryID->fetch_assoc();
                }
            } else {
                for($i=0;$i<$this->numRows ;$i++ ){
                    $result[$i] = $this->queryID->fetch_row();
                }
            }
            
            $this->queryID->data_seek(0);
        }
        return $result;
    }

    /**
     *
     * 取得数据表的字段信息
     *
     * @access public
     *
     * @throws Leb_Exception
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
     * 取得数据表的字段信息
     *
     * @access public
     *
     * @throws Leb_Exception
     *
     */
    public function getTables($dbName='')
    {
        $sql = !empty($dbName)?'SHOW TABLES FROM '.$dbName:'SHOW TABLES ';
        $result = $this->query($sql);
        $info = array();
        if($result) {
            foreach ($result as $key => $val) {
                $info[$key] = current($val);
            }
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
     *
     * @return false | integer
     *
     */
    public function replace($data,$options=array())
    {
        foreach ($data as $key=>$val){
            $value   =  $this->parseValue($val);
            if(is_scalar($value)) { // 过滤非标量数据
                $values[] = $value;
                $fields[] = $this->addSpecialChar($key);
            }
        }
        $sql  = 'REPLACE INTO '.$this->parseTable($options['table']).' ('.implode(',', $fields).') VALUES ('.implode(',', $values).')';
        return $this->execute($sql);
    }

    /**
     *
     * 插入记录
     *
     * @access public
     *
     * @param mixed $datas 数据
     * @param array $options 参数表达式
     *
     * @return false | integer
     *
     */
    public function insertAll($datas,$options=array())
    {
        if(!is_array($datas[0])) return false;
        $fields = array_keys($datas[0]);
        array_walk($fields, array($this, 'addSpecialChar'));
        $values = array();
        foreach ($datas as $data){
            $value   =  array();
            foreach ($data as $key=>$val){
                $val   =  $this->parseValue($val);
                if(is_scalar($val)) { // 过滤非标量数据
                    $value[]   =  $val;
                }
            }
            $values[] = '('.implode(',', $value).')';
        }
        $sql = 'INSERT INTO ' . $this->parseTable($options['table'])
               . ' ('.implode(',', $fields).') VALUES '
               . implode(',',$values);
        return $this->execute($sql);
    }

    /**
     *
     * 关闭数据库
     *
     * @static
     * @access public
     * @throws Leb_Exception
     *
     */
    public function close()
    {
        if (!empty($this->queryID))
            $this->queryID->free_result();
        if ($this->_linkID && !$this->_linkID->close()){
            throw new Leb_Exception($this->error());
        }
        $this->_linkID = 0;
    }

    /**
     *
     * 数据库错误信息
     * 并显示当前的SQL语句
     * @static
     * @access public
     * @return string
     *
     */
    public function error()
    {
        $this->error = $this->_linkID->error;
        return $this->error;
    }

    /**
     *
     * SQL指令安全过滤
     *
     * @static
     * @access public
     *
     * @param string $str  SQL指令
     * @return string
     *
     */
    public function escape_string($str)
    {
        if($this->_linkID) {
            return  $this->_linkID->real_escape_string($str);
        }else{
            return addslashes($str);
        }
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
