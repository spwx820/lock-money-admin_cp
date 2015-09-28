<?php
/**
 * 应用公共函数定义
 * @author liuxp
 * @version $Id: functions.php 1 2011-04-08 07:42:35Z $
 */

/**
 * 使用反斜线引用数组中的字符串
 * @param array/string $string 需要转义的数组或字符串
 * @return array/string $string 转义后的数组或字符串
 */
function daddslashes($string, $force = 0)
{
    !defined('MAGIC_QUOTES_GPC') && define('MAGIC_QUOTES_GPC', get_magic_quotes_gpc());
    if (!MAGIC_QUOTES_GPC || $force){
        if (is_array($string)){
            foreach ($string as $key => $val){
                $string[$key] = daddslashes($val, $force);
            }
        }else {
            $string = addslashes($string);
        }
    }
    return $string;
}

/**
 * 通过相同的规则获得模板对应的表名
 *
 * @param int $t_id
 */
function get_template_table_name($t_id)
{
    if (empty($t_id)) {
    	return false;
    }
    return 'template_data_'.$t_id;
}

/**
 * 通过相同的规则获得模板域英文名
 *
 * @param int $tf_id
 */
function get_template_field_name($tf_id)
{
	if(empty($tf_id)){
		return false;
	}
	return 'tf_'.$tf_id;
}

/**
 *
 * 创建选择数据库字段类型的下拉列表
 *
 * @param string $name
 * @param string $id
 * @param string $selectec
 */
function db_field_type($name, $id = '', $selected = '')
{
    $id = empty($id) ? $name : $id;
    $field_type = array('tinyint','smallint','mediumint','int','integer','','bigint','real','double','float','decimal','numeric','char','varchar','date','','time','datetime','year','timestamp','tinytext','text','mediumtext','longtext','tinyblob','','blob','mediumblob','longblob','enum','set');
    $return = $if_selected = '';
    $return = "<select  name='{$name}' id='{$id}'>\n";
    foreach ($field_type as $type) {
    	if (!empty($selected) && $selected = $type) {
    		$if_selected = ' selected ';
    	}
    	$return .= "<option  value='tinyint' {$if_selected} >tinyint</option>\n";
    	$if_selected = '';
    }
    $return .= "</select>\n";
    return $return;
}


/**
 * 将二组数组转换成，指定下标值为索引的二维数组
 *
 * @param array  $data    数据,二维数组
 * @param string $index   做索引的下标值
 * @return array          返回值：array('index1' => array(),'index2'=> array())
 */
function index_array($data, $index)
{
    $data = (array) $data;
    $tData = array();
    foreach ($data as $value) {
        $tData[$value[$index]] = $value;
    }
    unset($data);
    return $tData;
}

/**
 * 将二组数组转换成，指定下标值为索引和值的一维数组
 *
 * @param array   $data    数据，二维数组
 * @param string  $index   做索引的下标值
 * @param string  $value   做值的下标值
 * @return array           返回值：array('index1'=> 'value1', 'index2'=> 'value2');
 */
function pair_array($data, $index, $value)
{
    $data = (array) $data;
    $tData = array();
    foreach ($data as $val) {
        $tData[$val[$index]] = $val[$value];
    }
    unset($data);
    return $tData;
}

/**
 * 注册模板域代码执行时所需的环境变量
 *
 * @param string $key
 * @param mixed $value
 * @param string $type
 */
function add_gvar($key, $value, $type = '')
{
    if ('' != $type) {
    	$GLOBALS[$type][$key] = $value;
    } else {
        $GLOBALS[$key] = $value;
    }
}

/**
 * 获取环境变量
 *
 * @param string $key
 * @param string $type
 */
function get_gvar($key, $type = '')
{
    if ('' != $type) {
    	return isset($GLOBALS[$type][$key])?
    	             $GLOBALS[$type][$key]:false;
    } else {
        return isset($GLOBALS[$key])?
                     $GLOBALS[$key]:false;
    }
}


//模板域算法相关函数

/**
 * 当请求成功时，返回值为指定URL的内容;当请求失败时，
 * 返回值为以“Error\n”的字符串，要获得该字符串，需要将isdie设置为‘0’;
 *
 * @param string  $url      请求的URL，必须是绝对URL
 * @param array   $param    参数列表，是Hash数组的引用
 * @param string  $method   请求的方法， 可选0- GET请求(默认请求方式),1- POST请求
 * @param int     $timeout  超时时间
 * @param array   $headers  HTTP头，是Hash数组的引用
 * @param int     $isdie    失败是否退出发布,
 *                           '1'，表示一旦失败则终止发布，此为默认值;
 *                           '0',请求失败也继续发布；
 * @return
 */
function get_url_data($url, $param = array(), $method = 0, $timeout=5, $headers = array(), $isdie=1)
{
    if (0 == $method) {
        $re = curl_get($url, $param, $headers, $timeout);
    } else {
        $re =  curl_post($url, $param, $headers, $timeout);
    }

    if (1 == $isdie && false === $re['result']) {
        exit(__FUNCTION__ . '函数操作失败：' . $re['error']);
    }

    if (false === $re['result']) {
        return $re['error'];
    } else {
        return $re['result'];
    }
}

/**
 * 多URL抓取函数，获取指定urllist的内容。如果urllist内的URL抓取成功则返回，
 * 否则抓取下一个URL；全部失败则报错、程序退出
 *
 * @param array   $url      请求的URL列表（数组），必须是绝对URL
 * @param array   $param    参数列表，是Hash数组的引用
 * @param string  $method   请求的方法， 可选0- GET请求(默认请求方式),1- POST请求
 * @param int     $timeout  超时时间
 * @param array   $headers  HTTP头，是Hash数组的引用
 * @param int     $isdie    失败是否退出发布,
 *                           '1'，表示一旦失败则终止发布，此为默认值;
 *                           '0',请求失败也继续发布；
 * @return
 */
