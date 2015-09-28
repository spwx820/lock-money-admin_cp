<?php
/**
 * 渠道统计显示
 *
 * @category   Leb
 * @package    Leb_Action
 * @author     lihui
 * @version    $Id: channel.php 2014-09-17 10:30:00 lihui
 * @copyright (c) 2014 dianjoy.com
 * @license
 */
class invite_codeController extends Application
{
    private $channelCountModel;

    public function  execute($plugins)
    {
        $this->channelCountModel = $this->loadAppModel('Channel_count');
        $this->passwordStr  = "dianjoy";
    }

    public function indexAction()
    {
        $password  = trim($this->reqVar('passwd',''));
        $invite_code_channel   = trim($this->reqVar('invite_code',''));
        $startDate = trim($this->reqVar('start_date',''));
        $endDate   = trim($this->reqVar('end_date',''));

        $whereStr = '';
        if(!empty($invite_code_channel) && $password == md5($this->passwordStr.$invite_code_channel)){
            $channel = daddslashes($invite_code_channel);
            $whereStr .= " AND invite_code_channel='$invite_code_channel'";
        }else{
            echo "参数错误！";
            die();
        }

        if(!empty($startDate)){
            $startDate = daddslashes($startDate);
            $whereStr .= " AND cdate >= '$startDate'";
        }

        if(!empty($endDate)){
            $endDate = daddslashes($endDate);
            $whereStr .= " AND cdate <= '$endDate'";
        }
        $whereStr = trim($whereStr," AND");

        $re = $this->channelCountModel->query("SELECT invite_code_channel, cdate, user_num FROM invite_code_channel_count WHERE $whereStr GROUP by cdate ORDER BY id DESC");
        if($re){
            echo '<table width="100%" cellspacing="0">
                        <thead>
                        <tr>
                            <th align="left">邀请码</th>
                            <th align="left">时间</th>
                            <th align="left">用户总数</th>
                        </tr>
                        </thead>
                        <tbody>';

            foreach($re as $key=>$val){
                echo '<tr>
                        <td align="left">'.$val['invite_code_channel'].'</td>
                        <td align="left">'.$val['cdate'].'</td>
                        <td align="left">'.$val['user_num'].'</td>
                    </tr>';
            }
            echo '</tbody></table>';
        }else{
            echo "结果为空";
        }
    }

}