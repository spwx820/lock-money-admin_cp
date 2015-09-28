<?php
/**
 *
 * 取数据
 * @author mingyu@leju.sina.com.cn
 *
 */
class Plugin_Model
{
    private $_model;
	private $_parse;
    private $dataInfo = array(); //数据相关信息
    
    public function __construct($model,$dataInfo)
	{
	    $this->_model = $model;
        $this->dataInfo = $dataInfo;
	}


    /**
     *
     * @param string $sql    SQL串
     * @return array
     */
    public function query($sql, $errorMsg = false)
    {
        $sqlInfo = $this->separateSql($sql);
        $isForce = $sqlInfo['isForce'];
        unset($sqlInfo['isForce']);
        
        //SQLRESULT模板域中可以直接写的字段名
        $sqlInfo['t_id'] = $this->dataInfo['t_id'];
        $sqlInfo['p_id'] = $this->dataInfo['p_id'];

        $result = $this->_model->getDataBySql($sqlInfo, $isForce);
        if ($errorMsg == true) {
            if (false === $result) {
                return array('isError'=> true,
                             'errorMsg' => $this->_model->getError(),
                             'data' => $sql);
            }
            return array('isError'=> false, 'data' =>$result);
        } else {
            return $result;
        }
    }

    /**
     * 分解SQL
     * @param string $sql   原生SQL
     * @return array        分解后SQL数组
     */
    private function separateSql($sql)
    {
        $result = array('fields' => '',
                        'where' => '',
                        'order' => '',
                        'limit' => '10',
                        'isForce' => true
                       );
        $sql = trim($sql);
        $endChar = substr($sql, strlen($sql) - 1);
        if ($endChar == ';') {
            $result['isEnd'] = false;
            $sql = substr($sql, 0, strlen($sql) - 1);
        }

        $result['fields'] = $this->getFields($sql);
        $result['where'] = $this->getWhere($sql);
        //$group = $this->getGroup($sql);
        $result['order'] = $this->getOrder($sql);
        $result['limit'] = $this->getLimit($sql);

        return $result;
    }

    private function getFields($sql)
    {
        $pattern = '/select\s+(.+)\s+from/Ui';
        preg_match($pattern, $sql, $match);
        return isset ($match[1])? $match[1]: '';
    }


    private function getWhere($sql)
    {
        $pattern = '/where\s+(.+)((\s+(order|group|limit|;))|\s*$)/Ui';
        preg_match($pattern, $sql, $match);
        return isset ($match[1])? $match[1]: '';
    }

    private function getGroup($sql)
    {
        $pattern = '/group\s+by\s+(.+)((\s+(order|limit))|\s*$)/Ui';
        preg_match($pattern, $sql, $match);
        return isset ($match[1])? $match[1]: '';
    }

    private function getOrder($sql)
    {
        $pattern = '/order\s+by\s+(.+)((\s+(limit))|\s*$)/Ui';
        preg_match($pattern, $sql, $match);
        return isset ($match[1])? $match[1]: '';
    }

    private function getLimit($sql)
    {
        $pattern = '/limit\s+(.+)\s*$/Ui';
        preg_match($pattern, $sql, $match);
        return isset ($match[1])? $match[1]: '10';
    }

    
    /**
     * 调用模型类的相应方法
     * @param string  $methodName
     * @param array   $arguments
     * @return mixed
     */
    /*
    public function __call($methodName, $arguments)
    {
        return call_user_func_array( array($this->_model, $methodName), $arguments);
    }*/
}