function get_urllist_data($urllist, $param=array(), $method=0, $timeout=5, $headers=array(), $isdie=1)
{
    $errors = '';
    foreach ($urllist as $url) {
         if (0 == $method) {
            $re = curl_get($url, $param, $headers, $timeout, $isdie);
        } else {
            $re =  curl_post($url, $param, $header, $timeout, $isdie);
        }

        if (false === $re['result']) {
            0 == $isdie && $errors .= $url . ":\n" . $re['error'] . "\n";
        } else {
            return $re['result'];
        }
    }
    if (1 == $isdie) {
        exit( __FUNCTION__  . '函数操作失败');
    } else {
        return $errors;
    }

}

function curl_short($url)
{
    $ch2 = curl_init();
    curl_setopt($ch2, CURLOPT_URL, $url);
    curl_setopt($ch2, CURLOPT_HEADER, false);
    curl_setopt($ch2, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch2, CURLOPT_TIMEOUT, 1);
    $orders = curl_exec($ch2);
    curl_close($ch2);
}

/**
 * 提交GET请求，curl方法
 * @param string  $url       请求url地址
 * @param mixed   $data      GET数据,数组或类似id=1&k1=v1
 * @param array   $header    头信息
 * @param int     $timeout   超时时间
 * @param int     $port      端口号
 * @return array             请求结果,
 *                            如果出错,返回结果为array('error'=>'','result'=>''),
 *                            未出错，返回结果为array('result'=>''),
 */
function curl_get($url, $data = array(), $header = array(), $timeout = 1, $port = 80)
{
	$ch = curl_init();
    if (!empty($data)) {
        $data = is_array($data)?http_build_query($data): $data;
        $url .= (strpos($url,'?')?  '&': "?") . $data;
    }

	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_POST, 0);
    //curl_setopt($ch, CURLOPT_PORT, $port);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
	
    $result = array();
    $result['result'] = curl_exec($ch);
    if (0 != curl_errno($ch)) {
        $result['error']  = "Error:\n" . curl_error($ch);

    }
	curl_close($ch);
	return $result;
}


/**
 * 提交POST请求，curl方法
 * @param string  $url       请求url地址
 * @param mixed   $data      POST数据,数组或类似id=1&k1=v1
 * @param array   $header    头信息
 * @param int     $timeout   超时时间
 * @param int     $port      端口号
 * @return string            请求结果,
 *                            如果出错,返回结果为array('error'=>'','result'=>''),
 *                            未出错，返回结果为array('result'=>''),
 */
function curl_post($url, $data = array(), $header = array(), $timeout = 5, $port = 80)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
    //curl_setopt($ch, CURLOPT_PORT, $port);
    !empty ($header) && curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    
    $result = array();
    $result['result'] = curl_exec($ch);
    if (0 != curl_errno($ch)) {
        $result['error']  = "Error:\n" . curl_error($ch);

    }
	curl_close($ch);

    return $result;
}


/**
 * 对数组进行编码转换
 *
 * @param strint       $in_charset   输入编码
 * @param string       $out_charset  输出编码
 * @param string|array  $arr         输入数据
 * @return array                     返回数组
 */
function iconv_mixed($in_charset, $out_charset, $arr)
{
	if (strtolower($in_charset) == "utf8" || strtolower($in_charset) == 'utf-8') {
		$in_charset = "UTF-8";
	}

	if (is_array($arr)) {
		foreach ($arr as $key => $value) {
			$arr[$key] = iconv_mixed($in_charset, $out_charset . "//IGNORE", $value);
		}
	} else {
		if (!is_numeric($arr)) {
			$arr = iconv($in_charset, $out_charset . "//IGNORE", $arr);
		}
	}
	return $arr;
}

/**
 * 将utf8字符串转成gb2312
 * @param string $str
 * @return string
 */
function utf8_gb2312($str)
{
    return iconv_mixed('UTF-8', 'GB2312', $str);
}

/**
 * 将utf8字符串转成gbk
 * @param string $str
 * @return string
 */
function utf8_gbk($str)
{
    return iconv_mixed('UTF-8', 'GBK', $str);
}

/**
 * 将gb2312字符串转成utf8
 * @param string $str
 * @return string
 */
function gb2312_utf8($str)
{
    return iconv_mixed('GB2312', 'UTF-8', $str);
}

/**
 * 将gbk字符串转成utf8
 * @param string $str
 * @return string
 */
function gbk_utf8($str)
{
     return iconv_mixed('GBK', 'UTF-8', $str);
}

/**
 * 按UNICODE编码截取字符串前$length个字符
 * @param string $str
 * @param int $length
 */
function cn_substr($string, $length)
{
    if ($length == 0) {
        return '';
    }
    
	$newlength = 0;
	if (strlen($string) > $length) {
		for($i=0; $i < $length; $i++)
		{
            $a = base_convert(ord($string{$newlength}), 10, 2);
			$newlength++;
			$a = substr('00000000'.$a, -8);

            if (substr($a, 0, 1) == 0) {
                continue;
            } elseif (substr($a, 0, 3) == 110) {
				$newlength ++;
			} elseif (substr($a, 0, 4) == 1110) {
				$newlength += 2;
			} elseif (substr($a, 0, 5) == 11110) {
                $newlength += 3;
            } elseif (substr($a, 0, 6) == 111110) {
                $newlength += 4;
            } elseif (substr($a, 0, 7) == 1111110) {
                $newlength += 5;
            } else {
                $newlength ++;
            }

		}

		return substr($string, 0, $newlength);
	} else {
		return $string;
	}
}

/**
 * 对URL进行标准格式化
 * @param string   $url
 * @return $string
 */
