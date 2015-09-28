<?php
/**
 * 后台设备修改管理
 *
 * @category   Leb
 * @package    Leb_Action
 * @author     lihui
 * @version    $Id: device_modify.php 2014-10-08 10:58:00 lihui
 * @copyright (c) 2014 dianjoy.com
 * @license
 */
class device_modifyController extends Application
{
    private $deviceModel;
    private $userModel;
    private $configModel;

    public function  execute($plugins)
    {

        $this->deviceModel = $this->loadModel('Device_modify');
        $this->userModel   = $this->loadAppModel('User');
        $this->configModel = C('global.php');


    }

    public function indexAction()
    {


        $page = (int)$this->reqVar('page',1);

        $deviceSet = array();
        $deviceList  = $this->deviceModel->getDeviceModeifyList($deviceSet,$page,20);
        $deviceCount = $this->deviceModel->getDeviceModeifyCount($deviceSet);
        $devicePages = pages($deviceCount,$page,20,'',$array = array());



        $this->assign('deviceList', $deviceList);
        $this->assign('devicePages', $devicePages);
        $this->assign("deviceStatus", $this->configModel['device_modify_status']);

        $this->getViewer()->needLayout(false);



        $this->render('device_modify_list');


    }

    public function addAction()
    {
        die("暂停使用");
        $dosubmit = daddslashes($this->postVar('dosubmit',''));
        $device_id = daddslashes($this->postVar('device_id',''));
        $pnum = daddslashes($this->postVar('pnum',''));

        if($dosubmit){
            if(empty($pnum)){
                $this->redirect('手机号不能为空!', '', 3);
                die();
            }
            if(empty($device_id)){
                $this->redirect('设备ID不能为空!', '', 3);
                die();
            }
            $userRe = $this->userModel->getUser(array("pnum"=>$pnum));
            if(!$userRe){
                $this->redirect('该手机号不存在!', '', 3);
                die();
            }
            $deviceRe = $this->userModel->getUser(array("device_id"=>$device_id));
            if($deviceRe){
                if(trim($userRe['device_id']) == $device_id){
                    $this->redirect('该用户不需要修改设备ID!', '', 3);
                    die();
                }else{
                    $this->redirect('该设备ID已存在,对应手机号为'.$deviceRe['pnum'], '', 10);
                    die();
                }
            }

            $deviceSet['uid']  = $userRe['uid'];
            $deviceSet['pnum'] = $userRe['pnum'];
            $deviceSet['device_id'] = $userRe['device_id'];
            $deviceSet['new_device_id'] = $device_id;
            $deviceSet['creater'] = UNAME;
            $backId = $this->deviceModel->addDeviceModeify($deviceSet);
            if($backId && !empty($userRe['uid'])){
                $isSucceed = $this->userModel->updateDeviceId($userRe['uid'],$device_id);
                if($isSucceed){
                    //清楚旧用户缓存
                    $this->clearRedis($userRe['uid'],$userRe['pnum'],$userRe['device_id']);

                    $this->deviceModel->deviceModeifySucceed($backId);
                }
            }
            $this->redirect('', '/admin/device_modify/add', 0);
            die();
        }

        $this->getViewer()->needLayout(false);
        $this->render('device_modify_add');
    }

    private function clearRedis($uid,$pnum,$deviceId)
    {
        //清理redis缓存
        $redis = Leb_Dao_Redis::getInstance();

        if(!empty($uid)){
            $uKey  = '_ZHUAN_U_L'.$uid;
            $uInfo = $redis->get($uKey);
            if($uInfo)
                $redis->del($uKey);

            $uGKey  = '_ZHUAN_U_S_L_G_'.$uid;
            $uGInfo = $redis->get($uGKey);
            if($uGInfo)
                $redis->del($uGInfo);

            $uPKey  = '_ZHUAN_P_U'.$uid;
            $uPInfo = $redis->get($uPKey);
            if($uPInfo)
                $redis->del($uPInfo);
        }

        if(!empty($pnum)){
            $pKey  = '_ZHUAN_U_P'.$pnum;
            $pInfo = $redis->get($pKey);
            if($pInfo)
                $redis->del($pInfo);
        }

        if(!empty($deviceId)){
            $dKey = '_ZHUAN_U_I_B_D_I'.$deviceId;
            $dInfo = $redis->get($dKey);
            if($dInfo)
                $redis->del($dInfo);
        }
    }

    public function ajaxuserAction()
    {
        $pnum = daddslashes($this->getVar('pnum',''));
        if(!empty($pnum)){
            $userRe = $this->userModel->getUser(array("pnum"=>$pnum));
            if($userRe){
                exit("1");
            }
        }
        exit("0");
    }

    public function ajaxdeviceAction()
    {
        $device_id = daddslashes($this->getVar('device_id',''));
        if(!empty($device_id)){
            $userRe = $this->userModel->getUser(array("device_id"=>$device_id));
            if(!$userRe){
                exit("1");
            }
        }
        exit("0");
    }

}