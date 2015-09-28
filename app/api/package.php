<?php
/**
 * 后台打包管理
 *
 * @category   Leb
 * @package    Leb_Action
 * @author     lihui
 * @version    $Id: package.php 2014-09-30 10:58:00 lihui
 * @copyright (c) 2014 dianjoy.com
 * @license
 */
class packageController extends Application
{
    private $versionModel;

    public function execute($plugins)
    {
        $this->versionModel = $this->loadAppModel('Channel_count');

    }


    public function pak_ppjoyAction()
    {
        $channel = daddslashes(trim($this->reqVar('channel', '')));
        $invite = (int)$this->reqVar('invite', 0);
        $c = daddslashes(trim($this->reqVar('c', '')));

        $pakRe = $this->pakIos($channel, $invite, $c);
        if (1 == $pakRe['status'])
        {
            echo "itms-services://?action=download-manifest&url=" . $pakRe["url"];

        } else
        {
            echo "error";
        }
    }

    private function pakIos($channel, $invite, $c)
    {

        if (!empty($channel))
        {
            $path = "../package/";
            if (!file_exists($path))
            {
                //mkdir("$path", 0700);
            }
            $ipaUrl = _PAK_IOS_IP_ . "/pack.php?channel=$channel&c=$c";
            if (!empty($invite))
            {
                $ipaUrl .= "&code=$invite";
            }


            $sql = "SELECT sys_value from z_sys_conf WHERE sys_key = 'ios_version_zaibei'";
            $versionRe['version'] = $this->versionModel->query($sql)[0]['sys_value'];

            if (empty($versionRe['version']))
            {
                return array('status' => -1, 'error' => '版本号无法获取');
            }
            $ipaName = $this->pakName($channel, $invite, $c) . "_" . trim($versionRe['version']) . ".ipa";
            $plistName = $this->pakName($channel, $invite, $c) . ".plist";

            //生成plist文件
            $plistContent = $this->iosTem($ipaName, trim($versionRe['version']));

            $isWrite = $this->wPlistfile($plistName, $plistContent);


            if (!$isWrite)
            {
                return array('status' => -2, 'error' => 'plist文件生成失败');
            }
            $plistUrl = 'https://www.hongbaosuoping.com/plist/' . $plistName; // how it works

            if (file_exists($path . $ipaName))
            {
                return array('status' => 1, 'error' => '成功', "url" => $plistUrl);
            }

            //生成ipa包
            $packName = $path . $ipaName;
            $headerFile = 'headerFile';
            exec("wget --server-response -O $packName  -o $headerFile '$ipaUrl'"); // 获取ipa包

            $file_size = $this->getContentLengthFromHeaderFile($headerFile);

            curl_short(_DOMAIN_ . "/cron/ipa_all/checkCDN?ipaName=$ipaName&invite=$invite&channel=$channel");  // 异步访问 chackcdn链接, 改变打包状态


            //判断远程文件是否已存在
            if (!file_get_contents($plistUrl))
            {
                return array('status' => -3, 'error' => '无法获取plist文件');
            }
            return array('status' => 1, 'error' => '成功', "url" => $plistUrl);
        }
        return array('status' => 0, 'error' => '打包失败');
    }


    private function getContentLengthFromHeaderFile($filename_header)
    {
        $result = 0;
        if (!file_exists($filename_header)) {
            var_dump("debug");
            return $result;
        }

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
        $plistPath = "/data/plist/";

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

}