function format_url($url)
{    
    $url = trim($url);
    $url = preg_replace('/([^:])\/{2,}/i', "\\1/", $url);
    return $url;
}

/**
 * 判断URL是否绝对路径
 * @param string    $url
 * @return boolean
 */
function is_abs_url($url)
{
    $url = trim($url);
    $result = false;
    if (preg_match('/[a-z]+:\/\/[a-z0-9]+/i', $url)) {
        $result = true;
    }
    
    return $result;
}

/**
 * 输出即显示(带chunked头信息，不支持内容压缩时使用)
 * @param mixed $string   输出内容
 * @return void
 */
function flush_output($str)
{
    if (is_string($str)) {
        echo $str;
    } else {
        $str = var_export($str, true);
        echo  '<pre>' . $str . '</pre>';
    }
    flush();
	@ob_flush();
}

/**
 * xml串转成数组
 * @param string $xml_string xml串
 * @return array $data       失败返回空数组
 */
function xml_array($xml_string)
{
    $xml_string = preg_replace('/(<\?xml\s+version=(\'|\")1.0(\'|\")\s+encoding=)([\"\'a-z-0-9]+)(\s*\?>)/i',
                               '$1"utf-8"$5', $xml_string);
    
    $xml_string = str_replace('<![CDATA[', '', $xml_string);
    $xml_string = str_replace(']]>', '', $xml_string);
    $xml = @simplexml_load_string( $xml_string );
    if (false === $xml) {
        return array();
    }
    $data = array();
    simple_xml_array($xml, $data);
    return $data;
}

/**
 * simplexml对象转成数组
 * @param object $simple_xml
 * @param array $data
 */
function simple_xml_array($simple_xml, &$data)
{
    $simple_xml = (array) $simple_xml;//var_dump($simple_xml);exit;
    foreach ($simple_xml as $k => $v){
        if ($k === '@attributes')
        {  
            continue;
        }
        $v = (array)$v;
        foreach ($v as $k1 => $v1){ 
            if ($k1 !== '@attributes') {
                if (is_array($v1)) {
                    $data[$k][$k1] = array();
                    simple_xml_array($v1,  $data[$k][$k1]);
                } elseif ($v1 instanceof SimpleXMLElement ) {
                    $k2 = $v1->getName();
                    $data[$k][$k2] = array();
                    simple_xml_array($v1, $data[$k][$k2]);
                } else {
                    $data[$k][$k1] = $v1;
                }
            }
        }
    }
}

/**
 * 调试变量
 * @param mixed $data   变量
 * @param string $label 变量描述
 */
function d($data, $label='')
{
	$ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && ($_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest');
	
	if(!$ajax) echo '<pre style="text-align: left;">';
	if($label) echo $label.'--------------------------<br/>';
	print_r($data);
	if(!$ajax) echo '</pre>';
}

/**
 * 返回json对象
 * @param bool $state   1或0
 * @param string $msg   返回信息
 */
function json($state, $msg = '')
{
    $ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && ($_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest');
    $data = array('state' => $state, 'msg' => $msg);
    if($ajax) {
        echo json_encode($data);
    }else {
        print_r($data);
    }
    exit;
}

/**
 * @desc 特殊字符转义
 * @param mixed $input
 * @return mixed
 */
function filter($input){
    if(is_array($input)){
        foreach($input AS $k => $v){
            $input[$k] = filter($v);
        }
    }else{
        $input = addslashes($input);
    }
    return $input;
}

/**
 * 获取客户端ip
 *
 * @return string
 */
function get_real_ip(){
    $ip = false;
    if(!empty($_SERVER["HTTP_CLIENT_IP"])){
        $ip = $_SERVER["HTTP_CLIENT_IP"];
    }
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])){
        $ips = explode (", ", $_SERVER['HTTP_X_FORWARDED_FOR']);
        if ($ip){
            array_unshift($ips, $ip);
            $ip = FALSE;
        }
        for ($i = 0; $i < count($ips); $i++){
            if (!preg_match ("/^(10|172\.16|192\.168)\.$/", $ips[$i])){
                $ip = $ips[$i];
                break;
            }
        }
    }
    return ($ip ? $ip : $_SERVER['REMOTE_ADDR']);
}

/**
 * 安全过滤函数
 *
 * @param $string
 * @return string
 */
function safe_replace($string) {
    $string = str_replace('%20','',$string);
    $string = str_replace('%27','',$string);
    $string = str_replace('%2527','',$string);
    $string = str_replace('*','',$string);
    $string = str_replace('"','&quot;',$string);
    $string = str_replace("'",'',$string);
    $string = str_replace('"','',$string);
    $string = str_replace(';','',$string);
    $string = str_replace('<','&lt;',$string);
    $string = str_replace('>','&gt;',$string);
    $string = str_replace("{",'',$string);
    $string = str_replace('}','',$string);
    $string = str_replace('\\','',$string);
    return $string;
}

/**
 * 获取当前页面完整URL地址
 */
function get_url() {
    $sys_protocal = isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == '443' ? 'https://' : 'http://';
    $php_self = $_SERVER['PHP_SELF'] ? safe_replace($_SERVER['PHP_SELF']) : safe_replace($_SERVER['SCRIPT_NAME']);
    $path_info = isset($_SERVER['PATH_INFO']) ? safe_replace($_SERVER['PATH_INFO']) : '';
    $relate_url = isset($_SERVER['REQUEST_URI']) ? safe_replace($_SERVER['REQUEST_URI']) : $php_self.(isset($_SERVER['QUERY_STRING']) ? '?'.safe_replace($_SERVER['QUERY_STRING']) : $path_info);
    return $sys_protocal.(isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '').$relate_url;
}

