<?php
/**
 * widget广告运营
 *
 * @category   Leb
 * @package    Leb_Action
 * @author     lihui
 * @version    $Id: adwidget.php 2015-01-14 14:58:00 lihui
 * @copyright (c) 2015 dianjoy.com
 * @license
 */
class adwidgetController extends Application
{
    private $configModel;
    private $adwidgetModel;
    private $operateLogModel;

    public function execute($plugins)
    {
        $this->configModel = C('global.php');
        $this->adwidgetModel  = $this->loadModel('Adwidget');
        $this->operateLogModel = $this->loadModel('Operate_log');
    }

    public function indexAction()
    {
        $page = (int)$this->reqVar('page',1);
        $actionType = daddslashes($this->postVar('action_type',''));
        $actionStatus = daddslashes($this->postVar('action_status',''));

        $adSet = array();
        if(!empty($actionType)){
            $adSet['action'] = $actionType;
        }
        if(is_numeric($actionStatus)){
            $adSet['z_status'] = $actionStatus;
        }
        $adList  = $this->adwidgetModel->getAdList($adSet,$page,60);
        $adCount = $this->adwidgetModel->getAdCount($adSet);
        $adPages = pages($adCount,$page,60,'',$array = array());

        $this->assign('adList', $adList);
        $this->assign('adCount', $adCount);
        $this->assign('adPages', $adPages);
        $this->assign("adTop", $this->configModel['ad_top']);
        $this->assign("adStatus", $this->configModel['ad_status']);
        $this->assign("adZStatus", $this->configModel['ad_z_status']);
        $this->assign("adType", $this->configModel['ad_widget_type']);
        $this->assign("actionType", $actionType);
        $this->assign("actionStatus", $actionStatus);

        $this->getViewer()->needLayout(false);
        $this->render('adwidget_list');
    }

    public function addAction()
    {
        $dosubmit = daddslashes($this->postVar('dosubmit',''));

        $adAdd['name']  = daddslashes($this->postVar('ad_name',''));
        $adAdd['ad_id'] = daddslashes($this->postVar('ad_id',''));
        $adAdd['click_url'] = daddslashes($this->postVar('click_url',''));
        $adAdd['url_scheme'] = daddslashes($this->postVar('url_scheme',''));
        $adAdd['process_name'] = daddslashes($this->postVar('process_name',''));
        $adAdd['top']  = (int)$this->postVar('top',0);
        $adAdd['tips'] = daddslashes($this->postVar('tips',''));
        $adAdd['start_date'] = daddslashes($this->postVar('start_date',''));
        $adAdd['end_date'] = daddslashes($this->postVar('end_date',''));
        $adAdd['ctime']  = date("Y-m-d H:i:s",time());
        $adAdd['action'] = daddslashes($this->postVar('ac',''));
        $adAdd['message_id'] = (int)$this->postVar('message_id',0);
        $adAdd['protocol'] = 'intent';
        $fileUpload = $_FILES['file_uplode'];
        if(!empty($dosubmit)){
            $checkRe = $this->addCheck($adAdd);
            if(empty($checkRe['s']) && !empty($checkRe['e'])){
                $this->redirect($checkRe['e'], '', 3);
                die();
            }

            list($width_img, $height_img) = getimagesize($fileUpload['tmp_name']);
            if(empty($fileUpload['name'])){
                $this->redirect('请上传图片!', '', 3);
                die();
            }elseif($fileUpload['size'] > 204800){
                $this->redirect('图片大小不能超过200KB', '', 3);
                die();
            }elseif($width_img>1242 || $height_img>2208){
                $this->redirect('图片尺寸超出限制', '', 3);
                die();
            }else{
                $adAdd['url_images'] = $this->uploadFile($fileUpload);
                if(!empty($adAdd['url_images'])){
                    $this->adwidgetModel->addAd($adAdd);

                    //操作日志记录
                    $this->oplog($adAdd);
                    $this->redirect('', '/admin/adwidget/', 0);
                }else{
                    $this->redirect('上传失败,请联系管理员!', '', 3);
                    die();
                }
            }
        }

        //APP消息
        $publicMsg = $this->messageSelect();

        $this->assign('publicMsg', $publicMsg);
        $this->assign("adType", $this->configModel['ad_widget_type']);
        $this->getViewer()->needLayout(false);
        $this->render('adwidget_add');
    }

    //检查输出
    private function addCheck($adAdd)
    {
        $dateNow = date("Y-m-d H:i:s",time());
        if(empty($adAdd['name'])){
            return array('s'=>0,'e'=>'请填写广告名称');
        }elseif(empty($adAdd['action'])){
            return array('s'=>0,'e'=>'请选择广告类别');
        }elseif(empty($adAdd['ad_id']) && in_array($adAdd['action'],array('integral_detail'))){
            return array('s'=>0,'e'=>'请填写积分墙广告ID');
        }elseif(empty($adAdd['message_id']) && in_array($adAdd['action'],array('message_detail'))){
            return array('s'=>0,'e'=>'请选择消息');
        }elseif(empty($adAdd['start_date']) || empty($adAdd['end_date'])){
            return array('s'=>0,'e'=>'上、下架时间不能为空');
        }elseif($adAdd['end_date'] < $adAdd['start_date'] || $dateNow > $adAdd['end_date']){
            return array('s'=>0,'e'=>'下架时间不能小于上架时间或当前时间');
        }elseif(empty($adAdd['tips'])){
            return array('s'=>0,'e'=>'说明不能为空');
        }else{
            return array('s'=>1,'e'=>'');
        }
    }

