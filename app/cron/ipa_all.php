<?php
/**
 * ipa升级批量打包
 *
 * @category   Leb
 * @package    Leb_Action
 * @author     lihui
 * @version    $Id: ipa_all.php 2015-05-11 10:58:00 lihui
 * @copyright (c) 2015 dianjoy.com
 * @license
 */
class ipa_allController extends Application
{
    private $packageModel;
    private $versionModel;

    public function execute($plugins)
    {
        $this->packageModel = $this->loadModel('Package', array(), 'admin');
        $this->versionModel = $this->loadAppModel('Version_set');
    }

    /**
     * 默认action
     */
    public function indexAction()
    {
    }

    //ios打包操作
    public function pakAction()
    {
        $redis = Leb_Dao_Redis::getInstance();
        $redisGet = $redis->lRange("ZHUAN_ADMIN_TASK_PACKAGE_BATCH_IOS", 0, 0);
        if (empty($redisGet))
            die("ZHUAN_ADMIN_TASK_PACKAGE_BATCH_IOS not find");

        foreach ($redisGet as $key => $val)
        {
            $redis->lRem("ZHUAN_ADMIN_TASK_PACKAGE_BATCH_IOS", $val, 0);

            $vals = json_decode($val, true);
            if (!empty($vals['uid']) || !empty($vals['channel']))
            {
                $pakRe = $this->pakIos($vals['channel'], $vals['uid'], $vals['c']);

                $dateNow = date("Y-m-d H:i:s", time());
                if (!empty($pakRe['status']) && 1 == $pakRe['status'])
                {
                    $pushValue = array("uid" => $vals['uid'], "channel" => $vals['channel'], "rtn" => 1, "error" => "", "ctime" => $dateNow);
                } else if (!empty($pakRe['status']) && 11 == $pakRe['status'])
                {
                    $pushValue = array("uid" => $vals['uid'], "channel" => $vals['channel'], "rtn" => 11, "error" => $pakRe['error'], "ctime" => $dateNow);
                }
                else{
                        $pushValue = array("uid" => $vals['uid'], "channel" => $vals['channel'], "rtn" => 0, "error" => $pakRe['error'], "ctime" => $dateNow);
                }
                $redis->lPush("ZHUAN_ADMIN_TASK_PACKAGE_BATCH_RESULT_IOS", json_encode($pushValue));
            }
            sleep(10);
        }
        die();
    }

