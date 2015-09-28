<?php
/**
 * 渠道统计运行程序（存储昨天数据）
 * （设备访问日志表会更新到最新的访问时间，防止device_visit_num统计不准确，请在0点后的10分钟内执行）
 *
 * @category   Leb
 * @package    Leb_Action
 * @author     lihui
 * @version    $Id: channel.php 2014-09-17 10:30:00 lihui
 * @copyright (c) 2014 dianjoy.com
 * @license
 */
class channelController extends Application
{
    private $channelCountModel;
    private $userModel;
    private $deviceLogModel;
    private $channelSetModel;

    public function  execute($plugins)
    {
        $this->channelCountModel = $this->loadAppModel('Channel_count');
        $this->userModel = $this->loadAppModel('User');
        $this->deviceLogModel = $this->loadAppModel('Device_log');
        $this->channelSetModel = $this->loadModel('Channel_set',array(),'admin');
    }

    public function indexAction()
    {
    }

    public function cronAction()
    {
        $yesterday = date("Y-m-d",strtotime('-1 day'));
        $channelWhere = " cdate='$yesterday'";
        $isCount = $this->channelCountModel->query("SELECT 'X' FROM t_channel_count WHERE $channelWhere LIMIT 1");
        if(!$isCount ){
            $startDateWhere = $yesterday." 00:00:00";
            $endDateWhere = $yesterday." 23:59:59";
            $whereStr = " ctime>='$startDateWhere' AND ctime<='$endDateWhere'";
            $re = $this->userModel->query("SELECT channel, LEFT(ctime,10) as cdate,
                                           COUNT(*) as user,
                                           SUM(IF(score>500,1,0)) as active_user,
                                           SUM(IF(invite_code>0,1,0)) as invited_user
                                           FROM z_user
                                           WHERE $whereStr
                                           GROUP BY channel ORDER BY ctime ASC");

            $re1 = $this->userModel->query("SELECT channel, LEFT(ctime,10) as cdate,
                                           COUNT(*) as user,
                                           SUM(IF(score>500,1,0)) as active_user,
                                           SUM(IF(invite_code>0,1,0)) as invited_user
                                           FROM z_user
                                           WHERE $whereStr
                                           AND  os_type = 'android'
                                           GROUP BY channel ORDER BY ctime ASC");


            $re1_cp = array();
            foreach($re1 as $key=>$val)
            {
                $re1_cp[$val['channel']] = $val;
            }

            if($re){
                foreach($re as $key=>$val){
                    $channelAdd['user_num'] = $channelAdd['active_num'] = $channelAdd['invited_num'] = '';
                    if(!empty($val['channel'])){
                        $channelSet['channel'] = $val['channel'];
                        $channelSet['status']  = 1;
                        $channelSetRe = $this->channelSetModel->getChannelSet($channelSet);
                        if(!empty($channelSetRe['weight']) && !empty($val['user'])){
                            $channelAdd['user_num'] = $val['user'] * $channelSetRe['weight']/100;
                        }
                        if(!empty($channelSetRe['weight']) && !empty($val['active_user'])){
                            $channelAdd['active_num'] = $val['active_user'] * $channelSetRe['weight']/100;
                        }
                        if(!empty($channelSetRe['weight']) && !empty($val['invited_user'])){
                            $channelAdd['invited_num'] = $val['invited_user'] * $channelSetRe['weight']/100;
                        }
                    }
                    if(empty($channelAdd['user_num'])){
                        $channelAdd['user_num'] = $val['user'];
                    }
                    if(empty($channelAdd['active_num'])){
                        $channelAdd['active_num'] = $val['active_user'];
                    }
                    if(empty($channelAdd['invited_num'])){
                        $channelAdd['invited_num'] = $val['invited_user'];
                    }

                    $startCdateWhere = $val['cdate']." 00:00:00";
                    $endCdateWhere = $val['cdate']." 23:59:59";

                    $deviceActiveWhere['channel'] = $val['channel'];
                    $deviceActiveWhere['condition'] = " AND ctime>='{$startCdateWhere}' AND ctime<='{$endCdateWhere}'";
                    $deviceActiveCount = $this->deviceLogModel->getDeviceCount($deviceActiveWhere);
                    $channelAdd['device_active_num'] = (int)$deviceActiveCount;

                    $deviceVisitWhere['channel'] = $val['channel'];
                    $deviceVisitWhere['condition'] = " AND ctime>='{$startCdateWhere}' AND ctime<='{$endCdateWhere}'";
                    $deviceVisitCount = $this->deviceLogModel->getDeviceCount($deviceVisitWhere);
                    $channelAdd['device_visit_num'] = (int)$deviceVisitCount;

                    $channelAdd['channel'] = $val['channel'];
                    $channelAdd['cdate']   = $yesterday;
                    $channelAdd['user_num_real']   = $val['user'];
                    $channelAdd['active_num_real'] =  $val['active_user'];
                    $channelAdd['invited_num_real'] =  $val['invited_user'];
                    $channelAdd['status'] = 1;
                    $channelAdd['ctime'] = date("Y-m-d H:i:s",time());
                    $channelAdd['user_num_android'] = intval($re1_cp[$val['channel']]['user']);

                    $this->channelCountModel->addChannelCount($channelAdd);

                    $this->channelCountModel->query("UPDATE t_channel_count SET user_num_android = {$channelAdd['user_num_android']}
                    WHERE channel = '{$channelAdd['channel']}' AND ctime = '{$channelAdd['ctime']}'; ");
                    usleep(50);
                }
                echo $yesterday."succeed\r\n";
            }else{
                echo $yesterday."fail:empty data\r\n";
            }
        }else{
            echo $yesterday."fail:not empty data\r\n";
        }
    }

}