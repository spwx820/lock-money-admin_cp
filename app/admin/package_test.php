<?php
/**
 * 后台上传测试包管理
 *
 * @category   Leb
 * @package    Leb_Action
 * @author     lihui
 * @version    $Id: package_upload.php 2014-12-29 14:58:00 lihui
 * @copyright (c) 2014 dianjoy.com
 * @license
 */
class package_testController extends Application
{
    private $configModel;
    private $packageTestModel;
    private $operateLogModel;

    public function execute($plugins)
    {
        $this->configModel = C('global.php');
        $this->packageTestModel = $this->loadModel('Package_test');
        $this->operateLogModel = $this->loadModel('Operate_log');
    }

    public function indexAction()
    {
        $page = (int)$this->reqVar('page', 1);
        $actionOs = daddslashes(trim($this->reqVar('action_os', '')));
        $actionStatus = daddslashes(trim($this->reqVar('action_status', '')));
        $dateNow = date("Y-m-d H:i:s", time());

        $pageUrl = "/admin/package_test/";
        $packageSet = array();
        if (is_numeric($actionOs))
        {
            $packageSet['pk_os'] = $actionOs;
            $pageUrl .= "&action_os=$actionOs";
        }

        if (is_numeric($actionStatus))
        {
            $packageSet['status'] = $actionStatus;
            $pageUrl .= "&action_status=$actionStatus";
        }

        $packageList = $this->packageTestModel->getTestPackageList($packageSet, $page, 20);
        if ($packageList)
        {
            foreach ($packageList as $key => $val)
            {
                if ($val['end_date'] >= $dateNow)
                {
                    $packageList[$key]['isvalid'] = 1;
                } else
                {
                    $packageList[$key]['isvalid'] = 0;
                }
            }
        }
        $packageCount = $this->packageTestModel->getTestPackageCount($packageSet);
        $packagePages = pages($packageCount, $page, 20, $pageUrl, array());


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

        $this->assign('packageList', $packageList);
        $this->assign('packagePages', $packagePages);
        $this->assign("packageOs", $this->configModel['pk_os']);
        $this->assign("packageScale", $this->configModel['pk_scale']);
        $this->assign("packageStatus", $this->configModel['pk_status']);
        $this->assign("actionOs", $actionOs);
        $this->assign("actionStatus", $actionStatus);
        $this->assign('page', $page);
        $this->assign('adminId', UID);

        $this->getViewer()->needLayout(false);
        $this->render('package_test_list');
    }

    public function addAction()
    {
        $ajax = daddslashes($this->reqVar('ajax', ''));
        if (!empty($ajax))
        {
            if ($ajax == 'pk_version')
            {
                $pkVersion = daddslashes(trim($this->reqVar('pk_version', '')));
                $this->ajaxVersion($pkVersion, 1);
            }
        } else
        {
            $dosubmit = daddslashes($this->postVar('dosubmit', ''));
            $packageAdd['start_date'] = daddslashes($this->postVar('start_date', ''));
            $packageAdd['end_date'] = daddslashes($this->postVar('end_date', ''));
            $packageAdd['pk_version'] = daddslashes(trim($this->postVar('pk_version', '')));
            $packageAdd['scale'] = (int)$this->postVar('pk_scale', 0);
            $packageAdd['pk_os'] = 1;

            $dateNow = date("Y-m-d H:i:s", time());
            $fileUpload = $_FILES['file_uplode'];
            if ($dosubmit)
            {
                //判断是否存在同版本
                $packageSet['pk_os'] = 1;
                $packageSet['pk_version'] = $packageAdd['pk_version'];
                $isVersion = $this->packageTestModel->getTestPackage($packageSet);
                if (empty($packageAdd['pk_version']))
                {
                    $this->redirect('版本不能为空!', '', 3);
                    die();
                } elseif ($isVersion)
                {
                    $this->redirect('该版本已存在!', '', 3);
                    die();
                } elseif (empty($packageAdd['pk_os']) || !in_array($packageAdd['pk_os'], array(1, 2)))
                {
                    $this->redirect('请选择系统!', '', 3);
                    die();
                } elseif (empty($packageAdd['scale']))
                {
                    $this->redirect('请选择投放比例!', '', 3);
                    die();
                } elseif (empty($fileUpload['name']))
                {
                    $this->redirect('请上传包!', '', 3);
                    die();
                } elseif (empty($packageAdd['start_date']) || empty($packageAdd['end_date']))
                {
                    $this->redirect('上、下架时间不能为空!', '', 3);
                    die();
                } elseif ($packageAdd['end_date'] < $packageAdd['start_date'] || $dateNow > $packageAdd['end_date'])
                {
                    $this->redirect('下架时间不能小于上架时间或当前时间!', '', 3);
                    die();
                }

                $packageAdd['pk_name'] = 'hongbaosuoping';
                $pkName = $packageAdd['pk_name'] . '_' . $packageAdd['pk_version'];
                $pkUrl = $this->uploadFile($fileUpload, $pkName);
                if (!empty($pkUrl))
                {
                    $packageAdd['pk_url'] = $pkUrl;
                    if ($this->packageTestModel->addPackage($packageAdd))
                    {
                        //操作日志记录
                        $this->oplog($packageAdd);
                        $this->redirect('', '/admin/package_test/', 0);
                    }
                }
            }
            $this->assign("packageOs", $this->configModel['pk_os']);
            $this->assign("packageScale", $this->configModel['pk_scale']);

            $this->getViewer()->needLayout(false);
            $this->render('package_test_add');
        }
    }