/**
 * 分页函数
 *
 * @param $num 信息总数
 * @param $curr_page 当前分页
 * @param $perpage 每页显示数
 * @param $urlrule URL规则
 * @param $array 需要传递的数组，用于增加额外的方法
 * @return 分页
 */
function pages($num, $curr_page, $perpage = 20, $urlrule = '', $array = array())
{
    if ($urlrule == '')
    {
        $urlrule = url_par('page={$page}');
    } else
    {
        $urlrule = url_par('page={$page}', $urlrule);
    }
    $multipage = '';
    if ($num > $perpage)
    {
        $page = 11;
        $offset = 4;
        $pages = ceil($num / $perpage);
        $from = $curr_page - $offset;
        $to = $curr_page + $offset;
        $more = 0;
        if ($page >= $pages)
        {
            $from = 2;
            $to = $pages - 1;
        } else
        {
            if ($from <= 1)
            {
                $to = $page - 1;
                $from = 2;
            } elseif ($to >= $pages)
            {
                $from = $pages - ($page - 2);
                $to = $pages - 1;
            }
            $more = 1;
        }
        $multipage .= '总数<b>' . $num . '</b>&nbsp;&nbsp;';
        if ($curr_page > 0)
        {
            if ($curr_page == 1)
            {
                $multipage .= ' <a href="' . pageurl($urlrule, 1, $array) . '" class="a1">上一页</a>';
                $multipage .= ' <span>1</span>';
            } elseif ($curr_page > 6 && $more)
            {
                $multipage .= ' <a href="' . pageurl($urlrule, $curr_page - 1, $array) . '" class="a1">上一页</a>';
                $multipage .= ' <a href="' . pageurl($urlrule, 1, $array) . '">1</a>..';
            } else
            {
                $multipage .= ' <a href="' . pageurl($urlrule, $curr_page - 1, $array) . '" class="a1">上一页</a>';
                $multipage .= ' <a href="' . pageurl($urlrule, 1, $array) . '">1</a>';
            }
        }
        for ($i = $from; $i <= $to; $i++)
        {
            if ($i != $curr_page)
            {
                $multipage .= ' <a href="' . pageurl($urlrule, $i, $array) . '">' . $i . '</a>';
            } else
            {
                $multipage .= ' <span>' . $i . '</span>';
            }
        }
        if ($curr_page < $pages)
        {
            if ($curr_page < $pages - 5 && $more)
            {
                $multipage .= ' ..<a href="' . pageurl($urlrule, $pages, $array) . '">' . $pages . '</a> <a href="' . pageurl($urlrule, $curr_page + 1, $array) . '" class="a1">下一页</a>';
            } else
            {
                $multipage .= ' <a href="' . pageurl($urlrule, $pages, $array) . '">' . $pages . '</a> <a href="' . pageurl($urlrule, $curr_page + 1, $array) . '" class="a1">下一页</a>';
            }
        } elseif ($curr_page == $pages)
        {
            $multipage .= ' <span>' . $pages . '</span> <a href="' . pageurl($urlrule, $curr_page, $array) . '" class="a1">下一页</a>';
        } else
        {
            $multipage .= ' <a href="' . pageurl($urlrule, $pages, $array) . '">' . $pages . '</a> <a href="' . pageurl($urlrule, $curr_page + 1, $array) . '" class="a1">下一页</a>';
        }
    }
    return $multipage;
}

/**
 * 返回分页路径
 *
 * @param $urlrule 分页规则
 * @param $page 当前页
 * @param $array 需要传递的数组，用于增加额外的方法
 * @return 完整的URL路径
 */
function pageurl($urlrule, $page, $array = array())
{
    if (strpos($urlrule, '#'))
    {
        $urlrules = explode('#', $urlrule);
        $urlrule = $page < 2 ? $urlrules[0] : $urlrules[1];
    }
    $findme = array('{$page}');
    $replaceme = array($page);
    if (is_array($array))
        foreach ($array as $k => $v)
        {
            $findme[] = '{$' . $k . '}';
            $replaceme[] = $v;
        }
    $url = str_replace($findme, $replaceme, $urlrule);

    if (is_array($array))
        foreach ($array as $k => $v)
        {
            $url = $url . "&$k=$v";
        }
    return $url;
}

/**
 * URL路径解析，pages 函数的辅助函数
 *
 * @param $par 传入需要解析的变量 默认为，page={$page}
 * @param $url URL地址
 * @return URL
 */
function url_par($par, $url = '')
{
    if ($url == '') $url = get_url();
    $pos = strpos($url, '?');
    if ($pos === false)
    {
        $url .= '?' . $par;
    } else
    {
        $querystring = substr(strstr($url, '?'), 1);
        parse_str($querystring, $pars);
        $query_array = array();
        foreach ($pars as $k => $v)
        {
            if ($k == 'page')
                continue;
            $query_array[$k] = $v;
        }
        $querystring = http_build_query($query_array) . '&' . $par;
        $url = substr($url, 0, $pos) . '?' . $querystring;
    }
    return $url;
}

//
//<div class="col-sm-7 pull-right">
//            <div class="dataTables_paginate paging_simple_numbers" id="example1_paginate">
//                <ul class="pagination">
//                    <li class="paginate_button previous disabled" id="example1_previous">
//                        <a href="#" aria-controls="example1" data-dt-idx="0" tabindex="0">Previous</a>
//                    </li>
//                    <li class="paginate_button active">
//                        <a href="#" aria-controls="example1" data-dt-idx="1" tabindex="0">1</a>
//                    </li>
//                    <li class="paginate_button">
//                        <a href="#" aria-controls="example1" data-dt-idx="2" tabindex="0">2</a>
//                    </li>
//                    <li class="paginate_button">
//                        <a href="#" aria-controls="example1" data-dt-idx="3" tabindex="0">3</a>
//                    </li>
//                    <li class="paginate_button">
//                        <a href="#" aria-controls="example1" data-dt-idx="4" tabindex="0">4</a>
//                    </li>
//                    <li class="paginate_button">
//                        <a href="#" aria-controls="example1" data-dt-idx="5" tabindex="0">5</a>
//                    </li>
//                    <li class="paginate_button">
//                        <a href="#" aria-controls="example1" data-dt-idx="6" tabindex="0">6</a>
//                    </li>
//                    <li class="paginate_button next" id="example1_next">
//                        <a href="#" aria-controls="example1" data-dt-idx="7" tabindex="0">Next</a>
//                    </li>
//                </ul>
//            </div>
//        </div>

