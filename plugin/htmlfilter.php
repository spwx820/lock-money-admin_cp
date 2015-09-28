<?php
/**
 * HTML和JAVASCRIPT标签过滤类
 * @author liuxp
 * @version $Id: htmlfilter.php 1 2011-04-08 07:42:35Z xiaoping1 $
 * @example 只保留a 和 input 标签，其它标签都过滤
 * $str = '<a style="dispaly:none">this is a tag</a>
 *         <pre>this is pre tag</pre>
 *         <input name="input1" value=""/>
 *         <div>this is div tag</div>
 *         <a style="dispaly:none">this is second a tag</a>
 *         <input name="input2" value=""/>';
 * $filter = Plugin_filter::getInstance(array('a','input'));
 * $result = $filter->exec($str);
 *
 */
class Plugin_HtmlFilter
{
    //不过滤标签，不带尖括号，比如不过滤<a>标签，只写array('a'),将会过滤<a>、</a>、</a>
    protected $_noFilts = array();
    protected $_leftFlag = '%%left_filte%%';
    protected $_rightFlag = '%%right_filte%%';
    static protected $_instance = null;//单例

    /**
     * 获取实例化对象
     *
     * @return Leb_Filter
     */
    static public function getInstance()
    {
        $args = func_get_args();
        if (!isset($args[0])) {
            $args[0] = array();
        }
        if (!isset(self::$_instance)) {
			self::$_instance = new self($args[0]);
		} else {
		    self::$_instance->setNoFiltes($args[0]);
		}
		return self::$_instance;
    }

    /**
     * 初始化操作
     *
     * @param array $noFilets   不过滤标签数组
     */
    private function __construct($noFilets = array())
    {
        if (!empty($noFilets)) {
            $this->setNoFiltes($noFilets);
        }
    }

    /**
     * 执行过滤操作
     *
     * @param string $str    要过滤字符串
     * @return string        过滤后字符串
     */
    public function exec($str)
    {
        if (!empty($this->_noFilts)) {
            $str = preg_replace(array_keys($this->_noFilts),
                                 array_values($this->_noFilts),
                                 $str);
        }
        $str = preg_replace('/<[\/\!]*?[^<>]*?\/*>/si', ' ', $str);
        return str_replace(array($this->_leftFlag, $this->_rightFlag),
                           array('<', '>'),
                           $str);
    }

    /**
     * 设置不过滤标签
     *
     * @param array $noFilets 不过滤标签数组
     */
    public function setNoFiltes($noFilets)
    {
        $noFilets = (array) $noFilets;
        $noFilets = array_flip($noFilets);
        $this->_noFilts = array();
        foreach ($noFilets as $key => $value){
            $index = '/<\s*(' . $key . '(\s+.*|\s*))>/i';
            $this->_noFilts[$index] = $this->_leftFlag . "\\1" . $this->_rightFlag;
            $index = '/<\s*\/\s*(' . $key . '\s*)>/i';
            $this->_noFilts[$index] = $this->_leftFlag . "/\\1" . $this->_rightFlag;
            $index = '/<\s*(' . $key . '\s+.*\s*)\/>/i';
            $this->_noFilts[$index] = $this->_leftFlag . "\\1/" . $this->_rightFlag;
        }
    }
}