<?php
/**
 * 错误码统计运行程序(每10分钟运行一次)
 *
 * @category   Leb
 * @package    Leb_Action
 * @author     lihui
 * @version    $Id: errorcode.php 2014-10-16 10:30:00 lihui
 * @copyright (c) 2014 dianjoy.com
 * @license
 */
class errorcodeController extends Application
{
    private $errorcodeLogModel;
    private $errorcodeCountModel;
    private $errorcodeTypeModel;
    private $configSet;

    public function execute($plugins)
    {
        $redisConfig = array('host'=> '192.168.99.118','port'=> 6380,'weight'=> '100','lasting' => 1,'connectTime' => 1);
        $this->configSet['servers']['local'][] = $redisConfig;

        $this->errorcodeLogModel = $this->loadAppModel('Errorcode_log');
        $this->errorcodeCountModel = $this->loadAppModel('Errorcode_count');
        $this->errorcodeTypeModel  = $this->loadAppModel('Errorcode_type');
    }

    public function indexAction()
    {
    }

    //每天0点运行一次
    public function cronTypeAction()
    {
        $run_1 = time();
        //通过redis缓存获取错误码记录
        $redis = Leb_Dao_Redis::getInstance($this->configSet);
        $errorCodeKey = '_zhuanerrcode'.date("Y-m-d",time());
        $errorCodeKeysInfo = $redis->keys($errorCodeKey.'*');
        if($errorCodeKeysInfo){
            foreach($errorCodeKeysInfo as $key=>$val){
                $insertCode = microtime ();
                $codeStr = trim($val,"_");
                if(empty($codeStr))
                    continue;

                $codeArr = explode("_",$codeStr);
                $errorCode = $codeArr[1];
                $errorCodeType = $codeArr[2];
                if(empty($errorCode) || empty($errorCodeType))
                    continue;

                //记录错误码
                $codeSet['errorcode'] = $errorCode;
                $isCode = $this->errorcodeCountModel->getErrorcode($codeSet);
                if(!$isCode){
                    $addErrorCode['errorcode'] = $errorCode;
                    $addErrorCode['status'] = 1;
                    $addErrorCode['ctime'] = date("Y-m-d H:i:s",time());
                    $this->errorcodeCountModel->addErrorcode($addErrorCode);
                }

                //记录错误码类别
                $codeTypeSet['errorcode'] = $errorCode;
                $codeTypeSet['type_name'] = $errorCodeType;
                $isCodeType = $this->errorcodeTypeModel->getErrorCodeType($codeTypeSet);
                if(!$isCodeType){
                    $addErrorCodeType['errorcode'] = $errorCode;
                    $addErrorCodeType['type_name'] = $errorCodeType;
                    $addErrorCodeType['status'] = 1;
                    $addErrorCodeType['ctime'] = date("Y-m-d H:i:s",time());
                    $this->errorcodeTypeModel->addErrorcodeType($addErrorCodeType);
                }
            }
        }
        $run_2 = time();
        $runtime = $run_2 - $run_1;
        echo "runtime_".$runtime."<br/>";
        echo $errorCodeKey.'succeed\t\n';
    }

