<?php
/**
 *
 * 模板解析
 * @author mingyu@leju.sina.com.cn
 * @version $Id: parse.php 1 2011-04-08 07:42:35Z xiaoping1 $
 *
 */
class Plugin_Parse
{
	private	$_design_regexp  = '/\${.+}/Ui';
    private $_tNameKey = 'TABLES';   //文档表信息变量KEY
	private $_dModel = null; //文档MODEL

    //预置的SQL字段值
    private $_sqlFieldConvert = array(
                                        'd_id' => 'd_id',
                                        't_id' => 't_id',
                                        'p_id' => 'p_id',
                                        'createtime' => 'create_time',
                                        'mendtime'   => 'last_edit_time',
                                        'url'     => 'inner_url_1',
                                        '文档URL' => 'inner_url_1',
                                        'creator' => 'creator',
                                        'mender'  => 'last_editor',
                                    );

	public function __construct()
	{

	}

	public function getDModel()
	{
	    if (is_null($this->_dModel)) {
	    	$this->_dModel = Leb_Helper::loadModel('document', array(), 'document');
	    }
	    return $this->_dModel;
	}
	/**
	 *
	 * 得到模板设计中的模板域字段
	 * @param string $html          模板设计和算法CODE中的内容
	 * @return array $match[0]
	 */
	public function getFields($html)
	{
		$num = preg_match_all($this->_design_regexp,$html,$match);
		return $match[0];
	}

	/**
	 *
	 * 得到模板域中文名
	 * @param  string $field
	 */
	public function getFieldCnName($field)
	{
		$pattern = '/\${(\S+)}/Ui';
		return  preg_replace($pattern, '${1}', $field);
	}

	/**
	 *
	 * 得到Sql中所有的模板域
	 * @param string $sql
	 */
	public function getSqlFields($sql)
	{
		$pattern = '/\{([^\r\n;:]+)\}/Usi';
		$num = preg_match_all($pattern,$sql,$match);
        return array_unique($match[1]);
	}

	/**
	 *
	 * 得到SQL语句中的中文表名
	 * @param string $sql
	 */
	public function getSqlTableNameCn($sql)
	{
	    $tableNames = array();
		$pattern = '/from\s?(.*)\s*(where|;)/Ui';
        $num = preg_match_all($pattern,$sql,$matchs);
        if($num == 0)
        {
           return array();
        } else {
            foreach ($matchs[1] as $match)
            {
                $num = preg_match_all('/\{(.+)\}/Usi',$match,$tableName);
                if ($num > 0) {
                	$tableNames = array_merge($tableNames,$tableName[1]);
                }
            }
        }
        return array_unique($tableNames);
	}

    /**
     *
     * @param string $sql       模板域算法中SQL
     * @param string $cnName    中文表名
     * @param string $enName    英文表名（真实表名）
     * @return string           替换后SQL
     */
	public function sqlTableNameReplace($sql, $cnName, $enName)
	{
        return preg_replace('/from\s+\{' . $cnName . '\}\s*/Ui',
                          'from '. $enName .' ', $sql);
	}

    
	/**
	 *
	 * 得到SQL语句中的英文表名
	 * @param string $sql
	 */
	public function getSqlTableNameEn($sql)
	{
		$pattern = '/from\s+?(.+?)\s/i';
        $num = preg_match($pattern,$sql,$match);
        if($num == 0)
        {
           return null;
        }
        return $match[1];
	}

	/**
	 *
	 * 把SQL语句中的模板域替换
	 * @param string $sql
	 * @param array $fields
	 * @param array $fieldsInfo
	 * @return string
	 */
	public function sqlFieldCnNameReplace($sql, array $fields, array $fieldsInfo)
	{
        foreach ($fields as $cName)
        {
        	if (isset($fieldsInfo[$cName])) {
        		$sql = str_replace('{' . $cName .'}', $fieldsInfo[$cName], $sql);
        	}
        }
        return $sql;

	}

    /**
     * 获取广告代码
     *
     * @param string $str  模板域算法
     * @return array       广告代码
     */
    public function getAdCode($str)
    {
        $pattern = '/\<\!--ADS\:(\w+)--\>/i';
        preg_match_all($pattern, $str, $matches);
        return $matches[1];
    }


    /**
     * 替换广告代码为广告内容
     *
     * @param string $str       模板域算法
     * @param string $content   广告代码与对应的广告内容
     * @return string           替换后结果
     */
    public function adCodeReplace($str, $content)
    {
        foreach ($content as $k => $v)
        {
            $pattern = '/\<\!--ADS\:' . $k . '--\>/Ui';
            $str = preg_replace($pattern, $v, $str);
        }
        return $str;
    }

    /**
     * 获得全局变量
     * @param string    $str    模板设计解析后内容
     * @param type      $type   全局变量类型：$:项目组 @频道级
     * @return <type>
     */
    public function getEmbeds($str, $type='$')
    {
        $pattern = '/\\' . $type . 'G{(.+)}/Ui';
        preg_match_all($pattern, $str, $matches);
        return array_unique($matches[1]);
    }

