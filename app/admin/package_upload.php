<?php
/**
 * 后台上传包管理
 *
 * @category   Leb
 * @package    Leb_Action
 * @author     lihui
 * @version    $Id: package_upload.php 2014-11-24 10:58:00 lihui
 * @copyright (c) 2014 dianjoy.com
 * @license
 */
class package_uploadController extends Application
{
    private $versionModel;
    private $packageUpModel;

    public function execute($plugins)
    {
        $this->versionModel = $this->loadAppModel('Version_set');
        $this->packageUpModel = $this->loadModel('Package_upload');
    }

    public function indexAction()
    {
        $search = daddslashes($this->postVar('search', ''));
        $keyword = daddslashes($this->reqVar('keyword', ''));
        $startTime = daddslashes($this->reqVar('start_time', ''));
        $endTime = daddslashes($this->reqVar('end_time', ''));
        $page = (int)$this->reqVar('page', 1);

        $pageUrl = "/admin/package_upload/";
        if (!empty($keyword))
        {
            $packageSet['creater'] = $keyword;
            $pageUrl .= "&keyword=$keyword";
        }

        $whereStr = "1";
        if (!empty($startTime))
        {
            $startTime = $startTime . " 00:00:00";
            $packageSet['start_time'] = $startTime;
            $pageUrl .= "&start_time=$startTime";
        }
        if (!empty($endTime))
        {
            $endTime = $endTime . " 23:59:59";
            $packageSet['end_time'] = $endTime;
            $pageUrl .= "&end_time=$endTime";
        }

        $packageList = $this->packageUpModel->getUpPackageList($packageSet, $page, 20);

        foreach($packageList as &$var)
        {
            $time = strtotime($var['createtime']);
            if(time() - $time < 600)
            {
                $var["clear_cdn"] = '1';
            }
            else
            {
                $var["clear_cdn"] = '';
            }
        }

        $packageCount = $this->packageUpModel->getUpPackageCount($packageSet);
        $packagePages = pages($packageCount, $page, 20, $pageUrl, array());

        $this->assign('startTime', $startTime);
        $this->assign('endTime', $endTime);
        $this->assign('keyword', $keyword);
        $this->assign('packageList', $packageList);
        $this->assign('packagePages', $packagePages);
        $this->assign('siteUrl', str_replace('http', 'https', _SITE_URL_));

        $this->getViewer()->needLayout(false);
        $this->render('package_upload_list');
    }

    public function addAction()
    {
        $dosubmit = daddslashes($this->postVar('dosubmit', ''));
        $pkName = daddslashes($this->postVar('pk_name', ''));
        $fileUpload = $_FILES['file_uplode'];
        if ($dosubmit)
        {
            if (empty($pkName))
            {
                $this->redirect('名称不能为空!', '', 3);
                die();
            } elseif (empty($fileUpload['name']) or substr($fileUpload['name'], -3) != 'ipa')
            {
                $this->redirect('请上传包!', '', 3);
                die();
            }
            $pkUrl = $this->uploadFile($fileUpload, $pkName);
            if (!empty($pkUrl))
            {
                $packageAdd['pk_name'] = $pkName;
                $packageAdd['pk_url'] = $pkUrl;
                $packageAdd['creater'] = UNAME;
                if ($this->packageUpModel->upPackage($packageAdd))
                {
                    $this->redirect('', '/admin/package_upload/', 0);
                }
            }
        }
        $pack['android'] = 'off';
        $pack['ios'] = 'on';

        $this->assign('pack', $pack);
        $this->assign('plist', "off");
        $this->assign('name', "包");

        $this->getViewer()->needLayout(false);
        $this->render('package_upload_add');
    }


    public function add_androidAction()
    {
        $dosubmit = daddslashes($this->postVar('dosubmit', ''));
        $pkName = daddslashes($this->postVar('pk_name', ''));
        $fileUpload = $_FILES['file_uplode'];
        if ($dosubmit)
        {
            if (empty($pkName))
            {
                $this->redirect('名称不能为空!', '', 3);
                die();
            } elseif (empty($fileUpload['name']) or substr($fileUpload['name'], -3) != 'apk' )
            {
                var_dump(substr($fileUpload['name'], -3));die();
                $this->redirect('请上传包!', '', 3);
                die();
            }

            $pkUrl = $this->uploadFile($fileUpload, $pkName);
            if (!empty($pkUrl))
            {
                $packageAdd['pk_name'] = $pkName;
                $packageAdd['pk_url'] = $pkUrl;
                $packageAdd['creater'] = UNAME;
                if ($this->packageUpModel->upPackage($packageAdd))
                {
                    $this->redirect('', '/admin/package_upload/', 0);
                }
            }
        }
        $pack['android'] = 'on';
        $pack['ios'] = 'off';

        $this->assign('pack', $pack);
        $this->assign('plist', "off");
        $this->assign('name', "包");

        $this->getViewer()->needLayout(false);
        $this->render('package_upload_add');
    }


