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
class invite_code_channelController extends Application
{
    private $userModel;

    public function  execute($plugins)
    {
        $this->userModel = $this->loadAppModel('User');
    }

    public function cronAction()
    {
        $os_type = "ios";
        $this -> stat($os_type);
        $os_type = "android";
        $this -> stat($os_type);
        echo "succ";
    }

    public function stat($os_type)
    {
        $os_flag = 1;
        if($os_type == 'android')
        {$os_flag = 0;}

        $invite_code_set = $this->userModel->query("SELECT code_channel FROM a_invite_code_channel_set;");
        $invite_code_list = [];
        foreach ($invite_code_set as $val)
        {
            $invite_code_list[] = $val['code_channel'];
        }
        $invite_code_str = "(" . join(",", $invite_code_list) . ")";

//        $invite_code_str = '(42816376,46861905,79861020,90570323,45841726,71992544,48895276,77664877,73831830,77712325,96392367,99366866,77712325,45841726,93383293,76368883,87974038,48693797,70045239,76373710,79947840,40818242,45841726,74051717,71433564,43079039,46789345,72940953,45841726,95971201,48020109,73778601,49088160,44889585,75904640,45314951,90881119,49795697,88758076,94151413,86749439,76797449,78452070,85007771,44303054,76675218,83843238,78141602,46604872,86808494,72468776,99236511,94279931,44207886,48828148,77190009,45841726,47204292,45947708,95004135,98954265,48468948,48279363,80279206,46655626,94151413,48651742,44842659,46796516,90969242,77712325,76691642,79097082,46114472,83448058,94279931,94151413,96202541,44697088,76733788,43683758,86808494,41149086,99834194,47388048,70450672,90878858,87720528,95555906,95648451,95004135,43636632,69225170,48657587,90539629,95490580,77850883,49552856,43388053,47581441)';


        $yesterday = date("Y-m-d", strtotime('-1 day'));
//        $channelWhere = "cdate = '$yesterday'";
//        $isCount = $this->userModel->query("SELECT * FROM t_channel_count WHERE $channelWhere LIMIT 1");

        $startDateWhere = $yesterday . " 00:00:00";
        $endDateWhere = $yesterday . " 23:59:59";
        $whereStr = " ctime >= '$startDateWhere' AND ctime <= '$endDateWhere'";
        $invited_user_real = $this->userModel->query("SELECT invite_code, LEFT(ctime,10) AS cdate,
                                           COUNT(*) AS invited_user_real
                                           FROM z_user
                                           WHERE $whereStr AND invite_code IN $invite_code_str AND os_type = '$os_type'
                                           GROUP BY invite_code ORDER BY ctime ASC;");  // 昨日邀请码真实邀请量, 分系统

        $invited_user = $this->userModel->query("SELECT code_channel, invite_num FROM a_invite_code_channel_set WHERE code_channel IN $invite_code_str GROUP BY code_channel;");  // 邀请码邀请量(score_register), 每日新增 = 当天 - 前一天, 不分系统

        $_2day_ago = date("Y-m-d", strtotime('-2 day'));
        $time_start = $_2day_ago ;
        $time_end = $_2day_ago ;
        $time_constrain = " cdate >= '$time_start' AND cdate <= '$time_end'";
        $invited_user_c = $this->userModel->query("SELECT invite_code_channel, user_num FROM invite_code_channel_count WHERE invite_code_channel IN $invite_code_str AND $time_constrain ;");
        $temp = [];

        foreach ($invited_user as $var)
        {
            $temp[$var['code_channel']] = $var;
        }
        $invited_user = $temp;
        $temp = [];
        foreach ($invited_user_c as $var)
        {
            $temp[''. $var['invite_code_channel']] = $var;
        }
        $invited_user_c = $temp;

        $today = date("Y-m-d H:i:s",time());
        foreach ($invited_user_real as $val)
        {
            $user_num = array_key_exists($val['invite_code'], $invited_user_c) ? intval($invited_user[$val['invite_code']]['invite_num']) - intval($invited_user_c[$val['invite_code']]['user_num']) : $invited_user[$val['invite_code']]['invite_num'];
            $this->userModel->query("INSERT INTO invite_code_channel_count (invite_code_channel, cdate, user_num, user_num_real, os_type, ctime, status) VALUES ({$val['invite_code']}, '{$val['cdate']}', {$user_num}, {$val['invited_user_real']}, $os_flag, '$today', 1)");  // 该天的实际邀请数;  核减邀请数
        }

/////////////////////////////////////////////以上是统计 该天的实际邀请数;  核减邀请数
/////////////////////////////////////////////以下是统计 邀请的用户质量的统计

        $dailyStat = $this->userModel->query("SELECT invite_code,
                                                         COUNT(*) AS num,
                                                         SUM(IF(score_ad <> 0, 1, 0))                         AS ad_user,
                                                         SUM(score_ad) / SUM(IF(score_ad <> 0, 1, 0))         AS ad_avg,
                                                         SUM(IF(score_register, 1, 0))                        AS reg_user,
                                                         SUM(score_register) /(200*SUM(IF(score_register, 1, 0)) )  AS reg_avg
                                                        FROM z_user
                                                        WHERE
                                                              $whereStr
                                                          AND
                                                              invite_code in $invite_code_str
                                                          AND
                                                              os_type = '$os_type'
                                                        GROUP BY channel;
                                                        ");


        foreach ($dailyStat as $val)
        {
            $this->userModel->query("UPDATE invite_code_channel_count SET download_user_num = {$val['ad_user']}, download_score_ave = {$val['ad_avg']}, invited_num = {$val['reg_user']}, invited_num_ave = {$val['reg_avg']} WHERE os_type = $os_flag AND cdate = '{$yesterday}' AND invite_code_channel = {$val['invite_code']};");  // 该天 邀请的用户质量的统计
        }
    }

}