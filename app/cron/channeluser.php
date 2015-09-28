<?php
/**
 * 前天注册用户行为统计运行程序（存储昨天数据）
 * （由于依赖于Channel_count表， 请在每日0点的30分钟后执行）
 *
 * @category   Leb
 * @package    Leb_Action
 * @author     lihui
 * @version    $Id: channel.php 2014-09-17 10:30:00 zw
 * @copyright (c) 2015 dianjoy.com
 * @license
 */
class channeluserController extends Application
{

    private $channelCountModel;
    private $userModel;
    private $channelUserModel;

    public function  execute($plugins)
    {
        $this->channelCountModel = $this->loadAppModel('Channel_count');
        $this->userModel = $this->loadAppModel('User');
        $this->channelUserModel = $this->loadAppModel('Channel_user');
    }

    public function indexAction()
    {
    }

    public function cronAction()
    {
        $day = daddslashes(trim($this->reqVar('day', '')));
        $time_stamp = time();
        if (!empty($day))
            $time_stamp = strtotime($day);

        $ctime = date("Y-m-d", $time_stamp - 60 * 60 * 24 * 2); // 2天注册的用户 android
        $this->stat($ctime, 0);
        $ctime = date("Y-m-d", $time_stamp - 60 * 60 * 24 * 4); // 4天注册的用户
        $this->stat($ctime, 1);
        $ctime = date("Y-m-d", $time_stamp - 60 * 60 * 24 * 8); // 8天注册的用户
        $this->stat($ctime, 2);

        $ctime = date("Y-m-d", $time_stamp - 60 * 60 * 24 * 2); // 2天注册的用户 ios
        $this->stat($ctime, 3);
        $ctime = date("Y-m-d", $time_stamp - 60 * 60 * 24 * 4); // 4天注册的用户
        $this->stat($ctime, 4);
        $ctime = date("Y-m-d", $time_stamp - 60 * 60 * 24 * 8); // 8天注册的用户
        $this->stat($ctime, 5);

    }

    public function stat($ctime, $code)
    {
        $today = date("Y-m-d", time());
        $ctime_1 = date("Y-m-d", strtotime($ctime . " +1 days"));
        if ($code < 3)
        {
            $os_type = 'android';
        } else
        {
            $os_type = 'ios';
        }
        $channelCount = $this->channelCountModel->query("SELECT COUNT(DISTINCT channel) FROM t_channel_count ")[0]["COUNT(*)"];
        $channelCount = intval($channelCount);
        $channelNameKey = [];


        $channelList = $this->channelCountModel->query("SELECT DISTINCT channel FROM t_channel_count");

        $channelList_str = "(";

        foreach ($channelList as $var)
        {
            $channelList_str .= "'" . $var["channel"] . "', ";

            $channelNameKey[$var["channel"]] = "channel_" . $var["channel"]; // 以渠道号为key， 序号从1开始
        }
        $channelList_str .= "'')";
        $dailyStat = $this->userModel->query("SELECT channel,
                                                         COUNT(*) AS num,
                                                         SUM(IF(score_ad <> 0, 1, 0))                         AS ad_user,
                                                         SUM(score_ad) / SUM(IF(score_ad <> 0, 1, 0))         AS ad_avg,
                                                         SUM(IF(score_register, 1, 0))                        AS reg_user,
                                                         SUM(score_register) /(200*SUM(IF(score_register, 1, 0)) )  AS reg_avg
                                                        FROM z_user
                                                        WHERE
                                                              ctime > '$ctime' and ctime < '$ctime_1'
                                                          AND
                                                              channel in $channelList_str
                                                          AND
                                                              os_type = '$os_type'
                                                        GROUP BY channel
                                                        ");

        $queryStr = "";
        foreach ($dailyStat as $var)
        {
            $temp0 = $var["ad_user"] ? $var["ad_user"] : 0;
            $temp1 = $var["ad_avg"] ? $var["ad_avg"] : 0;

            $temp2 = $var["reg_user"] ? $var["reg_user"] : 0;
            $temp3 = $var["reg_avg"] ? $var["reg_avg"] : 0;

            $queryStr .= "('" . $var["channel"] . "', ";
            $queryStr .= "'" . $ctime . "' , ";

            $queryStr .= $temp0 . ", ";
            $queryStr .= $temp1 . ", ";
            $queryStr .= $temp2 . ", ";
            $queryStr .= $temp3 . "," . $code . ", '$today'),";
        }

        $queryStr = substr($queryStr, 0, strlen($queryStr) - 1);
        if (!empty($queryStr))
        {
            $sql = "INSERT INTO t_channel_user (channel, cdate, ad_user, ad_avg, reg_user, reg_avg, code, udate) VALUES $queryStr";
        }
        $this->channelUserModel->query($sql);
    }
}