<?php
/**
 * 框架公共函数定义
 * @author liuxp
 * @version $Id: functions.php 40215 2013-03-24 05:37:24Z guangzhao $
 */

/**
 * 快速文件数据读取和保存 针对简单类型数据 字符串、数组
 *
 * @param string $name    文件名
 * @param mixed  $value   null：删除文件｜''：读取文件内容｜其它：写入内容
 * @param string $path    文件目录
 * @return mixed
 */
function F($name,$value='',$path=_CACHE_DIR_)
{
    static $_cache = array();
    $filename   =   $path . _DIR_SEPARATOR_ . $name .'.php';
    if('' !== $value)
    {
        if(is_null($value))
        {
            // 删除缓存
            return unlink($filename);
        }
        else
        {
            // 缓存数据
            $dir = dirname($filename);
            // 目录不存在则创建
            if(!is_dir($dir))
            {
            	mk_dir($dir);
            }

            return file_put_contents(
                $filename,
                "<?php\nreturn ".var_export($value,true).";\n?>"
            );
        }
    }

    if(isset($_cache[$name]))
    {
    	return $_cache[$name];
    }

    // 获取缓存数据
    if(is_file($filename))
    {
        $value =  include $filename;
        $_cache[$name] = $value;
    }
    else
    {
        $value = false;
    }

    return $value;
}

/**
 * 获取配置
 * @param string $fileName   文件名
 * @param string $key        数组下标，默认为空，即返回全部，否则返回指定下标值
 * @param string $dir        查找路径，默认查找路径： 应用名/模块名/config/文件名；
 *                                                     应用名/_config/文件名；
 *                                                     config/文件名;
 * @return mixed             如果文件存在返回数组，否则返回false;
 * @example
 * 文件user.php内容如：
 * <?php
 *  return array('name' => 'admin','ext' => array('city'=>'beijing', 'post'=>'100000'));
 * 获取代码：
 * $user = C('user.php');//获取用户全部信息
 * $userExtInfo = C('test.php', 'ext');//获取用户其它信息
 */
function C($fileName, $key = '', $dir = '')
{
    static $_CONFIG;
    if('' != $dir)
    {
        $file = $dir . _DIR_SEPARATOR_ . $fileName;
        if(is_file($file))
        {
            $config = $file;
        }
        else
        {
            return false;
        }
    }
    else
    {
        if(is_file(_APP_
            .$GLOBALS['APPLICATION']._DIR_SEPARATOR_
            .'config'._DIR_SEPARATOR_
            .$fileName))
        {
            $config = _APP_ .$GLOBALS['APPLICATION'] . _DIR_SEPARATOR_
                      . 'config' . _DIR_SEPARATOR_ . $fileName;
        }
        elseif(is_file(_APP_._DIR_SEPARATOR_
            .'_config'._DIR_SEPARATOR_
            .$fileName))
        {
            $config = _APP_ . _DIR_SEPARATOR_ . '_config'
                      . _DIR_SEPARATOR_ . $fileName;
        }
        elseif(is_file(_CONFIG_ . $fileName))
        {
            $config = _CONFIG_ . $fileName;
        }
        else
        {
            return false;
        }
    }

    $mkey = md5($config);
    if(!isset($_CONFIG[$mkey]))
    {
        $_CONFIG[$mkey] = require($config);
    }

    if('' == $key)
    {
        return $_CONFIG[$mkey];
    }
    else
    {
        return isset($_CONFIG[$mkey][$key])? $_CONFIG[$mkey][$key]: false;
    }
}

/**
 * 写文件或删除文件
 *
 * @param string $name    文件名
 * @param string $content 文件内容，默认为''，表示删除指定文件
 * @param string $path    目录名
 * @return boolean
 */
function W($name, $content='', $path = _CACHE_DIR_)
{
    $name = str_replace("\\", '/', $name);
    $path = str_replace("\\", '/', $path);
    if(substr($path, -1) == '/') {
        $path = substr($path, 0, -1);
    }
    if(substr($name, 0, 1) == '/') {
        $name = substr($name, 1);
    }
    $filename = $path . '/'. $name;

    if('' == $content) {
        // 文件
        return unlink($filename);
    }else{
        $dir = dirname($filename);
        // 目录不存在则创建
        if(!is_dir($dir)) {
        	mk_dir($dir);
        }
        return file_put_contents($filename, $content);
    }
}