    public function add_plistAction()
    {
        $dosubmit = daddslashes($this->postVar('dosubmit', ''));
        $pkName = daddslashes($this->postVar('pk_name', ''));
        $fileUpload = $_FILES['file_uplode'];
        if ($dosubmit)
        {

            if (empty($pkName))
            {
                $this->redirect('名称不能为空!', '', 3);
                die();
            } elseif (empty($fileUpload['name']) )
            {
                $this->redirect('请上传plist!', '', 3);
                die();
            }
            else if( substr($fileUpload['name'], -5) != 'plist')
            {
                $this->redirect('请上传正确plist!', '', 3);
                die();
            }

            $pkUrl = $this->uploadFile($fileUpload, $pkName);
            if (!empty($pkUrl))
            {
                $packageAdd['pk_name'] = $pkName;
                $packageAdd['pk_url'] = '';
                $packageAdd['creater'] = UNAME;
                if ($this->packageUpModel->upPackage($packageAdd))
                {
                    $this->redirect('', '/admin/package_upload/', 0);
                }
            }
        }
        $this->assign('plist', "on");
        $this->assign('pack', "off");
        $this->assign('name', "plist");

        $this->getViewer()->needLayout(false);
        $this->render('package_upload_add');
    }

    private function uploadFile($filename, $newFilename)
    {
        if (empty($filename) || empty($newFilename))
        {
            return false;
        }

        //上传路径
        $path = "../package/";
        if (!file_exists($path))
        {
            mkdir("$path", 0700);
        }

        $packageZ = strtolower(substr($filename["name"], -3));
        if (!in_array($packageZ, array('apk', 'ipa', 'ist')))
        {
            return false;
        }
        if ($packageZ == 'ist')
        {
            $packageZ = 'plist';
            $path = "../plist/";
            if (!file_exists($path))
            {
//                mkdir("$path", 0700);
            }
        }

        $flag = 0;
        if ($filename["name"] && !empty($newFilename))
        {
            $file2name = $newFilename . '.' . $packageZ;
            $file2 = $path . $file2name;
            if (substr($file2, -4, 4) != ".plist")
            {
                $flag = 1;
            }
        }

        if (file_exists($path . $file2name))
        {
            @unlink($path . $file2name);
        }
        if ($flag == 1)
        {
            $result = move_uploaded_file($filename["tmp_name"], $file2);

            //清除快网缓存
            $urlArr = array(_PHOTO_URL_ . '/hongbao/' . $file2name);
            $dirArr = array(_PHOTO_URL_ . '/hongbao/');
            $this->clearCache($urlArr, $dirArr);
        } else
        {
            $result = move_uploaded_file($filename["tmp_name"], $file2);
        }

        if ($result)
        {
            return _PHOTO_URL_ . '/hongbao/' . $file2name;
        } else
        {
            return false;
        }
    }

    public function clearCacheAction()
    {
        $pkId = daddslashes($this->getVar('pk_id', 0));
        $page = (int)$this->getVar('page', 1);

        $getPackage = $this->packageUpModel->getUpPackage(array('id' => $pkId));
        if ($getPackage)
        {
            //清除快网缓存
            $urlArr = array($getPackage['pk_url']);
            $dirArr = array(_PHOTO_URL_ . '/hongbao/');
            $data = $this->clearCache($urlArr, $dirArr);

            var_dump($urlArr);
            echo "<br>";
            var_dump($dirArr);
            echo "<br>";
            var_dump($data);
            echo "<br>";

            if (!empty($data['rs']))
            {
                $this->redirect($data['error'], '', 100);
                die();
            }
        }
        $this->redirect('', '/admin/package_upload/?page=' . $page, 0);

    }

    private function clearCache($urlArr, $dirArr)
    {
        $data = array('rs' => 0, 'error' => '');
        if (empty($urlArr) || empty($dirArr))
        {
            return array('rs' => -1, 'error' => 'URL无法获取');
        }

        $push = new Plugin_pushcontent();
        $push->setUserInfo('dianjoy.com', 'TeHvNV06', 'dianjoycomFW');
        $call_return = $push->pushData($urlArr, $dirArr);
        if (is_array($call_return))
        {
            switch ($call_return['result'])
            {
                //成功调用，无错误，在break前添加您的成功后处理代码
                case 'success':
                {
                    $data = array('rs' => 1, 'error' => '清除成功');
                    break;
                }
                case 'error':
                {
                    //输出错误信息，在此添加您的错误处理代码
                    $data = array('rs' => -2, 'error' => $call_return['detail_info']);
                    break;
                }
                case 'warning':
                {
                    //部分提交更新成功，但存在有问题的URL
                    //输出提示信息
                    $data = array('rs' => -3, 'error' => $call_return['detail_info']);
                    break;
                }
            }
        }
        return $data;
    }

    public function plistAction()
    {
        $pkId = daddslashes($this->getVar('pk_id', 0));
        $page = (int)$this->getVar('page', 1);
        $getPackage = $this->packageUpModel->getUpPackage(array('id' => $pkId));
        if ($getPackage)
        {
            $versionRe = $this->versionModel->getIosVersion();
            if (empty($versionRe['version']))
            {
                $this->redirect('版本号无法获取!', '', 3);
                die();
            }
            //生成plist文件
            $plistContent = $this->iosTem($getPackage['pk_url'], $versionRe['version']);

            $plistName = $getPackage['pk_name'] . ".plist";
            $isWrite = $this->wPlistfile($plistName, $plistContent);
            if (!$isWrite)
            {
                $this->redirect('plist文件生成失败!', '', 3);
                die();
            }
        }
        $this->redirect('plist文件生成成功', '/admin/package_upload/?page=' . $page, 0);
    }

    //plist模板
    private function iosTem($pkName, $version)
    {
//        if (empty($pkName) || empty($version))
//            return false;
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
                                        <string>' . $pkName . '</string>
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

    //plist文件生成
    private function wPlistfile($filename, $plistContent)
    {
        $isSucceed = 0;
        $plistPath = "../plist/";
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

}