    public function editAction()
    {
        $pakId = (int)$this->reqVar('id', 0);
        $dosubmit = daddslashes(trim($this->postVar('dosubmit', '')));
        $page = (int)$this->getVar('page', 1);
        $pakSave['start_date'] = daddslashes($this->postVar('start_date', ''));
        $pakSave['end_date'] = daddslashes($this->postVar('end_date', ''));
        $pakSave['scale'] = (int)$this->postVar('pk_scale', 0);

        $dateNow = date("Y-m-d H:i:s", time());
        $fileUpload = $_FILES['file_uplode'];
        if ($pakId > 0)
        {
            $pakRe = $this->packageTestModel->getTestPackage(array('id' => $pakId));
            if ($pakRe && !empty($dosubmit))
            {
                $pakSave['status'] = 0;
                if (empty($pakSave['scale']))
                {
                    $this->redirect('请选择投放比例!', '', 3);
                    die();
                } elseif (empty($pakSave['start_date']) || empty($pakSave['end_date']))
                {
                    $this->redirect('上、下架时间不能为空!', '', 3);
                    die();
                } elseif ($pakSave['end_date'] < $pakSave['start_date'] || $dateNow > $pakSave['end_date'])
                {
                    $this->redirect('下架时间不能小于上架时间或当前时间!', '', 3);
                    die();
                }

                $pkUrl = $this->uploadFile($fileUpload, $pakRe['pk_name'] . '_' . $pakRe['pk_version']);
                if (!empty($pkUrl))
                {
                    $pakSave['pk_url'] = $pkUrl;
                }
                if ($this->packageTestModel->savePackage($pakId, $pakSave))
                {
                    //操作日志记录
                    $logAdd = $pakSave;
                    $logAdd['id'] = $pakId;
                    $this->oplog($logAdd);
                    $this->redirect('修改成功!', '/admin/package_test/?page=' . $page, 1);
                }
            }
            $this->assign('pakRe', $pakRe);
        }

        $this->assign("packageOs", $this->configModel['pk_os']);
        $this->assign("packageScale", $this->configModel['pk_scale']);
        $this->getViewer()->needLayout(false);
        $this->render('package_test_edit');
    }