    public function editAction()
    {
        $adId = (int)$this->reqVar('id',0);
        $dosubmit = daddslashes($this->postVar('dosubmit',''));

        $adRe = $this->adwidgetModel->getAd(array('id'=>$adId));
        if($adId > 0 && $adRe['id'] && !empty($dosubmit)){
            $adSave['name']  = daddslashes($this->postVar('ad_name',''));
            $adSave['ad_id'] = daddslashes($this->postVar('ad_id',''));
            $adSave['click_url'] = daddslashes($this->postVar('click_url',''));
            $adSave['url_scheme'] = daddslashes($this->postVar('url_scheme',''));
            $adSave['process_name'] = daddslashes($this->postVar('process_name',''));
            $adSave['top']  = (int)$this->postVar('top',0);
            $adSave['tips'] = daddslashes($this->postVar('tips',''));
            $adSave['start_date'] = daddslashes($this->postVar('start_date',''));
            $adSave['end_date'] = daddslashes($this->postVar('end_date',''));
            $adSave['action'] = daddslashes($this->postVar('ac',''));
            $adSave['message_id'] = (int)$this->postVar('message_id',0);

            $checkRe = $this->addCheck($adSave);
            if(empty($checkRe['s']) && !empty($checkRe['e'])){
                $this->redirect($checkRe['e'], '', 3);
                die();
            }

            $fileUpload = $_FILES['file_uplode'];
            if(!empty($fileUpload['name'])){
                list($width_img, $height_img) = getimagesize($fileUpload['tmp_name']);
                if($fileUpload['size'] > 204800){
                    $this->redirect('图片大小不能超过200KB', '', 3);
                    die();
                }elseif($width_img>750 || $height_img>370){
                    $this->redirect('图片尺寸超出限制', '', 3);
                    die();
                }

                $adSave['url_images'] = $this->uploadFile($fileUpload);
                if(empty($adSave['url_images'])){
                    $this->redirect('上传失败,请联系管理员!', '', 3);
                    die();
                }
            }

            $this->adwidgetModel->saveAd($adRe['id'],$adSave);

            //操作日志记录
            $this->oplog($adSave);
            $this->redirect('', '/admin/adwidget/', 0);
        }

        //APP消息
        $publicMsg = $this->messageSelect();

        $this->assign('adRe', $adRe);
        $this->assign('publicMsg', $publicMsg);
        $this->assign("adType", $this->configModel['ad_widget_type']);
        $this->getViewer()->needLayout(false);
        $this->render('adwidget_edit');
    }

    private function messageSelect()
    {
        $publicMsgModel = $this->loadAppModel('Public_msg');

        $msgSet['condition'] = " AND os_type in(0,2)";
        $publicMsg = $publicMsgModel->getMsgList($msgSet,1,60);
        return $publicMsg;
    }

    public function openAction()
    {
        $adId = (int)$this->reqVar('id',0);
        if($adId>0){
            $this->adwidgetModel->openAd($adId);

            //操作日志记录
            $logAdd['id'] = $adId;
            $this->oplog($logAdd);
        }
        $this->redirect('', '/admin/adwidget/', 0);
    }

    public function shutAction()
    {
        $adId = (int)$this->reqVar('id',0);
        if($adId>0){
            $this->adwidgetModel->shutAd($adId);

            //操作日志记录
            $logAdd['id'] = $adId;
            $this->oplog($logAdd);
        }
        $this->redirect('', '/admin/adwidget/', 0);
    }

    private function uploadFile($filename)
    {
        if(empty($filename)){
            return false;
        }

        $path="../data/adwidget/"; //上传路径
        if(!file_exists($path)){
            mkdir("$path", 0700);
        }
        //允许上传的文件格式
        $tp = array("image/pjpeg","image/jpeg","image/jpg","image/png");
        if(!in_array($filename["type"],$tp)){
            return false;
        }

        $flag = 0;
        if($filename["name"]){
            $imgType = explode("/",$filename["type"]);
            if(in_array($imgType[1],array('pjpeg','jpeg','jpg'))){
                $imgType[1] = 'jpg';
            }

            $file2name = md5_file($filename["tmp_name"]).'.'.$imgType[1];
            $file2 = $path.$file2name;
            $flag = 1;
        }
        $result = false;
        if($flag)
            $result = move_uploaded_file($filename["tmp_name"],$file2);

        if($result){
            return _PHOTO_URL_.'/hbdata/adwidget/'.$file2name;
        }else{
            return false;
        }
    }

    private function oplog($addContent)
    {
        if(empty($addContent)){
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