//
//function pages_new($num, $curr_page, $perpage = 60, $url='')
//{
//    if ($url == '')
//    {
//        return $url;
//    }
//    $multipage = '';
//    if ($num > $perpage)
//    {
//        $multipage .= '<div class="col-sm-7 pull-right">
//            <div class="dataTables_paginate paging_simple_numbers" id="example1_paginate">
//                <ul class="pagination">';
//        $curr_page_1 = $curr_page - 1;
//
//        if ($curr_page == 1)
//        {
//            $multipage .= '<li class="paginate_button previous disabled" id="example1_previous">
//                         <a href=' . "$url&page={$curr_page_1}" . ' >Previous</a>
//                    </li>';
//        } else
//        {
//            $multipage .= '<li class="paginate_button previous" id="example1_previous">
//                        <a href=' . "$url&page={$curr_page_1}" . ' >Previous</a>
//                    </li>';
//        }
//
//        if()
//
//    }
//    return $multipage;
//}


function get_page_header()
{
    return '<header class="main-header">
        <!-- Logo -->
        <a href="/admin/default" class="logo">
            <!-- mini logo for sidebar mini 50x50 pixels --><span class="logo-mini"><b>锁屏</b></span>
            <!-- logo for regular state and mobile devices --><span class="logo-lg"><img src="/images/logo_2.png" class="img-circle" alt="User Image" /><b>红包锁屏</b>管理后台</span></a>
        <!-- Header Navbar: style can be found in header.less -->
        <nav class="navbar navbar-static-top" role="navigation">
            <!-- Sidebar toggle button-->
            <a href="#" class="sidebar-toggle" data-toggle="offcanvas" role="button"><span class="sr-only">Toggle navigation</span><span class="icon-bar"></span><span class="icon-bar"></span><span class="icon-bar"></span></a>
            <div class="navbar-custom-menu">
                <ul class="nav navbar-nav">

                    <!-- Tasks: style can be found in dropdown.less -->
                    <li class="dropdown tasks-menu"><a href="#" class="dropdown-toggle" data-toggle="dropdown"><i class="fa fa-flag-o"></i><span class="label label-danger">9</span></a>
                        <ul class="dropdown-menu">
                            <li class="header">You have 9 tasks</li>
                            <li>
                                <!-- inner menu: contains the actual data -->
                                <ul class="menu">
                                    <li>
                                        <a href="#"><h3>Design some buttons <small class="pull-right">20%</small></h3>
                                        <div class="progress xs">
                                            <div class="progress-bar progress-bar-aqua" style="width: 20%" role="progressbar" aria-valuenow="20" aria-valuemin="0" aria-valuemax="100">
                                                <span class="sr-only">20% Complete</span>
                                            </div>
                                        </div></a>
                                    </li>

                                </ul>
                            </li>
                            <li class="footer"><a href="#">View all tasks</a></li>
                        </ul>
                    </li>

                    <!-- User Account: style can be found in dropdown.less -->
                    <li class="dropdown user user-menu"><a href="#" class="dropdown-toggle" data-toggle="dropdown"><span class="hidden-xs">Alexander Pierce</span></a>
                        <ul class="dropdown-menu">
                            <!-- User image -->
                            <li class="user-header"><img src="/images/member/user8-128x128.jpg" class="img-circle" alt="User Image" /><p>系统管理员<small>系统管理员</small></p></li>

                            <!-- Menu Footer-->
                            <li class="user-footer">
                                <div class="pull-left">
                                    <a href="#" class="btn btn-default btn-flat">资料</a>
                                </div>
                                <div class="pull-right">
                                    <a href="#" class="btn btn-default btn-flat">退出</a>
                                </div>
                            </li>
                        </ul>
                    </li>
                    <!-- Control Sidebar Toggle Button -->
                    <li><a href="#" data-toggle="control-sidebar"><i class="fa fa-gears"></i></a></li>
                </ul>
            </div>
        </nav>
    </header>';
}