    public function addIosAction()
    {
        $ajax = daddslashes($this->reqVar('ajax', ''));
        if (!empty($ajax))
        {
            if ($ajax == 'pk_version_ios')
            {
                $pkVersion = daddslashes(trim($this->reqVar('pk_version', '')));
                $this->ajaxVersion($pkVersion, 2);
            }
        } else
        {
            $dosubmit = daddslashes($this->postVar('dosubmit', ''));
            $packageAdd['start_date'] = daddslashes($this->postVar('start_date', ''));
            $packageAdd['end_date'] = daddslashes($this->postVar('end_date', ''));
            $packageAdd['pk_version'] = daddslashes(trim($this->postVar('pk_version', '')));
            $packageAdd['pk_url'] = daddslashes(trim($this->postVar('pk_url', '')));
            $packageAdd['scale'] = (int)$this->postVar('pk_scale', 0);
            $packageAdd['pk_os'] = 2;

            $dateNow = date("Y-m-d H:i:s", time());
            if ($dosubmit)
            {
                //判断是否存在同版本
                $packageSet['pk_os'] = 2;
                $packageSet['pk_version'] = $packageAdd['pk_version'];
                $isVersion = $this->packageTestModel->getTestPackage($packageSet);
                if (empty($packageAdd['pk_version']))
                {
                    $this->redirect('版本不能为空!', '', 3);
                    die();
                } elseif ($isVersion)
                {
                    $this->redirect('该版本已存在!', '', 3);
                    die();
                } elseif (empty($packageAdd['scale']))
                {
                    $this->redirect('请选择投放比例!', '', 3);
                    die();
                } elseif (empty($packageAdd['pk_url']))
                {
                    $this->redirect('下载URL不能为空!', '', 3);
                    die();
                } elseif (empty($packageAdd['start_date']) || empty($packageAdd['end_date']))
                {
                    $this->redirect('上、下架时间不能为空!', '', 3);
                    die();
                } elseif ($packageAdd['end_date'] < $packageAdd['start_date'] || $dateNow > $packageAdd['end_date'])
                {
                    $this->redirect('下架时间不能小于上架时间或当前时间!', '', 3);
                    die();
                }

                if ($this->packageTestModel->addPackage($packageAdd))
                {
                    //操作日志记录
                    $this->oplog($packageAdd);
                    $this->redirect('', '/admin/package_test/', 0);
                }
            }

            $this->assign("packageScale", $this->configModel['pk_scale']);
            $this->getViewer()->needLayout(false);
            $this->render('package_test_add_ios');
        }
    }

    public function editIosAction()
    {
        $pakId = (int)$this->reqVar('id', 0);
        $dosubmit = daddslashes(trim($this->postVar('dosubmit', '')));
        $page = (int)$this->getVar('page', 1);
        $pakSave['start_date'] = daddslashes($this->postVar('start_date', ''));
        $pakSave['end_date'] = daddslashes($this->postVar('end_date', ''));
        $pakSave['pk_url'] = daddslashes($this->postVar('pk_url', ''));
        $pakSave['scale'] = (int)$this->postVar('pk_scale', 0);

        $dateNow = date("Y-m-d H:i:s", time());
        if ($pakId > 0)
        {
            $pakRe = $this->packageTestModel->getTestPackage(array('id' => $pakId));
            if ($pakRe && !empty($dosubmit))
            {
                $pakSave['status'] = 0;
                if (empty($pakSave['scale']))
                {
                    $this->redirect('请选择投放比例!', '', 3);
                    die();
                } elseif (empty($pakSave['pk_url']))
                {
                    $this->redirect('下载URL不能为空!', '', 3);
                    die();
                } elseif (empty($pakSave['start_date']) || empty($pakSave['end_date']))
                {
                    $this->redirect('上、下架时间不能为空!', '', 3);
                    die();
                } elseif ($pakSave['end_date'] < $pakSave['start_date'] || $dateNow > $pakSave['end_date'])
                {
                    $this->redirect('下架时间不能小于上架时间或当前时间!', '', 3);
                    die();
                }
                if ($this->packageTestModel->savePackage($pakId, $pakSave))
                {
                    //操作日志记录
                    $logAdd = $pakSave;
                    $logAdd['id'] = $pakId;
                    $this->oplog($logAdd);
                    $this->redirect('修改成功!', '/admin/package_test/?page=' . $page, 1);
                }
            }
            $this->assign('pakRe', $pakRe);
        }

        $this->assign("packageOs", $this->configModel['pk_os']);
        $this->assign("packageScale", $this->configModel['pk_scale']);

        $this->getViewer()->needLayout(false);
        $this->render('package_test_edit_ios');
    }

