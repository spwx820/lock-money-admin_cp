<?php
/**
 * 渠道统计显示(实时查询暂停使用)
 *
 * @category   Leb
 * @package    Leb_Action
 * @author     lihui
 * @version    $Id: channel.php 2014-09-17 10:30:00 lihui
 * @copyright (c) 2014 dianjoy.com
 * @license
 */
class channel_bakController extends Application
{
    private $userModel;

    public function  execute($plugins)
    {
        die();
        $this->userModel = $this->loadAppModel('User');
        $this->passwordStr  = "dianjoy";
    }

    public function indexAction()
    {
        $password  = $this->reqVar('passwd','');
        $channel   = $this->reqVar('channel','');
        $startDate = $this->reqVar('start_date','');
        $endDate   = $this->reqVar('end_date','');

        $whereStr = '';
        if(!empty($channel) && $password == md5($this->passwordStr.$channel)){
            $channel = daddslashes($channel);
            $whereStr .= " AND channel='$channel'";
        }else{
            echo "参数错误！";
            die();
        }

        if(!empty($startDate)){
            $startDate = daddslashes($startDate);
            $whereStr .= " AND left(ctime,10)>='$startDate'";
        }

        if(!empty($endDate)){
            $endDate = daddslashes($endDate);
            $whereStr .= " AND left(ctime,10)<='$endDate'";
        }
        $whereStr = trim($whereStr," AND");

        $re = $this->userModel->query("SELECT channel, LEFT(ctime,10) as cdate,
                                       COUNT(*) as user,
                                       SUM(IF(score>500,1,0)) as active_user
                                       FROM z_user
                                       WHERE $whereStr
                                       GROUP BY channel, LEFT(ctime,10) ORDER BY LEFT(ctime,10), channel");

        if($re){
            echo '<table width="100%" cellspacing="0">
                        <thead>
                        <tr>
                            <th align="left">渠道号</th>
                            <th align="left">时间</th>
                            <th align="left">用户总数</th>
                        </tr>
                        </thead>
                         <tbody>';
            foreach($re as $key=>$val){
                echo '<tr>
                        <td align="left">'.$val['channel'].'</td>
                        <td align="left">'.$val['cdate'].'</td>
                        <td align="left">'.$val['user'].'</td>
                    </tr>';
            }
            echo '</tbody></table>';
        }else{
            echo "结果为空";
        }
    }

}