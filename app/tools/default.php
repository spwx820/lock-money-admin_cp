<?php
/**
 * 清理缓存
 *
 * @category   Leb
 * @package    Leb_Action
 * @author     lihui
 * @version    $Id: default.php 2014-09-03 9:58:00 lihui
 * @copyright (c) 2014 dianjoy.com
 * @license
 */
class defaultController extends Application
{
    /**
     * 清空缓存
     */
    public function clearCacheAction()
    {
        $key = daddslashes($this->getVar('key'));

        //键名是:数据库名_表名
        if(empty($key)){
            return false;
        }

        $memcache = Leb_Dao_Memcache::getInstance();
        $result = $memcache->del($key);
        var_dump($result);
    }

    /**
     * 获取表结构
     */
    public function getCacheAction()
    {
        $key = daddslashes($this->getVar('key'));

        //键名是:数据库名_表名
        if(empty($key)){
            return false;
        }
        $memcache = Leb_Dao_Memcache::getInstance();
        $result = $memcache->get($key);
        var_dump($result);
    }

    /**
     * phpinfo
     */
    public function getPhpInfoAction()
    {
        if($_GET['debug'] == 'fdsafljofelwjfdlasjkfdlsajkfdlsjafdlsjfdlasfjdlsadianle')
        {
            var_dump($_SERVER);
        }
    }

}