    //每10分钟运行一次
    public function cronAction()
    {
        $run_1 = time();
        //通过redis缓存获取错误码记录
        $redis = Leb_Dao_Redis::getInstance($this->configSet);
        $errorCodeKey = '_zhuanerrcode'.date("Y-m-d",time());

        //获取错误码统计记录
        $howHour = date("Y-m-d H:00:00",time());
        $lastHourStar = date("Y-m-d H:00:00",strtotime($howHour)-3600);
        $lastHourEnd  = date("Y-m-d H:59:59",strtotime($howHour)-3600);

        $endTime = date("Y-m-d 23:59:59",strtotime('-1 day'));
        $sql = "SELECT errorcode,yesterday_num,today_num FROM t_errorcode_count ORDER BY errorcode ASC";
        $errorCodeRe = $this->errorcodeCountModel->query($sql);
        if($errorCodeRe){
            foreach($errorCodeRe as $e_key=>$e_val){
                if(empty($e_val['errorcode']))
                    continue;

                //每天第一次记录为昨天最后一次统计及统计昨天、前天总数
                $whereStr = "errorcode='{$e_val['errorcode']}' AND ctime='$endTime' AND is_end=1";
                $isEnd = $this->errorcodeLogModel->query("SELECT 'X' FROM t_errorcode_log WHERE $whereStr LIMIT 1");
                if(empty($isEnd)){
                    $this-> endCount($e_val['errorcode'],$endTime,$e_val['yesterday_num']);
                }

                //设置初始值
                $codeTypeList = $hourSumRe = $hourNumRe = array();
                $hourAvg = ${'total'.$e_val['errorcode']} = ${'total_type'.$e_val['errorcode']} = $errorCodeNum = 0;

                //上小时统计数量
                $hourWhereStr = "errorcode='{$e_val['errorcode']}' AND ctime>='$lastHourStar' AND ctime<='$lastHourEnd'";
                $hourSumRe = $this->errorcodeLogModel->query("SELECT SUM(num) as hour_sum FROM t_errorcode_log WHERE $hourWhereStr LIMIT 1");
                $hourNumRe = $this->errorcodeLogModel->query("SELECT COUNT(*) as hour_num FROM t_errorcode_log WHERE $hourWhereStr LIMIT 1");
                $hourAvg =  ceil($hourSumRe[0]['hour_sum']/$hourNumRe[0]['hour_num']);

                $codeTypeList = $this->errorcodeTypeModel->getErrorCodeTypeList(array('errorcode'=>$e_val['errorcode']));
                if($codeTypeList){
                    foreach($codeTypeList as $ec_key=>$ec_val){
                        if(empty($ec_val['errorcode']) || empty($ec_val['type_name']))
                            continue;

                        $isType = $redis->exists($errorCodeKey.'_'.$ec_val['errorcode'].'_'.$ec_val['type_name'].'_');
                        if(empty($isType))
                            continue;

                        $errorCodeNum = $redis->get($errorCodeKey.'_'.$ec_val['errorcode'].'_'.$ec_val['type_name'].'_');

                        $addErrorCodeLog['errorcode'] = $ec_val['errorcode'];
                        $addErrorCodeLog['type_name'] = $ec_val['type_name'];
                        $addErrorCodeLog['num'] = (int)$errorCodeNum;
                        $addErrorCodeLog['ctime'] = date("Y-m-d H:i:s",time());
                        $this->errorcodeLogModel->addErrorCodeLog($addErrorCodeLog);

                        ${'total'.$e_val['errorcode']} = ${'total'.$e_val['errorcode']} + $addErrorCodeLog['num'];
                        ${'total_type'.$e_val['errorcode']} = ${'total_type'.$e_val['errorcode']} + 1;
                    }

                    //实时统计
                    $errorCodeSave['today_num'] = ${'total'.$e_val['errorcode']};
                    $errorCodeSave['type_num']  = ${'total_type'.$e_val['errorcode']};
                    $errorCodeSave['last_num']  = $e_val['today_num'];
                    $errorCodeSave['last_hour_avg_num'] = $hourAvg;
                    $errorCodeSave['updatetime'] = date("Y-m-d H:i:s",time());
                    $this->errorcodeCountModel->saveErrorcode($e_val['errorcode'],$errorCodeSave);
                }
            }
        }
        $run_2 = time();
        $runtime = $run_2 - $run_1;
        echo "runtime_".$runtime."<br/>";
        echo $errorCodeKey.'succeed\t\n';
    }

    private  function endCount($errorCode,$endTime,$yesterdayNum)
    {
        if(empty($errorCode) || empty($endTime)) return '';

        //通过redis缓存获取错误码记录
        $redis = Leb_Dao_Redis::getInstance($this->configSet);
        $errorCodeKey  = '_zhuanerrcode'.date("Y-m-d",strtotime('-1 day')).'_'.$errorCode;

        $codeTypeSet['errorcode'] = $errorCode;
        $codeTypeList = $this->errorcodeTypeModel->getErrorCodeTypeList($codeTypeSet);
        if($codeTypeList){
            $totalNum = 0;
            foreach($codeTypeList as $key=>$val){
                $isType = $redis->exists($errorCodeKey.'_'.$val['type_name'].'_');
                if(empty($isType))
                    continue;

                $errorCodeNum = $redis->get($errorCodeKey.'_'.$val['type_name'].'_');

                $addErrorCodeLog['errorcode'] = $errorCode;
                $addErrorCodeLog['type_name'] = $val['type_name'];
                $addErrorCodeLog['num'] = (int)$errorCodeNum;
                $addErrorCodeLog['is_end'] = 1;
                $addErrorCodeLog['end_time'] = date("Y-m-d H:i:s",time());
                $addErrorCodeLog['ctime'] = $endTime;
                $this->errorcodeLogModel->addErrorCodeLog($addErrorCodeLog);

                $totalNum = $totalNum + $addErrorCodeLog['num'];
            }

            //更新昨天、前天总数
            $errorCodeSave['yesterday_num'] = $totalNum;
            $errorCodeSave['before_yesterday_num'] = (int)$yesterdayNum;
            $errorCodeSave['updatetime'] = date("Y-m-d H:i:s",time());
            $this->errorcodeCountModel->saveErrorcode($errorCode,$errorCodeSave);
        }
        return true;
    }

}