/**
 * 循环创建目录
 *
 * @param string $dir
 * @param int $mode
 * @return boolean
 */
function mk_dir($dir, $mode = 0755)
{
    if (is_dir($dir) || @mkdir($dir,$mode))
    {
        return true;
    }
    if (!mk_dir(dirname($dir),$mode)) {
        return false;
    }
    return @mkdir($dir,$mode);
}

/**
 * 字符串命名风格转换
 * @param string $name 字符串
 * @param integer $type 转换类型 ，
 * 	0 将驼峰命名风格转换为匈牙利命名风格，
 *  1 将匈牙利命名风格转换为驼峰命名风格
 * @return string
 */
function parse_name($name,$type=0)
{
    if($type) {
        return ucfirst(preg_replace("/_([a-zA-Z])/e", "strtoupper('\\1')", $name));
    }else{
        $name = preg_replace("/[A-Z]/", "_\\0", $name);
        return strtolower(trim($name, "_"));
    }
}

/**
 * 获取表单令牌值
 *
 * @return unknown
 */
function get_token_value()
{
	if (!defined('_TOKEN_TYPE_')) {
		defined('_TOKEN_TYPE_', 'md5');
	}

	if (_TOKEN_TYPE_ == 'md5') {
		$token = md5('LEJU_TOKEN'. time());
	} else {
		$token = sha1('LEJU_TOKEN'. time());
	}
	$_SESSION[_TOKEN_NAME_] = $token;
	return $token;
}

/**
 * load一个空的model,可以进行跨库操作
 *
 * @param array $db_config
 */
function load_empty_model($db_config=array(),$tableName='')
{
    return new Leb_Model($tableName, $db_config);
}

/**
 * 调试变量
 *
 * @param mixed $var         调试变量
 * @param boolean $display   是否在浏览器中显示，为false时，输出到信息变量X-LebPHP-Data中
 */
function debug($var, $display=true)
{
    Leb_Debuger::debug($var, $display);
}

/**
 * 递归将特殊字符为HTML字符编码
 *
 * @param array|string  $data
 * @return array|string
 */
function dhtmlspecialchars($data)
{
    if (is_array($data)) {
    	foreach ($data as $key => $value) {
    	    $data[$key] = dhtmlspecialchars($value);
    	}
    } else {
        $data = htmlspecialchars($data);
    }
    return $data;
}

/**
 * 递归将HTML字符编码还原
 *
 * @param array|string $data
 * @return array|string
 */
function dhtmlspecialchars_decode($data)
{
    if (is_array($data)) {
    	foreach ($data as $key => $value) {
    	    $data[$key] = dhtmlspecialchars_decode($value);
    	}
    } else {
        $data = htmlspecialchars_decode($data);
    }
    return $data;
}

/**
 * 生成URL
 *
 * @param string $action       动作名
 * @param string $controller   控制器名 ，可选，默认与当前控制器同名
 * @param string $application  模块名   ，可选，默认与当前模块名相同
 * @param array  $params       传递的参数，参数将以GET方法传递
 * @return string
 */
function build_url($action, $controller='', $application='', $param=array())
{
    $router = Leb_Router::getInstance();
	return $router->buildUrl($action, $controller, $application, $param);
}

/**
 * 多语言函数
 *
 * @param <string> $app            app
 * @param <type> $module        语言模块
 * @param <type> $lan           需要转义的key, 如果为'*'则输出全部语言数组
 * @param <type> $function       具体的方法名。
 *
 * @modify shiling              //模板里增加全局语言文件public.php
 */