    /**
     * 替换全局变量
     * @param   string    $str    模板设计解析后内容
     * @param   string    $data   全局变量键值对数组
     * @param   type      $type   全局变量类型：$:项目组 @频道级
     * @return  string
     */
    public function embedReplace($str, $data, $type='$')
    {
        foreach ($data as $k => $v)
        {
            $pattern = '/\\' . $type . 'G{' . $k . '}/Ui';
            $str = preg_replace($pattern, $v, $str);
        }
        return $str;
    }

    /**
     * 获取分页相关信息
     * @param   $string    模板解析后内容
     * @return  array      分页信息
     */
    public function getPageFlag($str)
    {
        //<!-- [page title=testtitle subtitle=testsubtitle] -->
        $pattern = '/\<\!--\s*\[page\s+title=(.+)\s+subtitle=(.+)\]\s*--\>/Ui';
        preg_match_all($pattern, $str, $matches);
        if (($pCount = count($matches[0])) > 0) {
            $result = array();
            foreach ($matches[0] as $k => $v) {
                $result[$v] = array('title'    => $matches[1][$k],
                                    'subtitle' => $matches[2][$k],
                                 );
            }
            return $result;
        } else {
            return array();
        }
        
    }


	/**
	 *
	 * 模板域值替换
	 * @param  $html 模板设计
	 * @param  $pattern 模板域
	 * @param  $replacement 替换值
	 */
	public function replace( $html , $fields , $fieldData)
	{
        $fields = (array) $fields;
	    foreach ($fields as $k => $v)
        {
            $cnName = $this->getFieldCnName($v);
            if(array_key_exists($cnName, $fieldData))
            {
                $html = str_replace($v, $fieldData[$cnName], $html);
            }else
            {
                //$html = $parse->replace($html, $v, '模板域'.$v.'不存在');
                //$html = str_replace($v, '', $html);
            }
        }

        return $html;
	}


