<?php
/**
 * 收益统计
 * 暂停使用移到command执行，Score表已转入新库
 * @category   Leb
 * @package    Leb_Action
 * @author     lihui
 * @version    $Id: income.php 2014-11-07 10:30:00 lihui
 * @copyright (c) 2014 dianjoy.com
 * @license
 */
class incomeController extends Application
{
    private $scoreModel;
    private $incomeModel;
    private $incomeDayModel;
    private $actionType;

    public function  execute($plugins)
    {
        die("暂停使用");
        ini_set('max_execution_time', '1800');
//      ini_set('memory_limit','256M');

        $dataDb = C('db.php');
        $this->scoreModel = $this->loadAppModel('Score',$dataDb);
        $this->incomeModel= $this->loadModel('Income',array(),'admin');
        $this->incomeDayModel = $this->loadModel('Income_day',array(),'admin');

        $configModel = C('global.php');
        $this->actionType = $configModel['hb_action_type'];
    }

    public function indexAction()
    {
    }

    public function cronAction()
    {
        $yesterday = date("Y-m-d",strtotime('-1 day'));
        $isIncomeDay = $this->incomeDayModel->query("SELECT 'X' FROM t_income_count_day WHERE cdate='$yesterday' LIMIT 1");
        if(!empty($isIncomeDay)){
            die($yesterday."ok");
        }

        $startTime = time();
        $incomeType = array();
        $tableZ = "z_score_log_";
        for($i=0;$i<100;$i++)
        {
            if($i<10){
                $numStr = '0'.$i;
            }else{
                $numStr = $i;
            }
            $tableStr = $tableZ.$numStr;
            $reData = $this->countType($yesterday,$tableStr);
            if(empty($reData))
               continue;

            foreach($reData as $key=>$val){
                if(!empty($incomeType[$key]['score'])){
                    $incomeType[$key]['score'] = (int)$incomeType[$key]['score'] + $val['score'];
                }else{
                    $incomeType[$key]['score'] = 0 + $val['score'];
                }
                if(!empty($incomeType[$key]['user'])){
                    $incomeType[$key]['user'] = (int)$incomeType[$key]['user'] + $val['user'];
                }else{
                    $incomeType[$key]['user'] = 0 + $val['user'];
                }
                $incomeType[$key]['name']  = !empty($val['name'])?$val['name']:'';
            }
            usleep(50);
        }

        //导入收益数据
        if(!empty($incomeType)){
            $incomeDay = array();
            foreach($incomeType as $ckey=>$cval){
                $isIncome = $this->incomeModel->query("SELECT 'X' FROM t_income_count WHERE cdate='$yesterday' AND action_type='$ckey' LIMIT 1");
                if(!empty($isIncome))
                    continue;

                $incomeAdd['cdate'] = $yesterday;
                $incomeAdd['action_type'] = $ckey;
                $incomeAdd['action_type_name'] = $cval['name'];
                $incomeAdd['score'] = $cval['score'];
                $incomeAdd['user']  = $cval['user'];
                $re = $this->addIncome($incomeAdd);
                if($re)
                    $incomeDay[$ckey] = $cval;
            }

            //按天统计
            if($incomeDay){
                if(isset($this->actionType[0]) && isset($incomeDay[0])){
                    $incomeDayAdd['score_ad'] = $incomeDay[0]['score'];
                }else{
                    $incomeDayAdd['score_ad'] = 0;
                }

                if(isset($this->actionType[1]) && isset($incomeDay[1])){
                    $incomeDayAdd['score_right_catch'] = $incomeDay[1]['score'];
                }else{
                    $incomeDayAdd['score_right_catch'] = 0;
                }

                if(isset($this->actionType[2]) && isset($incomeDay[2])){
                    $incomeDayAdd['score_register'] = $incomeDay[2]['score'];
                }else{
                    $incomeDayAdd['score_register'] = 0;
                }

                if(isset($this->actionType[3]) && isset($incomeDay[3])){
                    $incomeDayAdd['score_other'] = $incomeDay[3]['score'];
                }else{
                    $incomeDayAdd['score_other'] = 0;
                }

                if(isset($this->actionType[4]) && isset($incomeDay[4])){
                    $incomeDayAdd['score_task'] = $incomeDay[4]['score'];
                }else{
                    $incomeDayAdd['score_task'] = 0;
                }

                if(isset($this->actionType[5]) && isset($incomeDay[5])){
                    $incomeDayAdd['score_share'] = $incomeDay[5]['score'];
                }else{
                    $incomeDayAdd['score_share'] = 0;
                }
                $incomeDayAdd['cdate'] = $yesterday;
                $this->incomeDayModel->addIncomeDay($incomeDayAdd);
            }
        }
        $endTime = time() - $startTime;
        echo $yesterday."ok,用时:".$endTime."秒";
    }

    private function countType($yesterday,$tableStr)
    {
        if(empty($yesterday) || empty($tableStr) || empty($this->actionType)){
            return false;
        }

        $reDate = array();
        $startTime = $yesterday." 00:00:00";
        $endTime = $yesterday." 23:59:59";
        $whereStr = " ctime >='$startTime' AND ctime <='$endTime'";
        $scoreRe = $this->scoreModel->query("SELECT action_type,SUM(score) as s_num,COUNT(DISTINCT uid) as u_num
                                             FROM $tableStr
                                             WHERE $whereStr GROUP BY action_type");
        if($scoreRe){
            foreach($scoreRe as $key=>$val){
                $reDate[$val['action_type']]['score']= $val['s_num'];
                $reDate[$val['action_type']]['user'] = $val['u_num'];
                if(!empty($this->actionType[$val['action_type']])){
                    $reDate[$val['action_type']]['name'] = $this->actionType[$val['action_type']];
                }else{
                    $reDate[$val['action_type']]['name'] = '';
                }
            }
        }
        return $reDate;
    }

    private function addIncome($incomeAdd)
    {
        if(!isset($incomeAdd['cdate']) || !isset($incomeAdd['action_type']))
            return false;

        if(!isset($incomeAdd['score']) || !isset($incomeAdd['user']))
            return false;

        $re = $this->incomeModel->addIncome($incomeAdd);
        return $re;
    }

}