function language($module, $lan = '*', $app = '', $controller = '' , $action = '', $dl = false)
{
    $defaultLanguage = 'cn';
    $unknown = $lan;//$unknown = 'unknown';//当需要转义的字符串找不到时返回的默认值
    if(!$app) $app = get_gvar('APPLICATION');

    $lanData = array();

    if(!$dl){
        $language = isset($GLOBALS['LANGUAGE']) ? $GLOBALS['LANGUAGE'] : $defaultLanguage;
    }else {
        $language = $defaultLanguage;
    }

    $lanDir = _APP_ . '_language' . _DIR_SEPARATOR_ . $language;

    if (!is_dir($lanDir)){
        //语言包不存在，转为默认语言
        $language = 'cn';
        $lanDir = _APP_ . '_language' . _DIR_SEPARATOR_ . $language;
    }

    if($module == 'controller'){
        //对controller的转义
        $lanData = C($app. '.php', '', $lanDir);
        if(!isset ($lanData[$module])){
            return $unknown;
        }

        $lanData = $lanData[$module];
        $lan = $controller . '.' . $action . '.' . $lan;
    }elseif($module == 'model'){
        //对Model的转义
        if(!empty($app)){
            $lanData = C($app.'.php', '', $lanDir);
            $lanData = $lanData['model'];
        }
    }elseif($module == 'template'){
        //对模板的转义
        if(empty($app)){
           $lanData = C('template.php', '', $lanDir);
        }else {
            $lanData = C( $app . '.php', '', $lanDir);
        }
        $lanData = $lanData['template'];
        //私用语言文件覆盖全局文件
        $publicData = C('public.php', '', $lanDir);
        if(is_array($lanData) && is_array($publicData)) {
            $lanData = array_merge($publicData, $lanData);
        }
    }else {
        //对自定义语言模块的转义
        $lanData = C( $module . '.php', '', $lanDir);
        //var_dump($lanData);
    }

    if($lan == '*') {
        return $lanData;
    }
    if(isset ($lanData[$lan])){
        return $lanData[$lan];
    }

    //转义字符不存在，到默认语言包中寻找
    if($language != $defaultLanguage){
        $args = func_get_args();
        @$r = language($args[0], $args[1], $args[2], $args[3], $args[4], true);
        return $r;
    }

    return $unknown;
}

/**
 *
 * @return <float>
 */
function microtimeFloat()
{
    list($usec, $sec) = explode(" ", microtime());
    return ((float)$usec + (float)$sec);
}

/**
 *
 *
 * @return <array>
 */
function urldecodeUserInfo()
{
    if(isset($_COOKIE["user_info"])){
//         $user_info = urldecode(stripslashes($_COOKIE["user_info"]));
         return json_decode($_COOKIE["user_info"],true);
    }

}

/**
 * 获取客户端IP, 参考zend frmaework
 *
 * @param  boolean $checkProxy  是否检查代理
 * @return string
 */