function pages_new($num, $curr_page, $perpage = 60, $url='')
{
    if ($url == '')
    {
        return $url;
    }
    $multipage = '';
    if ($num > $perpage)
    {
        $multipage .= '<div class="col-sm-10 pull-right">';

        $multipage .= '<div class="dataTables_paginate paging_simple_numbers" id="example1_paginate">';
        $multipage .= '<ul class="pagination">
     <li class="next" > <span>总数 <b>' . $num . '</b></span></li>
    </ul>';
        $multipage .= ' <ul class="pagination">';
        $curr_page_1 = $curr_page - 1;
        $curr_page_1_N = $curr_page + 1;

        if ($num > $perpage)
        {
            $page = 11;
            $offset = 4;
            $pages = ceil($num / $perpage);
            $from = $curr_page - $offset;
            $to = $curr_page + $offset;
            $more = 0;
            if ($page >= $pages)
            {
                $from = 2;
                $to = $pages - 1;
            } else
            {
                if ($from <= 1)
                {
                    $to = $page - 1;
                    $from = 2;
                } elseif ($to >= $pages)
                {
                    $from = $pages - ($page - 2);
                    $to = $pages - 1;
                }
                $more = 1;
            }

            if ($curr_page > 0)
            {
                if ($curr_page == 1)
                {
                    $multipage .= '<li class="paginate_button previous disabled" id="example1_previous">
                         <a href=' . "$url&page={$curr_page_1}" . ' >Previous</a></li>';

                    $multipage .= '<li class="paginate_button active">
                        <a href="#" >1</a></li>';
                } elseif ($curr_page > 6 && $more)
                {
                    $multipage .= '<li class="paginate_button previous" id="example1_previous">
                        <a href=' . "$url&page={$curr_page_1}" . ' >Previous</a></li>';
                    $multipage .= '<li class="paginate_button">
                        <a href=' . "$url&page=1" . ' >1</a></li>';
                    $multipage .= '<li class="paginate_button">
                        <a href="#" >..</a></li>';
                } else
                {
                    $multipage .= '<li class="paginate_button previous" id="example1_previous">
                        <a href=' . "$url&page={$curr_page_1}" . ' >Previous</a></li>';
                    $multipage .= '<li class="paginate_button">
                        <a href=' . "$url&page=1" . ' >1</a></li>';
                }
            }
            for ($i = $from; $i <= $to; $i++)
            {
                if ($i != $curr_page)
                {
                    $multipage .= '<li class="paginate_button">
                        <a href=' . "$url&page=$i" . ' >' . $i . '</a></li>';
                } else
                {
                    $multipage .= '<li class="paginate_button active">
                        <a href="#" >' . $i . '</a></li>';
                }
            }
            if ($curr_page < $pages)
            {
                if ($curr_page < $pages - 5 && $more)
                {
                    $multipage .= '<li class="paginate_button">
                        <a href="#" >..</a></li>';
                    $multipage .= '<li class="paginate_button">
                        <a href=' . "$url&page=$pages" . ' >' . $pages . '</a></li>';
                    $multipage .= '<li class="paginate_button next" id="example1_next">
                        <a href=' . "$url&page={$curr_page_1_N}" . '  >Next</a></li>';
                } else
                {
                    $multipage .= '<li class="paginate_button">
                        <a href=' . "$url&page=$pages" . ' >' . $pages . '</a></li>';
                    $multipage .= '<li class="paginate_button next" id="example1_next">
                        <a href=' . "$url&page={$curr_page_1_N}" . '  >Next</a></li>';
                }
            } elseif ($curr_page == $pages)
            {
                $multipage .= '<li class="paginate_button">
                        <a href=' . "$url&page=$pages" . ' >' . $pages . '</a></li>';
                $multipage .= '<li class="paginate_button next disabled" id="example1_next">
                        <a href=' . "$url&page={$curr_page_1_N}" . '  >Next</a></li>';

            }
        }
    }
    $multipage .= '    </ul>
   </div> </div>';

    return $multipage;
}


function get_date_range_picker($component_id)
{
    return ' <div class="form-group">
                <span>起止日期</span>
                <div class="input-group" style="padding-left: 3px; width: 210px">
                    <div class="input-group-addon">
                        <i class="fa fa-calendar"></i>
                    </div>
                    <input type="text" class="form-control pull-right" id="' . $component_id . '" />
                </div>
            </div>

            <script>
            $(function () {
                //Date range picker
                $("#' . $component_id . '").daterangepicker({
                    format: "YYYY-MM-DD"
                });
            });
            </script>
';
}


function get_select($id, $label, $values, $len = 130)  // $values = arrayy(1 => "val_1", 3 => "val_2", 7 => "val_3")
{
    $str = ' <div class="form-group " style="padding-left: 3px;">
                <div class="input-group">
                    <select class="form-control" style="width: ' . $len . 'px" id="' . $id . '">
                        <option value="0">' . $label . '</option>';

    foreach($values as $key => $var)
    {
        $str .= '<option value="' . $key . '">' . $var . '</option>';
    }

    $str .= '</select>
                </div>
            </div>';

    return $str;
}