    private function pakIos($channel, $invite, $c)
    {
        if (!empty($channel))
        {
//            $path = _ROOT_ . "package/";
            $path = "/data/hongbao/";
            if (!file_exists($path))
            {
                //mkdir("$path", 0700);
            }

            $ipaUrl = _PAK_IOS_IP_ . "/pack.php?channel=$channel&c=$c";
            if (!empty($invite))
            {
                $ipaUrl .= "&code=$invite";
            }

//            $versionRe = $this->versionModel->getIosVersion();
            $sql = "SELECT sys_value from z_sys_conf WHERE sys_key = 'ios_version'";
            $versionRe['version'] = $this->versionModel->query($sql)[0]['sys_value'];

            if (empty($versionRe['version']))
            {
                return array('status' => -1, 'error' => '版本号无法获取');
            }

            $ipaName = $this->pakName(trim( $channel), $invite, $c) . "_" . trim($versionRe['version']) . ".ipa";
            $plistName = $this->pakName(trim( $channel), $invite, $c) . ".plist";

            //生成plist文件
            $plistContent = $this->iosTem($ipaName, trim($versionRe['version']));
            $isWrite = $this->wPlistfile($plistName, $plistContent);
            if (!$isWrite)
            {
                return array('status' => -2, 'error' => 'plist文件生成失败');
            }

            echo 'filesize : ';
            var_dump($path . $ipaName);
            var_dump(filesize($path . $ipaName));
            echo '<br>';

            var_dump(_DOMAIN_ . "/cron/ipa_all/checkCDN?ipaName=$ipaName&invite=$invite&channel=$channel");

            if (!(file_exists($path . $ipaName) and filesize($path . $ipaName) > 4000))
            {
                if (file_exists($path . $ipaName))
                {
                    echo 'pack : ';
                    echo '<br>';
                    @unlink($path . $ipaName);
                    echo 'filesize : ';
                    var_dump($path . $ipaName);
                    var_dump(filesize($path . $ipaName));
                    echo '<br>';

                }

                //生成ipa包
                $packName = $path . $ipaName;
                $headerFile = 'headerFile';
                exec("wget --server-response -O $packName  -o $headerFile '$ipaUrl'"); // 获取ipa包

                $file_size = $this->getContentLengthFromHeaderFile($headerFile);

                echo 'filesize : ';
                var_dump($path . $ipaName);
                var_dump(filesize($path . $ipaName));
                echo '<br>';
                echo '$ipaUrl : ';
                var_dump($ipaUrl);
                echo '<br>';


                if ((file_exists($path . $ipaName) and filesize($path . $ipaName) > 4000000 and filesize($path . $ipaName) < 9000000))  // 更新数据库的包状态
                {
                    $sql = "UPDATE a_package_log SET status = 1 WHERE uid = '{$invite}' AND channel = '{$channel}' AND pk_os = 2;";
                    $this->versionModel->query($sql);
                } else
                {
                    echo 'ERROR : 打包失败';
                    $sql = "UPDATE a_package_log SET status = -1 WHERE uid = '{$invite}' AND channel = '{$channel}' AND pk_os = 2;";
                    $this->versionModel->query($sql);
                }

                if ($file_size < 4000000 or $file_size > 9000000)
                {
                    $sql = "UPDATE a_package_log SET status = -2 WHERE uid = '{$invite}' AND channel = '{$channel}' AND pk_os = 2;";  // 获取包异常
                    $this->versionModel->query($sql);
                }

                curl_short(_DOMAIN_ . "/cron/ipa_all/checkCDN?ipaName=$ipaName&invite=$invite&channel=$channel");  // 异步访问 chackcdn链接, 改变打包状态

                //判断远程文件是否已存在
                $plistUrl = 'https://www.hongbaosuoping.com/plist/' . $plistName;
                if (!file_get_contents($plistUrl))
                {
                    return array('status' => -3, 'error' => '无法获取plist文件');
                }
            } else
            {
                if ((file_exists($path . $ipaName) and filesize($path . $ipaName) > 4000000 and filesize($path . $ipaName) < 9000000))  // 更新数据库的包状态
                {
                    $sql = "UPDATE a_package_log SET status = 1 WHERE uid = '{$invite}' AND channel = '{$channel}' AND pk_os = 2;";
                    $this->versionModel->query($sql);
                }
                curl_short(_DOMAIN_ . "/cron/ipa_all/checkCDN?ipaName=$ipaName&invite=$invite&channel=$channel");  // 异步访问 chackcdn链接, 改变打包状态

                return array('status' => 11, 'error' => '文件存在, 不用打包');

            }
            return array('status' => 1, 'error' => '成功');
        }
        return array('status' => 0, 'error' => '打包失败');
    }


    private function pakName($channel, $invite, $c = '')
    {
        if ($c == 'c' && !empty($invite))
        {
            $pakName = $channel . '-c_' . $invite;
        } elseif ($c == 'c')
        {
            $pakName = $channel . '-c';
        } elseif (!empty($invite))
        {
            $pakName = $channel . '-' . $invite;
        } else
        {
            $pakName = $channel;
        }
        return $pakName;
    }

    //plist文件生成
    private function wPlistfile($filename, $plistContent)
    {
        $isSucceed = 0;
        $plistPath = _ROOT_ . "plist/";
        if (!file_exists($plistPath))
        {
            //会导致到红包官网软连接失效
            //mkdir("$plistPath", 0700);
        }
        $plistPath .= $filename;
        $fp = fopen($plistPath, "w+"); //打开文件指针，创建文件
        if (!is_writable($plistPath))
        {
            file_put_contents($plistPath, $plistContent);
        }
        if (fwrite($fp, $plistContent))
        {
            $isSucceed = 1;
        }
        fclose($fp);  //关闭指针
        if ($isSucceed)
        {
            return true;
        }
        return false;
    }

