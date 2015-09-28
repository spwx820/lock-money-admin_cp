<?php
/**
 * 接口定义
 *
 * @category   Leb
 * @package    ILeb_Dao_Abstract
 * @author     ziyuan
 * @version    $Id: interfaces.php 29392 2013-01-31 08:22:18Z ziyuan $
 * @copyright
 * @license
 */

interface ILeb_Dao_Abstract
{
    // dao类型选择
    // true为启用缓存,false为直接访问数据库,
    const DAO_TYPE_NONE     = 0;  // 不做数据缓存
    const DAO_TYPE_BOTH     = 1;  // memcache与mysql的data表都存
    const DAO_TYPE_MEMCACHE = 2;  // 只存储在memcache中
    const DAO_TYPE_MYSQL    = 3;  // 只存储在mysql的data表中

    // model条件
    const DAO_OPT_TABLE     = 'table';
    const DAO_OPT_WHERE     = 'where';
    const DAO_OPT_LIMIT     = 'limit';
    const DAO_OPT_ORDER     = 'order';
    const DAO_OPT_LOCK      = 'lock';
    const DAO_OPT_FIELD     = 'field';
    const DAO_OPT_JOIN      = 'join';
    const DAO_OPT_GROUP     = 'group';
    const DAO_OPT_HAVING    = 'having';
    const DAO_OPT_DISTINCT  = 'distinct';
    const DAO_OPT_PARAM     = 'param';

    const DB_CFG_MASTER     = 'write';
    const DB_CFG_SLAVE      = 'read';
    const DB_CFG_DBNAME     = 'dbname';
}