function getClientIp($checkProxy = true)
{
    if ($checkProxy && isset($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } else if ($checkProxy
            && isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $ip = _CLI_ ? '' : $_SERVER['REMOTE_ADDR'];
    }

    return $ip;
}

//获取根域名
function get_domain($url)
{
    if(substr($url, 0, 4) == 'http') {
        $rs = parse_url($url);
        $host = $rs['host'];
    }elseif($index = strpos($url, '/')) {
        $host = substr($url, 0, $index);
    }else{
        $host = $url;
    }
    $arr = explode('.', $host);
    $last = array_pop($arr);
    $map = array('com','net','org','gov','cc','biz','info');
    $last2 = array_pop($arr);
    if(in_array($last2, $map)) {
        $last3 = array_pop($arr);
        $domain = $last3.'.'.$last2.'.'.$last;
    }else{
        $domain = $last2.'.'.$last;
    }
    return $domain;
}

define('IPT_ALL', 0);
define('IPT_INTERNAL', 1);
define('IPT_EXTERNAL', 2);
function get_local_ips($type = 0)
{
    // 可能的结果行格式
    // inet 10.207.15.55  netmask 255.255.255.0  broadcast 10.207.15.255
    // inet addr:10.207.16.254  Bcast:10.207.16.255  Mask:255.255.255.0
    $ncmd = "/sbin/ifconfig -a|grep 'inet '";
    $rlines = array();
    $rvar = 0;
    $rstr = exec($ncmd, $rlines, $rvar);

    $loip = '127.0.0.1';
    $inters = array('10.', '192.168.', '172.16.');

    if (!empty($rlines)) {
        foreach ($rlines as $k => $v) {
            $v = trim($v);
            $les = array();
            if (strchr($v, ':')) {
                $les = explode(':', $v);
                $les = explode(' ', 'fix ' . $les[1]);
            } else {
                $les = explode(' ', $v);
            }
            $ip = trim($les[1]);

            switch ($type) {
            case IPT_EXTERNAL:
                $is_inter = false;
                foreach ($inters as $ipp) {
                    if (strstr($ipp, $ip)) {
                        $is_inter = true;
                        break;
                    }
                }
                if (!$is_inter && $ip != $loip) {
                    $rlines[] = $ip;
                }
                break;
            case IPT_INTERNAL:
                foreach ($inters as $ipp) {
                    if (strstr($ipp, $ip)) {
                        $rlines[] = $ip;
                        break;
                    }
                }
                break;
            case IPT_ALL:
            default:
                $rlines[] = $ip;
                break;
            }
        }
    }

    return $rlines;
}

/**
 * 字符串返序列化
 * @param <type> $serial_str
 * @return <type>
 */
function mb_unserialize($serial_str)
{
    $out = preg_replace('/s:(\d+):"(.*?)";/se',
        "'s:'.strlen('$2').':\"$2\";'", $serial_str);
    return unserialize($out);
}

/**
 * 兼容的json编码
 *
 * @return string
 */
function leb_json_encode($value)
{
    $json = false;
    if (version_compare(PHP_VERSION, '5.4.0') >= 0) {
        $json = json_encode($value, JSON_UNESCAPED_UNICODE);
    } else if (version_compare(PHP_VERSION, '5.2.0') >= 0) {
        $json = json_encode($value);
    } else {
        // return false;
    }
    return $json;
}

/**
 * 兼容的json解码函数
 *
 * @return object
 */
function leb_json_decode($json)
{
    $value = false;
    if (version_compare(PHP_VERSION, '5.4.0') >= 0) {
        $value = json_decode($json, true, 512, JSON_BIGINT_AS_STRING);
    } else if (version_compare(PHP_VERSION, '5.2.0') >= 0) {
        $value = json_decode($json, true);
    } else {
        // return false;
    }
    return $value;
}

/**
 * json_last_error
 *
 * @return int
 */
function leb_json_last_error()
{
    return json_last_error();
}

/**
 * 返回json最后一次错误描述信息
 *
 * @return string
 */
function leb_json_last_strerror()
{
    $eno = json_last_error();

    $err = 'Unknown error.';

    switch($eno) {
    case JSON_ERROR_NONE:
        $err = 'No error has occurred';
        break;
    case JSON_ERROR_DEPTH:
        $err = 'The maximum stack depth has been exceeded';
        break;
    case JSON_ERROR_STATE_MISMATCH:
        $err = 'Invalid or malformed JSON';
        break;
    case JSON_ERROR_CTRL_CHAR:
        $err = 'Control character error, possibly incorrectly encoded';
        break;
    case JSON_ERROR_SYNTAX:
        $err = 'Syntax error';
        break;
    default:
        if(version_compare(PHP_VERSION, '5.3.3') >= 0 && JSON_ERROR_UTF8 == $eno)
        {
            $err = 'Malformed UTF-8 characters, possibly incorrectly encoded';
        }
    }

    return $err;
}

/**
 * 执行Worker指定的Action
 * @param host  gearman服务地址
 * @param port  gearman端口
 * @param app   应用ID
 * @param ctl   控制器ID
 * @param act   Action
 * @param param Action参数
 * @param sync  是否同步
 * @return mix
 */
function do_worker_action($host='', $port=0, $app='', $ctl='', $act='', $param=array(), $sync=false, $reg_func='action')
{
    if(empty($host))
    {
        $host = '127.0.0.1';
    }

    if(!$port)
    {
        $port = 4730;
    }

    $job = new GearmanClient();
    $job->addServer($host, $port);
    $data['act'] = $act;
    $data['ctl'] = $ctl;
    $data['app'] = $app;
    $data['param'] = $param;

    if($sync)
    {
        return json_decode($job->doNormal($reg_func, json_encode($data)), true);
    }
    else
    {
        $job->doBackground($reg_func, json_encode($data));
    }
}
