<?php
/**
 * 积分统计
 *
 * @category   Leb
 * @package    Leb_Action
 * @author     lihui
 * @version    $Id: score.php 2014-09-17 10:30:00 lihui
 * @copyright (c) 2014 dianjoy.com
 * @license
 */
class scoreController extends Application
{
    private $scoreModel;

    public function  execute($plugins)
    {
        ini_set('max_execution_time', '600');

        $dataDb = C('db.php');
        $this->scoreModel = $this->loadAppModel('Score',$dataDb);

        //189f1916f1900210747c589209014160
        $this->password  = md5("dianjoyceo");
    }

    public function indexAction()
    {
        $password  = $this->reqVar('passwd','');
        $startDate = daddslashes($this->reqVar('start_date',''));

        $whereStr = '';
        if( $password == $this->password && !empty($startDate)){
            $startDateWhere = date("Y-m-d 00:00:00",strtotime($startDate));
            $endDateWhere = date("Y-m-d 23:59:59",strtotime($startDate));
            $whereStr = " ctime>='$startDateWhere' AND ctime<='$endDateWhere'";

            $runTime = 0;
            $startTime = time();

            $adType = $actionType = $cNum = $uNum = $ssNum =array();

            for($i=0;$i<100;$i++){
                if(strlen($i)==1){
                    $tableStr = '0'.$i;
                }else{
                    $tableStr = $i;
                }

                $scoreRe = $this->scoreModel->query("SELECT COUNT(*) as c_num, COUNT(DISTINCT uid) as u_num, SUM(score) as ss_num, ad_type, action_type
                                                     FROM z_score_log_$tableStr  WHERE $whereStr GROUP BY action_type,ad_type");
                if($scoreRe){
                    foreach($scoreRe as $key=>$val){
                        if(!isset( $cNum[$val['ad_type']])){
                            $cNum[$val['ad_type']] = 0;
                        }
                        if(!isset( $uNum[$val['ad_type']])){
                            $uNum[$val['ad_type']] = 0;
                        }
                        if(!isset( $ssNum[$val['ad_type']])){
                            $ssNum[$val['ad_type']] = 0;
                        }
                        $cNum[$val['ad_type']] = $val['c_num'] + $cNum[$val['ad_type']];
                        $uNum[$val['ad_type']] = $val['u_num'] + $uNum[$val['ad_type']];
                        $ssNum[$val['ad_type']] = $val['ss_num'] + $ssNum[$val['ad_type']];

                        //echo  $tableStr."--".$val['ad_type'].'--'.$cNum[$val['action_type']].'--'.$uNum[$val['ad_type']].'--'.$ssNum[$val['ad_type']]."<br>";

                        $adType[$val['ad_type']] = $val['action_type'];
                    }
                }
            }
            if($adType){
                foreach($adType as $akey=>$aval){
                    echo "广告类型:".$akey."  动作:".$aval."  总计:".$cNum[$akey]."  用户数:".$uNum[$akey]."  总金额:".$ssNum[$akey]."<br>";
                }
            }
            $runTime = time() - $startTime;
            echo "运行时间为(秒)".$runTime;

        }else{
            echo "参数错误！";
        }
    }

    public function tjAction()
    {
        $password  = $this->reqVar('passwd','');
        $startDate = daddslashes($this->reqVar('start_date',''));

        $whereStr = '';
        if( $password == $this->password && !empty($startDate)){
            $startDateWhere = date("Y-m-d 00:00:00",strtotime($startDate));
            $endDateWhere = date("Y-m-d 23:59:59",strtotime($startDate));
            $whereStr = " ctime>='$startDateWhere' AND ctime<='$endDateWhere' AND ad_id='share_random'";

            $startTime = time();
            $cNum = $uNum = $ssNum = 0;
            for($i=0;$i<100;$i++){
                if(strlen($i)==1){
                    $tableStr = '0'.$i;
                }else{
                    $tableStr = $i;
                }
                $scoreRe = $this->scoreModel->query("SELECT COUNT(*) as c_num, COUNT(DISTINCT uid) as u_num, SUM(score) as ss_num
                                                     FROM z_score_log_$tableStr
                                                     WHERE $whereStr LIMIT 1");
                if($scoreRe){
                    foreach($scoreRe as $key=>$val){
                        $cNum  = $val['c_num'] + $cNum;
                        $uNum  = $val['u_num'] + $uNum;
                        $ssNum = $val['ss_num'] + $ssNum;
                    }
                }
            }
            echo '中奖数:'.$cNum.'--中奖用户数:'.$uNum.'--中奖金额:'.$ssNum/100;

            $runTime = time() - $startTime;
            echo "<br/>运行时间为(秒)".$runTime;

        }else{
            echo "参数错误！";
        }
    }

}