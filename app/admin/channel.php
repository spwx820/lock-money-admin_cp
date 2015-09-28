<?php
/**
 * 渠道统计
 *
 * @category   Leb
 * @package    Leb_Action
 * @author     lihui
 * @version    $Id: channel.php 2015-06-029 9:58:00 zw
 * @copyright (c) 2014 dianjoy.com
 * @license
 */
class channelController extends Application
{
    private $channelCountModel;
    private $userModel;
    private $dailyStatChannelModel;

    private $deviceLogModel;

    private $channelUserModel;


    public function realtimeAction()
    {
        $search = daddslashes($this->postVar('search', ''));
        $channel = daddslashes($this->postVar('channel', ''));
        $startTime = daddslashes($this->postVar('start_time', ''));
        $endTime = daddslashes($this->postVar('end_time', ''));

        $whereStr = '1';
        if (!empty($channel))
        {
            $channel = daddslashes($channel);
            $whereStr .= " AND channel='$channel'";
        }

        if (empty($startTime))
            $startTime = date("Y-m-d", time());

        if (empty($endTime))
            $endTime = date("Y-m-d", time());

        if (!empty($startTime))
        {
            $whereStr .= " AND ctime>='$startTime'";
        }

        if (!empty($endTime))
        {
            $whereStr .= " AND ctime<='$endTime'";
        }

        $whereStr = trim($whereStr, " AND");
        $channelList = $this->userModel->query("SELECT channel, LEFT(ctime,10) as cdate,
                                                COUNT(*) as user,
                                                AVG(score) AS avg_score,
                                                SUM(score_register)/200 AS referrals,
                                                SUM(score_right_catch)/100 AS rcatch_rmb,
                                                SUM(score_ad)/100 AS ads_rmb,
                                                SUM(IF(score>500,1,0)) as active_user,
                                                SUM(IF(invite_code>0,1,0)) as invited_user,
                                                SUM(IF(invite_code>0,1,0)*IF(score>500,1,0)) AS active_invited_user
                                                FROM z_user
                                                WHERE $whereStr
                                                GROUP BY channel, LEFT(ctime,10) ORDER BY user DESC");
        if ($channelList)
        {
            $toDay = date("Y-m-d", time());
            foreach ($channelList as $key => $val)
            {
                $deviceActiveWhere['channel'] = $val['channel'];
                $deviceActiveWhere['condition'] = " AND left(ctime,10) ='{$val['cdate']}'";
                $deviceActiveCount = $this->deviceLogModel->getDeviceCount($deviceActiveWhere);
                $channelList[$key]['active_num'] = (int)$deviceActiveCount;

                $channelList[$key]['visit_num'] = 0;
                if (!empty($val['cdate']) && $toDay == $val['cdate'])
                {
                    $deviceVisitWhere['channel'] = $val['channel'];
                    $deviceVisitWhere['condition'] = " AND left(update_time,10) ='{$val['cdate']}'";
                    $deviceVisitCount = $this->deviceLogModel->getDeviceCount($deviceVisitWhere);
                    $channelList[$key]['visit_num'] = (int)$deviceVisitCount;
                }
            }
        }

        $this->assign('channel', $channel);
        $this->assign('channelList', $channelList);
        $this->assign('startTime', $startTime);
        $this->assign('endTime', $endTime);

        $this->getViewer()->needLayout(false);
        $this->render('channel_realtime');
    }


    public function  execute($plugins)
    {
        $this->channelCountModel = $this->loadAppModel('Channel_count');
        $this->userModel = $this->loadAppModel('User');
        $this->deviceLogModel = $this->loadAppModel('Device_log');
        $this->dailyStatChannelModel = $this->loadModel('Daily_stat_channel_contribution');
        $this->channelUserModel = $this->loadAppModel('Channel_user');

    }

    public function indexAction()
    {
        $os_type = intval(daddslashes($this->reqVar('os_type', 0)));
        $channel = daddslashes($this->reqVar('channel', ''));
        $startTime = daddslashes($this->reqVar('start_time', ''));
        $endTime = daddslashes($this->reqVar('end_time', ''));
        $page = (int)$this->reqVar('page', 1);

        $pageUrl = "/admin/channel/";
        $channelSet = array();
        if (!empty($channel))
        {
            $channelSet['channel'] .= daddslashes($channel);
            $pageUrl .= "?channel=$channel";
        }

        if (!empty($startTime))
        {
            $channelSet['start_time'] = $startTime;
            $pageUrl .= !empty($channel) ? '&' : '?';
            $pageUrl .= "start_time=$startTime";
        }
        if (!empty($endTime))
        {
            $channelSet['end_time'] = $endTime;
            $pageUrl .= (!empty($startTime) || !empty($channel)) ? '&' : '?';
            $pageUrl .= "end_time=$endTime";
        }

        $channelSet['orderby'] = "id desc";

        $channelList = $this->channelCountModel->getChannelCountList($channelSet, $page, 60);
        $channelCount = $this->channelCountModel->getChannelCountC($channelSet);
        $channelPages = pages($channelCount, $page, 60, $pageUrl, $array = array());

        $channelList_str = "(";
        $statDateList_str = "(";

        foreach ($channelList as $var)
        {
            $statDateList_str .= "'" . substr($var["ctime"], 0, 10) . "',";

            $channelList_str .= "'" . $var["channel"] . "', ";
            $channelNameKey[$var["channel"]] = "channel_" . $var["channel"]; // 以渠道号为key， 序号从1开始
        }
        $channelList_str .= "'')";
        $statDateList_str .= "'')";

        $score_stat = $this->dailyStatChannelModel->query("SELECT * from z_daily_stat_channel_contribution
                                                                      WHERE  channel in $channelList_str
                                                                      AND stat_date in $statDateList_str
                                                                  ");
        $score_stat_cp = [];
        foreach ($score_stat as &$val)
        {
            $score_stat_cp[$val["channel"]] = $val;
        }
        unset($val);

        $score_stat = $score_stat_cp;

        unset($var);


        $this->assign('os_type', $os_type);
        $this->assign('channel', $channel);
        $this->assign('startTime', $startTime);
        $this->assign('endTime', $endTime);
        $this->assign('channelList', $channelList);
        $this->assign('channelPages', $channelPages);

        $this->getViewer()->needLayout(false);
        $this->render('channel');
    }

    public function qualityAction()
    {
        $yesterday = date("Y-m-d", strtotime('-1 day'));

        $os_type = intval(daddslashes($this->reqVar('os_type', 0)));
        $channel = daddslashes($this->reqVar('channel', ''));
        $startTime = daddslashes($this->reqVar('start_time', ''));
        $endTime = daddslashes($this->reqVar('end_time', ''));
        $page = (int)$this->reqVar('page', 1);

        $pageUrl = "/admin/channel/quality";
        $channelSet = array();
        if (!empty($channel))
        {
//            $channelSet['channel'] .= daddslashes($channel);
            $pageUrl .= "?channel=$channel";
            $channelList = $this->channelCountModel->query("SELECT DISTINCT(channel) FROM t_channel_count ;");
            $channelSet['condition'] = " AND channel in (";
            foreach ($channelList as $var)
            {
                if (strstr($var['channel'], $channel))
                    $channelSet['condition'] .= "'" . $var['channel'] . "', ";
            }
            $channelSet['condition'] .= "'_')";
            $channelSet['orderby'] = "channel asc, ctime desc";
        } else
        {
            $channelSet['orderby'] = "id desc";
        }

        if (!empty($startTime))
        {
            $channelSet['start_time'] = $startTime;
            $pageUrl .= !empty($channel) ? '&' : '?';
            $pageUrl .= "start_time=$startTime";
        }
        if (!empty($endTime))
        {
            $channelSet['end_time'] = $endTime;
            $pageUrl .= (!empty($startTime) || !empty($channel)) ? '&' : '?';
            $pageUrl .= "end_time=$endTime";
        }

        $channelList = $this->channelCountModel->getChannelCountList($channelSet, $page, 60);

        $channelCount = $this->channelCountModel->getChannelCountC($channelSet);
        $channelPages = pages($channelCount, $page, 60, $pageUrl, $array = array());

        $channelList = $this->get_quality_data($channelList, $os_type);

        $this->assign('os_type', $os_type);
        $this->assign('channel', $channel);
        $this->assign('startTime', $startTime);
        $this->assign('endTime', $endTime);
        $this->assign('channelList', $channelList);
        $this->assign('channelPages', $channelPages);
        $this->assign('_SITE_URL_', _PHOTO_URL_);

        $this->getViewer()->needLayout(false);
        $this->render('channel_quality');

    }

    public function get_quality_data($channelList, $os_type)
    {

        $channelList_str = "(";
        $statDateList_str = "(";
        foreach ($channelList as $var)
        {
            $statDateList_str .= "'" . substr($var["ctime"], 0, 10) . "',";

            $channelList_str .= "'" . $var["channel"] . "', ";
            $channelNameKey[$var["channel"]] = "channel_" . $var["channel"]; // 以渠道号为key， 序号从1开始
        }
        $channelList_str .= "'')";
        $statDateList_str .= "'')";

        $score_stat = $this->dailyStatChannelModel->query("SELECT * from z_daily_stat_channel_contribution
                                                                      WHERE  channel in $channelList_str
                                                                      AND stat_date in $statDateList_str");
        $score_stat_cp = [];
        foreach ($score_stat as &$val)
        {
            $score_stat_cp[$val["channel"]] = $val;
        }
        unset($val);
        $score_stat = $score_stat_cp;

        $kk = 0;

        foreach ($channelList as &$var)
        {
            $kk += 1;


            $tempC = $var["channel"];
            $tempD = $var["cdate"];

            $tmp = $this->dailyStatChannelModel->query("SELECT ad_user, ad_avg, reg_user, reg_avg FROM t_channel_user WHERE channel = '$tempC' AND cdate = '$tempD' AND code = '0';");
            if (!empty($tmp))
                $tmp = $tmp[0];

            $tmp1 = $this->dailyStatChannelModel->query("SELECT ad_user, ad_avg, reg_user, reg_avg FROM t_channel_user WHERE channel = '$tempC' AND cdate = '$tempD' AND code = '1';");
            if (!empty($tmp1))
                $tmp1 = $tmp1[0];

            $tmp2 = $this->dailyStatChannelModel->query("SELECT ad_user, ad_avg, reg_user, reg_avg FROM t_channel_user WHERE channel = '$tempC' AND cdate = '$tempD' AND code = '2';");
            if (!empty($tmp2))
                $tmp2 = $tmp2[0];

            $tmp3 = $this->dailyStatChannelModel->query("SELECT ad_user, ad_avg, reg_user, reg_avg FROM t_channel_user WHERE channel = '$tempC' AND cdate = '$tempD' AND code = '3';");
            if (!empty($tmp3))
                $tmp3 = $tmp3[0];

            $tmp4 = $this->dailyStatChannelModel->query("SELECT ad_user, ad_avg, reg_user, reg_avg FROM t_channel_user WHERE channel = '$tempC' AND cdate = '$tempD' AND code = '4';");
            if (!empty($tmp4))
                $tmp4 = $tmp4[0];

            $tmp5 = $this->dailyStatChannelModel->query("SELECT ad_user, ad_avg, reg_user, reg_avg FROM t_channel_user WHERE channel = '$tempC' AND cdate = '$tempD' AND code = '5';");
            if (!empty($tmp5))
                $tmp5 = $tmp5[0];


            $tmp_ = $this->dailyStatChannelModel->query("SELECT * FROM z_daily_stat_new_active_user WHERE channel = '$tempC' AND stat_date = '$tempD' AND os_type = 'android' ORDER  BY id DESC limit 1;");
            if (!empty($tmp_))
                $tmp_ = $tmp_[0];
            $tmp_1 = $this->dailyStatChannelModel->query("SELECT * FROM z_daily_stat_new_active_user WHERE channel = '$tempC' AND stat_date = '$tempD' AND os_type = 'ios' ORDER  BY id DESC limit 1;");
            if (!empty($tmp_1))
                $tmp_1 = $tmp_1[0];
            $tmp_2 = $this->dailyStatChannelModel->query("SELECT * FROM z_daily_stat_new_active_user WHERE channel = '$tempC' AND stat_date = '$tempD' AND os_type = '' ORDER  BY id DESC limit 1;");
            if (!empty($tmp_2))
                $tmp_2 = $tmp_2[0];


            if ($os_type == 1)  // android
            {
                $var['user_num'] = $var['user_num_android'] ? $var['user_num_android'] : '-';

                if (!empty($tmp_))
                {
                    $var["new_active_user"] = $tmp_["new_active_user"] ? $tmp_["new_active_user"] : "-";
                } else if (!empty($tmp_2))
                {
                    $var["new_active_user"] = $tmp_2['new_active_user'] . '(总)';
                } else
                {
                    $var["new_active_user"] = "-";
                }

            } else if ($os_type == 2)  // ios
            {
                $var['user_num'] = intval($var['user_num']) - intval($var['user_num_android']);

                $tmp = $tmp3;
                $tmp1 = $tmp4;
                $tmp2 = $tmp5;

                if (!empty($tmp_1))
                {
                    $var["new_active_user"] = $tmp_1["new_active_user"] ? $tmp_1["new_active_user"] : "-";
                } else if (!empty($tmp_2))
                {
                    $var["new_active_user"] = $tmp_2['new_active_user'] . '(总)';
                } else
                {
                    $var["new_active_user"] = "-";
                }

            } else if ($os_type == 0)
            {
                if (empty($tmp))
                {
                    $tmp = $tmp3;
                }
                else if (!empty($tmp3))
                {
                    foreach ($tmp as $key => $val)
                        $tmp[$key] = intval($tmp[$key]) + intval($tmp3[$key]);
                }

                if (empty($tmp1))
                {
                    $tmp1 = $tmp4;
                }
                else if (!empty($tmp4))
                {
                    foreach ($tmp1 as $key => $val)
                        $tmp1[$key] = intval($tmp1[$key]) + intval($tmp4[$key]);
                }

                if (empty($tmp2))
                {
                    $tmp2 = $tmp5;
                }
                else if (!empty($tmp5))
                {
                    foreach ($tmp2 as $key => $val)
                        $tmp2[$key] = intval($tmp2[$key]) + intval($tmp5[$key]);
                }

                if (!empty($tmp_2))
                {
                    $var["new_active_user"] = $tmp_2['new_active_user'];
                } else if (!empty($tmp_) and !empty($tmp_1))
                {
                    $var["new_active_user"] = intval($tmp_['new_active_user']) + intval($tmp_1['new_active_user']);
                }
            }

            if (!empty($tmp))
            {
                $var["dailyStat_ad_user"] = $tmp["ad_user"];
                $var["dailyStat_ad_avg"] = $tmp["ad_avg"];
                $var["dailyStat_reg_user"] = $tmp["reg_user"];
                $var["dailyStat_reg_avg"] = $tmp["reg_avg"];
            } else
            {
                $var["dailyStat_ad_user"] = "-";
                $var["dailyStat_ad_avg"] = "-";
                $var["dailyStat_reg_user"] = "-";
                $var["dailyStat_reg_avg"] = "-";
            }

            if (!empty($tmp1))
            {
                $var["dailyStat_ad_user_1"] = $tmp1["ad_user"];
                $var["dailyStat_ad_avg_1"] = $tmp1["ad_avg"];
                $var["dailyStat_reg_user_1"] = $tmp1["reg_user"];
                $var["dailyStat_reg_avg_1"] = $tmp1["reg_avg"];
            } else
            {
                $var["dailyStat_ad_user_1"] = "-";
                $var["dailyStat_ad_avg_1"] = "-";
                $var["dailyStat_reg_user_1"] = "-";
                $var["dailyStat_reg_avg_1"] = "-";
            }

            if (!empty($tmp2))
            {
                $var["dailyStat_ad_user_2"] = $tmp2["ad_user"];
                $var["dailyStat_ad_avg_2"] = $tmp2["ad_avg"];
                $var["dailyStat_reg_user_2"] = $tmp2["reg_user"];
                $var["dailyStat_reg_avg_2"] = $tmp2["reg_avg"];
            } else
            {
                $var["dailyStat_ad_user_2"] = "-";
                $var["dailyStat_ad_avg_2"] = "-";
                $var["dailyStat_reg_user_2"] = "-";
                $var["dailyStat_reg_avg_2"] = "-";
            }

            if (!empty($score_stat))
            {
                $temp = $score_stat[$var["channel"]]["avg_contribution"];
            } else
            {
                $var["score_today"] = '-';
            }

        }
        unset($var);

        return $channelList;
    }


    public function export_dataAction()
    {
        $yesterday = date("Y-m-d", strtotime('-1 day'));

        $os_type = intval(daddslashes($this->reqVar('os_type', 0)));
        $channel = daddslashes($this->reqVar('channel', ''));
        $startTime = daddslashes($this->reqVar('start_time', ''));
        $endTime = daddslashes($this->reqVar('end_time', ''));
        $page = (int)$this->reqVar('page', 1);

        $pageUrl = "/admin/channel/";
        $channelSet = array();
        if (!empty($channel))
        {
//            $channelSet['channel'] .= daddslashes($channel);
            $pageUrl .= "?channel=$channel";
            $channelList = $this->channelCountModel->query("SELECT DISTINCT(channel) FROM t_channel_user ;");
            $channelSet['condition'] = " AND channel in (";
            foreach ($channelList as $var)
            {
                if (strstr($var['channel'], $channel))
                    $channelSet['condition'] .= "'" . $var['channel'] . "', ";
            }
            $channelSet['condition'] .= "'_')";
            $channelSet['orderby'] = "channel asc, ctime desc";
        } else
        {
            $channelSet['orderby'] = "id desc";
        }

        if (!empty($startTime))
        {
            $channelSet['start_time'] = $startTime;
            $pageUrl .= !empty($channel) ? '&' : '?';
            $pageUrl .= "start_time=$startTime";
        } else
        {
            $day9 = date("Y-m-d", strtotime('-7 day'));

            $channelSet['start_time'] = $day9;
        }
        if (!empty($endTime))
        {
            $channelSet['end_time'] = $endTime;
            $pageUrl .= (!empty($startTime) || !empty($channel)) ? '&' : '?';
            $pageUrl .= "end_time=$endTime";
        } else
        {
            $today = date("Y-m-d", strtotime('-1 day'));

            $channelSet['end_time'] = $today;
        }
        $channelList = $this->channelCountModel->getChannelCountList($channelSet, 1, 10000);

        $channelCount = $this->channelCountModel->getChannelCountC($channelSet);
        $channelPages = pages($channelCount, $page, 60, $pageUrl, $array = array());
        $channelList = $this->get_quality_data($channelList, $os_type);


        $excelContent = $this->export_template($channelList);
        if (empty($excelContent))
        {
            $this->redirect('导出失败,没有导出内容!', '', 1);
            die();
        }
        $excelData = $excelContent;
        header('Content-type:application/vnd.ms-excel;charset=utf-8');
        header("Content-Disposition:filename=channel_stat.csv");
        echo $excelData;
    }

    private function export_template($channelList)
    {

        if (!$channelList) return;

        $replaceArr = array("・", "&nbsp;", " ", "•");
        $excelContent = "'ID, 渠道号,	时间,	用户总数,	每日渠道分数,下载广告用户数(前天),	人均广告下载分数(前天),	成功邀请的用户数(前天),	人均邀请(前天), 下载广告用户数(4天)	,人均广告下载分数(4天), 成功邀请的用户数(4天),	人均邀请数(4天), 下载广告用户数(8天),	人均广告下载分数(8天),	邀请用户(8天),	人均邀请数(8天),	活跃用户数(7天)\r\n";
        foreach ($channelList as $key => $val)
        {
            $excelContent .= $val['id'] . ',' . $val['channel'] . ',' . $val['cdate'] . ',' . $val['user_num'] . ',' . $val['score_today']
                . ',' . $val['dailyStat_ad_user'] . ',' . $val['dailyStat_ad_avg'] . ',' . $val['dailyStat_reg_user'] . ',' . $val['dailyStat_reg_avg']
                . ',' . $val['dailyStat_ad_user_1'] . ',' . $val['dailyStat_ad_avg_1'] . ',' . $val['dailyStat_reg_user_1'] . ',' . $val['dailyStat_reg_avg_1']
                . ',' . $val['dailyStat_ad_user_2'] . ',' . $val['dailyStat_ad_avg_2'] . ',' . $val['dailyStat_reg_user_2'] . ',' . $val['dailyStat_reg_avg_2'] . ',' . $val['new_active_user'] . ',' . "\r\n";
        }
        return $excelContent;
    }


    public function share_statAction()
    {
        $search = daddslashes($this->postVar('search', ''));
        $channel = daddslashes($this->postVar('channel', ''));
        $startTime = daddslashes($this->postVar('start_time', ''));
        $endTime = daddslashes($this->postVar('end_time', ''));

        $this->assign('channel', $channel);
        $this->assign('startTime', $startTime);
        $this->assign('endTime', $endTime);

        $this->getViewer()->needLayout(false);
        $this->render('channel_share');
    }


    public function get_share_qqAction()
    {
        echo '{"10":"23","11":"30","12":"67","23":"78","45":"76"}';

    }

}