    //plist模板
    private function iosTem($pkName, $version)
    {
        $sql = "SELECT sys_value from z_sys_conf WHERE sys_key = 'bundle_identifier'";
        $pack_ID = $this->versionModel->query($sql)[0]['sys_value'];

        $sql = "SELECT sys_value from z_sys_conf WHERE sys_key = 'ios_version'";
        $version = $this->versionModel->query($sql)[0]['sys_value'];

        $outInfo = '<?xml version="1.0.0.1" encoding="UTF-8"?>
                    <!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">
                    <plist version="1.0.0.1">
                    <dict>
                        <key>items</key>
                        <array>
                            <dict>
                                <key>assets</key>
                                <array>
                                    <dict>
                                        <key>kind</key>
                                        <string>software-package</string>
                                        <key>url</key>
                                        <string>' . _PHOTO_URL_ . '/hongbao/' . $pkName . '</string>
                                    </dict>
                                    <dict>
                                        <key>kind</key>
                                        <string>display-image</string>
                                        <key>needs-shine</key>
                                        <true/>
                                        <key>url</key>
                                        <string>http://fast-cdn.dianjoy.com/hongbao/hongbaosuoping_icon_114.png</string>
                                    </dict>
                                </array>
                                <key>metadata</key>
                                <dict>
                                    <key>bundle-identifier</key>
                                    <string>' . $pack_ID . '</string>
                                    <key>bundle-version</key>
                                    <string>' . $version . '</string>
                                    <key>kind</key>
                                    <string>software</string>
                                    <key>subtitle</key>
                                    <string>hongbaosuoping</string>
                                    <key>title</key>
                                    <string>红包锁屏</string>
                                </dict>
                            </dict>
                        </array>
                    </dict>
                    </plist>';
        return $outInfo;
    }

    private function curlGet($url, $timeout = 5, $port = 80)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_POST, 0);

        $result = array();
        $result['result'] = curl_exec($ch);
        if (0 != curl_errno($ch))
        {
            $result['error'] = "Error:\n" . curl_error($ch);
        }
        curl_close($ch);
        return $result;
    }

    public function checkCDNAction()
    {
        sleep(600);

        $ipaName = daddslashes(trim($this->reqVar('ipaName', '')));
        $invite = daddslashes(trim($this->reqVar('invite', '')));
        $channel = daddslashes(trim($this->reqVar('channel', '')));

        $cdn_url = "http://fast-cdn.dianjoy.com/hongbao/$ipaName";

        $responseInfo = get_headers($cdn_url, 1);
        $file_size = intval($responseInfo['Content-Length']);

        var_dump( $cdn_url);
        var_dump($file_size);

        if ($file_size < 4000000 or $file_size > 9000000)
        {
            echo "cdn包异常";
            $sql = "UPDATE a_package_log SET status = -3 WHERE uid = '{$invite}' AND channel like '{$channel}%' AND pk_os = 2;";  // cdn包异常
            $this->versionModel->query($sql);
        }
        else{
            echo "cdn包正常";

            $sql = "UPDATE a_package_log SET status = 2 WHERE uid = '{$invite}' AND channel like '{$channel}%' AND pk_os = 2;";  // cdn包正常
            $this->versionModel->query($sql);
        }
    }


    private function getContentLengthFromHeaderFile($filename_header)
    {
        $result = 0;
        if (!file_exists($filename_header)) {
            var_dump("debug");
            return $result;
        }


        // parse coupon
        $handle = @fopen($filename_header, "r");
        if ($handle)
        {
            while (!feof($handle))
            {
                $buffer = fgets( $handle);  // 按行读取
                $buffer = trim($buffer);
                if(strstr($buffer, "Content-Length:"))
                {

                    $file_size = intval(substr($buffer, 16, strlen($buffer)));
                    if($result < $file_size)
                        $result = $file_size;
                }

            }
            fclose($handle);
        }
        return $result;
    }
}