    private function uploadFile($filename, $newFilename)
    {
        if (empty($filename) || empty($newFilename))
        {
            return false;
        }

        //上传路径
        $path = "../data/pak/";
        if (!file_exists($path))
        {
            mkdir("$path", 0700);
        }

//      $packageApk = "application/vnd.android.package-archive";
        $packageZ = strtolower(substr($filename["name"], -3));
        if (!in_array($packageZ, array('apk', 'ipa')))
        {
            return false;
        }

        $flag = 0;
        if ($filename["name"] && !empty($newFilename))
        {
            $file2name = $newFilename . '.' . $packageZ;
            $file2 = $path . $file2name;
            $flag = 1;
        }

        if (file_exists($path . $file2name))
        {
            @unlink($path . $file2name);
        }
        $result = false;
        if ($flag)
        {
            $result = move_uploaded_file($filename["tmp_name"], $file2);

            //清除快网缓存
            $urlArr = array(_PHOTO_URL_ . '/hbdata/pak/' . $file2name);
            $dirArr = array(_PHOTO_URL_ . '/hbdata/pak/');
            $this->clearCache($urlArr, $dirArr);
        }

        if ($result)
        {
            return _PHOTO_URL_ . '/hbdata/pak/' . $file2name;
        } else
        {
            return false;
        }
    }

    public function auditAction()
    {
        $pkId = daddslashes(trim($this->getVar('pk_id', 0)));
        $page = (int)$this->getVar('page', 1);
        $getPackage = $this->packageTestModel->getTestPackage(array('id' => $pkId));
        if ($getPackage)
        {
            if ($this->packageTestModel->openPackage($pkId))
            {
                //其他包更新为待审核
                $sql = "UPDATE z_package_test SET status=0 WHERE status=1 AND id!='$pkId' AND pk_os='{$getPackage['pk_os']}'";
                $this->packageTestModel->query($sql);
            }

            //操作日志记录
            $logAdd['id'] = $pkId;
            $this->oplog($logAdd);
        }
        $this->redirect('', '/admin/package_test/?page=' . $page, 0);
    }

    public function shutAction()
    {
        $pkId = daddslashes($this->getVar('pk_id', 0));
        $page = (int)$this->getVar('page', 1);
        $getPackage = $this->packageTestModel->getTestPackage(array('id' => $pkId));
        if ($getPackage)
        {
            $this->packageTestModel->shutPackage($pkId);

            //操作日志记录
            $logAdd['id'] = $pkId;
            $this->oplog($logAdd);
        }
        $this->redirect('', '/admin/package_test/?page=' . $page, 0);
    }

    private function ajaxVersion($pkVersion, $pkOs)
    {
        if (!empty($pkVersion) && !empty($pkOs))
        {
            $packageSet['pk_os'] = $pkOs;
            $packageSet['pk_version'] = $pkVersion;
            $isVersion = $this->packageTestModel->getTestPackage($packageSet);
            if (!$isVersion)
            {
                exit("1");
            }
        }
        exit("0");
    }

    public function clearCacheAction()
    {
        $pkId = daddslashes($this->getVar('pk_id', 0));
        $page = (int)$this->getVar('page', 1);

        $getPackage = $this->packageTestModel->getTestPackage(array('id' => $pkId));
        if ($getPackage)
        {
            //清除快网缓存
            $urlArr = array($getPackage['pk_url']);
            $dirArr = array(_PHOTO_URL_ . '/hbdata/pak/');
            $data = $this->clearCache($urlArr, $dirArr);
            if (!empty($data['rs']))
            {
                $this->redirect($data['error'], '', 3);
                die();
            }
        }
        $this->redirect('', '/admin/package_test/?page=' . $page, 0);

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

    private function oplog($addContent)
    {
        if (empty($addContent))
        {
            return false;
        }

        //操作日志记录
        $logAdd['app'] = $this->_application;
        $logAdd['controller'] = $this->_controller;
        $logAdd['action'] = $this->_action;
        $logAdd['content'] = json_encode($addContent);
        $logAdd['ip'] = get_real_ip();
        $logAdd['operat'] = UNAME;
        $this->operateLogModel->addOpLog($logAdd);
    }

}