function get_table()
{
    return '<table id="example1" class="table table-bordered table-striped">
                                    <thead>
                                    <tr>
                                        <th>Rendering engine</th>
                                        <th>Browser</th>
                                        <th>Platform(s)</th>
                                        <th>Engine version</th>
                                        <th>CSS grade</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <tr>
                                        <td>Trident</td>
                                        <td>Internet Explorer 4.0 </td>
                                        <td>Win 95+</td>
                                        <td>4</td>
                                        <td>X</td>
                                    </tr>
                                    <tr>
                                        <td>Trident</td>
                                        <td>Internet Explorer 5.0 </td>
                                        <td>Win 95+</td>
                                        <td>5</td>
                                        <td>C</td>
                                    </tr>
                                    <tr>
                                        <td>Trident</td>
                                        <td>Internet Explorer 5.5 </td>
                                        <td>Win 95+</td>
                                        <td>5.5</td>
                                        <td>A</td>
                                    </tr>
                                    <tr>
                                        <td>Trident</td>
                                        <td>Internet Explorer 6 </td>
                                        <td>Win 98+</td>
                                        <td>6</td>
                                        <td>A</td>
                                    </tr>
                                    <tr>
                                        <td>Trident</td>
                                        <td>Internet Explorer 7</td>
                                        <td>Win XP SP2+</td>
                                        <td>7</td>
                                        <td>A</td>
                                    </tr>
                                    <tr>
                                        <td>Trident</td>
                                        <td>AOL browser (AOL desktop)</td>
                                        <td>Win XP</td>
                                        <td>6</td>
                                        <td>A</td>
                                    </tr>
                                    <tr>
                                        <td>Gecko</td>
                                        <td>Firefox 1.0</td>
                                        <td>Win 98+ / OSX.2+</td>
                                        <td>1.7</td>
                                        <td>A</td>
                                    </tr>
                                    <tr>
                                        <td>Gecko</td>
                                        <td>Firefox 1.5</td>
                                        <td>Win 98+ / OSX.2+</td>
                                        <td>1.8</td>
                                        <td>A</td>
                                    </tr>
                                    <tr>
                                        <td>Gecko</td>
                                        <td>Firefox 2.0</td>
                                        <td>Win 98+ / OSX.2+</td>
                                        <td>1.8</td>
                                        <td>A</td>
                                    </tr>
                                    <tr>
                                        <td>Gecko</td>
                                        <td>Firefox 3.0</td>
                                        <td>Win 2k+ / OSX.3+</td>
                                        <td>1.9</td>
                                        <td>A</td>
                                    </tr>
                                    <tr>
                                        <td>Gecko</td>
                                        <td>Camino 1.0</td>
                                        <td>OSX.2+</td>
                                        <td>1.8</td>
                                        <td>A</td>
                                    </tr>
                                    <tr>
                                        <td>Gecko</td>
                                        <td>Camino 1.5</td>
                                        <td>OSX.3+</td>
                                        <td>1.8</td>
                                        <td>A</td>
                                    </tr>
                                    <tr>
                                        <td>Gecko</td>
                                        <td>Netscape 7.2</td>
                                        <td>Win 95+ / Mac OS 8.6-9.2</td>
                                        <td>1.7</td>
                                        <td>A</td>
                                    </tr>
                                    <tr>
                                        <td>Gecko</td>
                                        <td>Netscape Browser 8</td>
                                        <td>Win 98SE+</td>
                                        <td>1.7</td>
                                        <td>A</td>
                                    </tr>
                                    <tr>
                                        <td>Gecko</td>
                                        <td>Netscape Navigator 9</td>
                                        <td>Win 98+ / OSX.2+</td>
                                        <td>1.8</td>
                                        <td>A</td>
                                    </tr>
                                    <tr>
                                        <td>Gecko</td>
                                        <td>Mozilla 1.0</td>
                                        <td>Win 95+ / OSX.1+</td>
                                        <td>1</td>
                                        <td>A</td>
                                    </tr>
                                    <tr>
                                        <td>Gecko</td>
                                        <td>Mozilla 1.1</td>
                                        <td>Win 95+ / OSX.1+</td>
                                        <td>1.1</td>
                                        <td>A</td>
                                    </tr>
                                    <tr>
                                        <td>Gecko</td>
                                        <td>Mozilla 1.2</td>
                                        <td>Win 95+ / OSX.1+</td>
                                        <td>1.2</td>
                                        <td>A</td>
                                    </tr>
                                    <tr>
                                        <td>Gecko</td>
                                        <td>Mozilla 1.3</td>
                                        <td>Win 95+ / OSX.1+</td>
                                        <td>1.3</td>
                                        <td>A</td>
                                    </tr>
                                    <tr>
                                        <td>Gecko</td>
                                        <td>Mozilla 1.4</td>
                                        <td>Win 95+ / OSX.1+</td>
                                        <td>1.4</td>
                                        <td>A</td>
                                    </tr>
                                    <tr>
                                        <td>Gecko</td>
                                        <td>Mozilla 1.5</td>
                                        <td>Win 95+ / OSX.1+</td>
                                        <td>1.5</td>
                                        <td>A</td>
                                    </tr>
                                    <tr>
                                        <td>Gecko</td>
                                        <td>Mozilla 1.6</td>
                                        <td>Win 95+ / OSX.1+</td>
                                        <td>1.6</td>
                                        <td>A</td>
                                    </tr>
                                    <tr>
                                        <td>Gecko</td>
                                        <td>Mozilla 1.7</td>
                                        <td>Win 98+ / OSX.1+</td>
                                        <td>1.7</td>
                                        <td>A</td>
                                    </tr>
                                    <tr>
                                        <td>Gecko</td>
                                        <td>Mozilla 1.8</td>
                                        <td>Win 98+ / OSX.1+</td>
                                        <td>1.8</td>
                                        <td>A</td>
                                    </tr>
                                    <tr>
                                        <td>Gecko</td>
                                        <td>Seamonkey 1.1</td>
                                        <td>Win 98+ / OSX.2+</td>
                                        <td>1.8</td>
                                        <td>A</td>
                                    </tr>
                                    <tr>
                                        <td>Gecko</td>
                                        <td>Epiphany 2.20</td>
                                        <td>Gnome</td>
                                        <td>1.8</td>
                                        <td>A</td>
                                    </tr>
                                    <tr>
                                        <td>Webkit</td>
                                        <td>Safari 1.2</td>
                                        <td>OSX.3</td>
                                        <td>125.5</td>
                                        <td>A</td>
                                    </tr>
                                    <tr>
                                        <td>Webkit</td>
                                        <td>Safari 1.3</td>
                                        <td>OSX.3</td>
                                        <td>312.8</td>
                                        <td>A</td>
                                    </tr>
                                    <tr>
                                        <td>Webkit</td>
                                        <td>Safari 2.0</td>
                                        <td>OSX.4+</td>
                                        <td>419.3</td>
                                        <td>A</td>
                                    </tr>
                                    <tr>
                                        <td>Webkit</td>
                                        <td>Safari 3.0</td>
                                        <td>OSX.4+</td>
                                        <td>522.1</td>
                                        <td>A</td>
                                    </tr>
                                    <tr>
                                        <td>Webkit</td>
                                        <td>OmniWeb 5.5</td>
                                        <td>OSX.4+</td>
                                        <td>420</td>
                                        <td>A</td>
                                    </tr>
                                    <tr>
                                        <td>Webkit</td>
                                        <td>iPod Touch / iPhone</td>
                                        <td>iPod</td>
                                        <td>420.1</td>
                                        <td>A</td>
                                    </tr>
                                    <tr>
                                        <td>Webkit</td>
                                        <td>S60</td>
                                        <td>S60</td>
                                        <td>413</td>
                                        <td>A</td>
                                    </tr>
                                    <tr>
                                        <td>Presto</td>
                                        <td>Opera 7.0</td>
                                        <td>Win 95+ / OSX.1+</td>
                                        <td>-</td>
                                        <td>A</td>
                                    </tr>
                                    <tr>
                                        <td>Presto</td>
                                        <td>Opera 7.5</td>
                                        <td>Win 95+ / OSX.2+</td>
                                        <td>-</td>
                                        <td>A</td>
                                    </tr>
                                    <tr>
                                        <td>Presto</td>
                                        <td>Opera 8.0</td>
                                        <td>Win 95+ / OSX.2+</td>
                                        <td>-</td>
                                        <td>A</td>
                                    </tr>
                                    <tr>
                                        <td>Presto</td>
                                        <td>Opera 8.5</td>
                                        <td>Win 95+ / OSX.2+</td>
                                        <td>-</td>
                                        <td>A</td>
                                    </tr>
                                    <tr>
                                        <td>Presto</td>
                                        <td>Opera 9.0</td>
                                        <td>Win 95+ / OSX.3+</td>
                                        <td>-</td>
                                        <td>A</td>
                                    </tr>
                                    <tr>
                                        <td>Presto</td>
                                        <td>Opera 9.2</td>
                                        <td>Win 88+ / OSX.3+</td>
                                        <td>-</td>
                                        <td>A</td>
                                    </tr>
                                    <tr>
                                        <td>Presto</td>
                                        <td>Opera 9.5</td>
                                        <td>Win 88+ / OSX.3+</td>
                                        <td>-</td>
                                        <td>A</td>
                                    </tr>
                                    <tr>
                                        <td>Presto</td>
                                        <td>Opera for Wii</td>
                                        <td>Wii</td>
                                        <td>-</td>
                                        <td>A</td>
                                    </tr>
                                    <tr>
                                        <td>Presto</td>
                                        <td>Nokia N800</td>
                                        <td>N800</td>
                                        <td>-</td>
                                        <td>A</td>
                                    </tr>
                                    <tr>
                                        <td>Presto</td>
                                        <td>Nintendo DS browser</td>
                                        <td>Nintendo DS</td>
                                        <td>8.5</td>
                                        <td>C/A<sup>1</sup></td>
                                    </tr>
                                    <tr>
                                        <td>KHTML</td>
                                        <td>Konqureror 3.1</td>
                                        <td>KDE 3.1</td>
                                        <td>3.1</td>
                                        <td>C</td>
                                    </tr>
                                    <tr>
                                        <td>KHTML</td>
                                        <td>Konqureror 3.3</td>
                                        <td>KDE 3.3</td>
                                        <td>3.3</td>
                                        <td>A</td>
                                    </tr>
                                    <tr>
                                        <td>KHTML</td>
                                        <td>Konqureror 3.5</td>
                                        <td>KDE 3.5</td>
                                        <td>3.5</td>
                                        <td>A</td>
                                    </tr>
                                    <tr>
                                        <td>Tasman</td>
                                        <td>Internet Explorer 4.5</td>
                                        <td>Mac OS 8-9</td>
                                        <td>-</td>
                                        <td>X</td>
                                    </tr>
                                    <tr>
                                        <td>Tasman</td>
                                        <td>Internet Explorer 5.1</td>
                                        <td>Mac OS 7.6-9</td>
                                        <td>1</td>
                                        <td>C</td>
                                    </tr>
                                    <tr>
                                        <td>Tasman</td>
                                        <td>Internet Explorer 5.2</td>
                                        <td>Mac OS 8-X</td>
                                        <td>1</td>
                                        <td>C</td>
                                    </tr>
                                    <tr>
                                        <td>Misc</td>
                                        <td>NetFront 3.1</td>
                                        <td>Embedded devices</td>
                                        <td>-</td>
                                        <td>C</td>
                                    </tr>
                                    <tr>
                                        <td>Misc</td>
                                        <td>NetFront 3.4</td>
                                        <td>Embedded devices</td>
                                        <td>-</td>
                                        <td>A</td>
                                    </tr>
                                    <tr>
                                        <td>Misc</td>
                                        <td>Dillo 0.8</td>
                                        <td>Embedded devices</td>
                                        <td>-</td>
                                        <td>X</td>
                                    </tr>
                                    <tr>
                                        <td>Misc</td>
                                        <td>Links</td>
                                        <td>Text only</td>
                                        <td>-</td>
                                        <td>X</td>
                                    </tr>
                                    <tr>
                                        <td>Misc</td>
                                        <td>Lynx</td>
                                        <td>Text only</td>
                                        <td>-</td>
                                        <td>X</td>
                                    </tr>
                                    <tr>
                                        <td>Misc</td>
                                        <td>IE Mobile</td>
                                        <td>Windows Mobile 6</td>
                                        <td>-</td>
                                        <td>C</td>
                                    </tr>
                                    <tr>
                                        <td>Misc</td>
                                        <td>PSP browser</td>
                                        <td>PSP</td>
                                        <td>-</td>
                                        <td>C</td>
                                    </tr>
                                    <tr>
                                        <td>Other browsers</td>
                                        <td>All others</td>
                                        <td>-</td>
                                        <td>-</td>
                                        <td>U</td>
                                    </tr>
                                    </tbody>
                                    <tfoot>
                                    <tr>
                                        <th>Rendering engine</th>
                                        <th>Browser</th>
                                        <th>Platform(s)</th>
                                        <th>Engine version</th>
                                        <th>CSS grade</th>
                                    </tr>
                                    </tfoot>
                                </table>';

}