    /**
     * 得到模板解析结果
     * @param string   $code
     * @param boolean  $debug     是否显示调试输出信息
     * @return array|string       如果返回结果是数组，代码有错误；
     */
	public function fetch($code, $debug = true)
	{
		$pre = '$GVARS = $GLOBALS[\'GVARS\'];
		        $db = $GLOBALS[\'db\'];
		        $DOCUMENT = &$GLOBALS[\'DOCUMENT\'];';
        
        ob_start();
        $return = eval($pre . $code); //return 返回值
        $output= trim(ob_get_clean());      //代码的所有输入，含错误信息

        if (!empty ($output) && $debug) {
            echo "<div class='fr w150 ' style='margin-right:50px;'><textarea>$output</textarea></div>";
        }
        if (false === $return) {  //代错中有语法错误
            return array('return' => $return, 'output' => $output, 'code' => $code);
        } else {
            return $return;
        }
        
	}
 

    /**
     * 解析算法中的块
     *
     * @param string $str           算法内容
     * @param string $section       块的名称，如为all，返回所有块
     * @return mixed array|string   结果
     */
    public function getSection($str, $section='all')
    {
        $result = array();
        $pattern = "/\s*\[(\w+)\]\r*\n/Usi";

        $sections = preg_split($pattern,$str);
        $num = preg_match_all($pattern, $str, $sectionNames);
        if ($sectionNames > 0) {
            foreach ($sectionNames[1] as $k => $v) {
                $result[strtolower($v)] = trim($sections[$k+1]);
            }
        }

        if ('all' == $section) {
            return $result;
        } else {
            return isset($result[$section])?$result[$section]:'';
        }

    }

    /**
     * 算法中CODE的处理,替换CODE中的中文名表名和字段名
     *
     * @param string $pCnName
     * @param string $code
     * @return string
     */
    public function execCodeSection($pCnName, $code)
    {
        //处理代码中表名
        $tableCnNames = $this->getSqlTableNameCn($code);

        $dModel = $this->getDModel();
        
        //代码中表名处理
        if (!empty($tableCnNames)) {

            //目前不支持联表，$tableCnNames只有一个表
            foreach ($tableCnNames as $tName) {
                if (false === ($re = get_gvar($pCnName . $tName,
                                              $this->getTnameKey()))) {
                    
                    $re = $dModel->getFnameByCnName($pCnName,$tName);
                    add_gvar($pCnName . $tName, $re, $this->getTnameKey());
                }
                if (empty($re['t_name'])) {
                    return $code;
                }
                $code = $this->sqlTableNameReplace($code,
                                                   $tName,
                                                   $re['t_name']);
                 
            }

            //处理sql中的字段名,不分主、扩展表字段
            $fields = $this->getSqlFields($code);
            if (count($fields) > 0) {
                $fieldData = array();
                $fieldsInfo = get_gvar($pCnName . $tName, $this->getTnameKey());
                $fieldsInfo = $fieldsInfo['f_en_name'];
                
                foreach ($fields as $field) {
                    if (isset($fieldsInfo[$field])) {
                        $code = str_replace('{' . $field .'}', $fieldsInfo[$field]['f_name'], $code);
                    } elseif (isset ($this->_sqlFieldConvert[$field])) {
                        $code = str_replace('{' . $field .'}', $this->_sqlFieldConvert[$field], $code);
                    } elseif (($tmp = trim($field)) && '$' == $tmp[0]) {
                        // 过滤掉PHP的字符串中的标准写法，
                        // 如"<a href='{$url}'>{$title}</a>"
                        continue;
                    } else {
                        return 'SQL中表字段 "{' . $field . '}" 不存在,或已停用(如果大括号中的字段名前后有空格，请删除空格再试)';
                    }

                }
            }
            
            return array('code' => $code, 'tCnName' => $tName);
        } 
        
        return array('code' => $code, 'tCnName' => '');
    }

    /**
     * 获取文档表信息变量KEY
     * @return string
     */
    public function getTnameKey()
    {
        return $this->_tNameKey;
    }

    /**
     * 算法中project的处理
     *
     * @param string $project  project段中的内容
     * @return string          项目名
     */
    public function execProjectSection($project)
    {
        $pattern = '/\s*name=\s*\{(.+)\}/Usi';
        preg_match($pattern, $project, $match);
        return isset($match[1])?trim($match[1]):'';
    }

    /**
     * 算法中样式处理
     *
     * @param string $polym
     * @return string
     */
    public function execPolymSection($polym)
    {
        $tArray = explode('name=', $project);
        return isset($tArray['1'])?trim($tArray[1]):'';
    }

    /**
     * 替换SQL中的中文名，执行SQL，返回执行结果；返回结果为：
     * 正常：array('isError'=> false, 'data' =>'执行结果，二维数组');
     * 出错：array('isError'  => true,
     *             'errorMsg' => '出错信息',
     *             'data'     => '解析后的执行SQL');
     *
     * @param string $pCnName   项目中文名
     * @param string $sql       SQL
     * @return array            当出错时isError为true，data中执行结果；
     *                           否则isError为false，data中为执行SQL，
     *                           data中为错误提示信息。
     * 
     */
    public function execSQL($pCnName, $sql)
    {
        $result = $this->execCodeSection($pCnName, $sql);
        if (is_string($result)) {
            return array('isError'=> true,
                         'errorMsg' => $result,
                         'data' => $sql);
        }
        
        $sql = $result['code'];
        $tName = $result['tCnName'];
        $dModel = $this->getDModel();
        
        $model = new Plugin_Model($this->getDModel(), 
                                  get_gvar($pCnName . $tName,
                                  $this->getTnameKey()));
        return $model->query($sql, true);
        
    }


    /**
	 * 算法中参数解析
     *
	 * @param string $arithmetic       算法
	 * @param string $group            组名，算法中中括号内的值
	 * @param string $key              键名，算法中 key=value 的 key
	 * @return mixed 数组或值   
	 */
    public function  getSectionVar($arithmetic, $group = '', $key = '')
	{
        $arithmetic = str_replace("\r\n", "\n", $arithmetic);
        $arithmetic = explode("\n", $arithmetic);
        $data = array();
        $k1 = '';
        foreach ($arithmetic as $r) {
            $r = trim($r);
            if(!$r || $r[0] == ';' || $r[0] == '#') {
                continue;
            }
            
            if($r[0] == '[') {
                $k1 = trim(str_replace(array('[', ']'), '', $r));
            } else {
                $pos = strpos($r, '=');
                $k = trim(substr($r, 0, $pos));
                $v = trim(substr($r, $pos + 1));
                    
                $tempV = strtolower($v);
 
                if ($tempV == 'false' || $tempV == 'no') {
                    $v = false;
                }
                if ($tempV == 'true' || $tempV == 'yes') {
                    $v = true;
                }

                if('' != $k1) {
                    $data[$k1][$k] = $v;
                }else{
                    $data[$k] = $v;
                }
            }
        }

        if ('' != $key) {
            if ('' != $group) {
                return isset ($data[$group][$key])? $data[$group][$key]:null;
            }
            return isset($data[$key])? $data[$key]: $data[$key];
        } else {
            if ('' != $group) {
                return isset ($data[$group])? $data[$group]: $data[$group];
            }
            return $data;
        }
	}


    /**
     *
     * @param string    $url       请求URL
     * @param string    $method    请求方法 post|get
     * @param array     $data      请求参数
     * @param int       $time      请求过期时间
     * @return string              结果；结果为false，请求出错；
     */
    public function http_process($url, $method, $data, $time=10)
    {
        $function = 'curl_' . $method;
        $result = $function($url, $data, array(), $time);
        return $result['result'];
    }
}
