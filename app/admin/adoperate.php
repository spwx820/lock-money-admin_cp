<?php
/**
 * 广告运营
 *
 * @category   Leb
 * @package    Leb_Action
 * @author     lihui
 * @version    $Id: adoperate.php 2014-09-17 14:58:00 lihui
 * @copyright (c) 2014 dianjoy.com
 * @license
 */
class adoperateController extends Application
{
    private $configModel;
    private $adModel;
    private $operateLogModel;

    public function  execute($plugins)
    {
        $this->configModel = C('global.php');
        $this->adModel = $this->loadModel('Ad_operate');
        $this->operateLogModel = $this->loadModel('Operate_log');
    }

    public function indexAction()
    {
        $page = (int)$this->reqVar('page',1);
        $actionType = daddslashes($this->postVar('action_type',''));
        $actionStatus = daddslashes($this->postVar('action_status',''));
        $keyword = daddslashes($this->reqVar('keyword',''));

        $pageUrl = "/admin/adoperate/";
        if(!empty($actionType)){
            $adSet['action'] = $actionType;
            $pageUrl .= "?action_type=$actionType";
        }
        if(is_numeric($actionStatus)){
            $adSet['z_status'] = $actionStatus;
            $pageUrl .= !empty($actionType) ? '&' : '?';
            $pageUrl .= "action_status=$actionStatus";
        }
        if(!empty($keyword)){
            $adSet['title'] = $keyword;
            $pageUrl .= (!empty($actionType) ||  !empty($actionStatus)) ? '&' : '?';
            $pageUrl .= "keyword=$keyword";
        }

        $adSet['condition'] = " AND action!='webdetail'";
        $adList = $this->adModel->getAdList($adSet,$page,60);
        $adCount = $this->adModel->getAdCount($adSet);
        $adPages = pages($adCount,$page,60,$pageUrl,$array = array());

        $this->assign('adList', $adList);
        $this->assign('adCount', $adCount);
        $this->assign('adPages', $adPages);
        $this->assign("adTop", $this->configModel['ad_top']);
        $this->assign("adStatus", $this->configModel['ad_status']);
        $this->assign("adZStatus", $this->configModel['ad_z_status']);
        $this->assign("adType", $this->configModel['ad_type']);
        $this->assign("actionType", $actionType);
        $this->assign("actionStatus", $actionStatus);
        $this->assign("keyword", $keyword);

        $this->getViewer()->needLayout(false);
        $this->render('adoperate_list');
    }

    public function adddetailAction()
    {
        $dosubmit = daddslashes($this->postVar('dosubmit',''));

        $adAdd['name'] = daddslashes($this->postVar('ad_name',''));
        $adAdd['ad_id'] = daddslashes($this->postVar('ad_id',''));
        $adAdd['top'] = (int)$this->postVar('top',0);
        $adAdd['tips']  = daddslashes($this->postVar('tips',''));
        $adAdd['start_date'] = daddslashes($this->postVar('start_date',''));
        $adAdd['end_date'] = daddslashes($this->postVar('end_date',''));
        $adAdd['ctime'] = date("Y-m-d H:i:s",time());
        $adAdd['protocol'] = 'http';
        $adAdd['action'] = daddslashes($this->postVar('ac',''));
        $adAdd['z_status'] = 0;
        $fileUpload = $_FILES['file_uplode'];

        if(!empty($dosubmit)){
            if(empty($adAdd['name'])){
                $this->redirect('请填写广告名称!', '', 3);
                die();
            }elseif(empty($adAdd['action'])){
                $this->redirect('请选择广告类别!', '', 3);
                die();
            }elseif(empty($adAdd['ad_id'])){
                $this->redirect('请填写积分墙广告ID!', '', 3);
                die();
            }elseif(empty($fileUpload['name'])){
                $this->redirect('请上传图片!', '', 3);
                die();
            }else{
                $newFilename = 's0_'.$adAdd['ad_id'];
                $adAdd['url_images'] = $this->uploadFile($fileUpload,$newFilename);
                if(!empty($adAdd['url_images'])){
                    $this->adModel->addAd($adAdd);

                    //操作日志记录
                    $this->oplog($adAdd);
                    $this->redirect('', '/admin/adoperate/', 0);
                }else{
                    $this->redirect('上传失败,请联系管理员!', '', 3);
                    die();
                }
            }
        }

        $this->assign("adDetail", $this->configModel['ad_detail']);
        $this->getViewer()->needLayout(false);
        $this->render('adoperate_add_detail');
    }

    public function editdetailAction()
    {
        $adId = (int)$this->reqVar('id',0);
        $dosubmit = daddslashes($this->postVar('dosubmit',''));
        if($adId>0){
            $adRe = $this->adModel->getAd(array('id'=>$adId));
            if($adRe && !empty($dosubmit)){
                $adSave['top']   = (int)$this->postVar('top',0);
                $adSave['tips']  = daddslashes($this->postVar('tips',''));
                $adSave['start_date'] = daddslashes($this->postVar('start_date',''));
                $adSave['end_date'] = daddslashes($this->postVar('end_date',''));
                $fileUpload = $_FILES['file_uplode'];
                if(!empty($fileUpload['name'])){
                    $newFilename = '';
                    $urlImages = explode("/",$adRe['url_images']);
                    if(!empty($adRe['url_images']) && !empty($urlImages)){
                        $urlImagesLastStr = end($urlImages);
                        $tagArr = explode("_",$urlImagesLastStr);
                        $tagNum = substr($tagArr[0],1,1);
                        if(is_numeric($tagNum) && !empty($adRe['ad_id'])){
                            $tagNum = $tagNum + 1;
                            $newFilename = 's'.$tagNum.'_'.$adRe['ad_id'];
                        }
                    }else{
                        $newFilename = 's0_'.$adRe['ad_id'];
                    }

                    $adImagesUrl = $this->uploadFile($fileUpload,$newFilename);
                    if(empty($adImagesUrl)){
                        $this->redirect('上传失败,请联系管理员!', '', 3);
                        die();
                    }else{
                        $adSave['url_images'] = $adImagesUrl;
                    }
                }
                $this->adModel->saveAd($adId,$adSave);

                //操作日志记录
                $logAdd = $adSave;
                $logAdd['id'] = $adId;
                $this->oplog($logAdd);
                $this->redirect('', '/admin/adoperate/', 0);
            }
            $this->assign('adRe', $adRe);
        }
        $this->assign("adDetail", $this->configModel['ad_detail']);
        $this->getViewer()->needLayout(false);
        $this->render('adoperate_edit_detail');
    }

    public function addclickdetailAction()
    {
        $dosubmit = daddslashes($this->postVar('dosubmit',''));

        $adAdd['name'] = daddslashes($this->postVar('ad_name',''));
        $adAdd['top']   = (int)$this->postVar('top',0);
        $adAdd['tips']  = daddslashes($this->postVar('tips',''));
        $adAdd['price'] = (int)$this->postVar('price',0);
        $adAdd['click_url']  = daddslashes($this->postVar('click_url',''));
        $adAdd['click_num']  = (int)$this->postVar('click_num',0);
        $adAdd['start_date'] = daddslashes($this->postVar('start_date',''));
        $adAdd['end_date'] = daddslashes($this->postVar('end_date',''));
        $adAdd['ctime'] = date("Y-m-d H:i:s",time());
        $adAdd['protocol'] = 'http';
        $adAdd['action'] = daddslashes($this->postVar('ac',''));
        $adAdd['z_status'] = 0;
        $adAdd['status'] = 1;
        $fileUpload = $_FILES['file_uplode'];

        if(!empty($dosubmit)){
            if(empty($adAdd['name'])){
                $this->redirect('请填写广告名称!', '', 3);
                die();
            }elseif(empty($adAdd['action'])){
                $this->redirect('请选择广告类别!', '', 3);
                die();
            }elseif(empty($adAdd['click_url'])){
                $this->redirect('请填写来源地址!', '', 3);
                die();
            }elseif(empty($adAdd['click_num'])){
                $this->redirect('请设置点击数!', '', 3);
                die();
            }elseif(empty($fileUpload['name'])){
                $this->redirect('请上传图片!', '', 3);
                die();
            }else{
                $adId = $this->adModel->addAd($adAdd);
                if(!empty($adId)){
                    $newFilename = 'web0_'.$adId;
                    $adSave['url_images'] = $this->uploadFile($fileUpload,$newFilename);
                    if(!empty($adSave['url_images'])){
                        $this->adModel->saveAd($adId,$adSave);

                        //操作日志记录
                        $logAdd = $adAdd;
                        $logAdd['url_images'] = $adSave['url_images'];
                        $logAdd['id'] = $adId;
                        $this->oplog($logAdd);
                    }
                }
                $this->redirect('', '/admin/adoperate/', 0);
            }
        }
        $this->assign("adClick", $this->configModel['ad_click_http']);
        $this->getViewer()->needLayout(false);
        $this->render('adoperate_add_clickdetail');
    }

    public function editclickdetailAction()
    {
        $adId = (int)$this->reqVar('id',0);
        $dosubmit = daddslashes($this->postVar('dosubmit',''));
        if($adId>0){
            $adRe = $this->adModel->getAd(array('id'=>$adId));
            if($adRe && !empty($dosubmit)){
                $adSave['top']  = (int)$this->postVar('top',0);
                $adSave['tips'] = daddslashes($this->postVar('tips',''));
                $adSave['price'] = (int)$this->postVar('price',0);
                $adSave['start_date'] = daddslashes($this->postVar('start_date',''));
                $adSave['end_date'] = daddslashes($this->postVar('end_date',''));
                $adSave['status'] = 1;
                $fileUpload = $_FILES['file_uplode'];

                if(!empty($fileUpload['name'])){
                    $newFilename = '';
                    $urlImages = explode("/",$adRe['url_images']);
                    if(!empty($adRe['url_images']) && !empty($urlImages)){
                        $urlImagesLastStr = end($urlImages);
                        $tagArr = explode("_",$urlImagesLastStr);
                        $tagNum = substr($tagArr[0],3,1);
                        if(is_numeric($tagNum)){
                            $tagNum = $tagNum + 1;
                            $newFilename = 'web'.$tagNum.'_'.$adId;
                        }
                    }else{
                        $newFilename = 'web0_'.$adId;
                    }
                    $adImagesUrl = $this->uploadFile($fileUpload,$newFilename);
                    if(empty($adImagesUrl)){
                        $this->redirect('上传失败,请联系管理员!', '', 3);
                        die();
                    }else{
                        $adSave['url_images'] = $adImagesUrl;
                    }
                }
                $this->adModel->saveAd($adId,$adSave);

                //操作日志记录
                $logAdd = $adSave;
                $logAdd['id'] = $adId;
                $this->oplog($logAdd);
                $this->redirect('', '/admin/adoperate/', 0);
            }
            $this->assign('adRe', $adRe);
        }

        $this->assign("adClick", $this->configModel['ad_click_http']);
        $this->getViewer()->needLayout(false);
        $this->render('adoperate_edit_clickdetail');
    }

    public function addintentAction()
    {
        $dosubmit = daddslashes($this->postVar('dosubmit',''));

        $adAdd['name'] = daddslashes($this->postVar('ad_name',''));
        $adAdd['pack_name'] = daddslashes($this->postVar('pack_name',''));
        $adAdd['click_url'] = daddslashes($this->postVar('click_url',''));
        $adAdd['top']  = (int)$this->postVar('top',0);
        $adAdd['tips'] = daddslashes($this->postVar('tips',''));
        $adAdd['price'] = (int)$this->postVar('price',0);
        $adAdd['start_date'] = daddslashes($this->postVar('start_date',''));
        $adAdd['end_date'] = daddslashes($this->postVar('end_date',''));
        $adAdd['ctime'] = date("Y-m-d H:i:s",time());
        $adAdd['action'] = daddslashes($this->postVar('ac',''));
        $adAdd['z_status'] = 0;
        $adAdd['status'] = 1;
        $fileUpload = $_FILES['file_uplode'];

        //web特殊处理
        if($adAdd['action'] == 'web'){
            $adAdd['protocol'] = 'web';
        }else{
            $adAdd['protocol'] = 'intent';
        }

        if(!empty($dosubmit)){
            if(empty($adAdd['name'])){
                $this->redirect('请填写广告名称!', '', 3);
                die();
            }elseif(empty($adAdd['action'])){
                $this->redirect('请选择应用类别!', '', 3);
                die();
            }elseif(empty($adAdd['pack_name']) && in_array($adAdd['action'],array('external','uninstall'))){
                $this->redirect('该类别需要填写包名!', '', 3);
                die();
            }elseif(empty($adAdd['click_url']) && in_array($adAdd['action'],array('web'))){
                $this->redirect('该类别需要填写包名!', '', 3);
                die();
            }elseif(empty($fileUpload['name'])){
                $this->redirect('请上传图片!', '', 3);
                die();
            }else{
                $adId = $this->adModel->addAd($adAdd);
                if(!empty($adId)){
                    $newFilename = 'intent0_'.$adId;
                    $adSave['url_images'] = $this->uploadFile($fileUpload,$newFilename);
                    if(!empty($adSave['url_images'])){
                        $this->adModel->saveAd($adId,$adSave);

                        //操作日志记录
                        $logAdd = $adAdd;
                        $logAdd['id'] = $adId;
                        $logAdd['url_images'] =  $adSave['url_images'];
                        $this->oplog($logAdd);
                    }
                }
                $this->redirect('', '/admin/adoperate/', 0);
            }
        }
        $this->assign("adIntent", $this->configModel['ad_intent']);
        $this->getViewer()->needLayout(false);
        $this->render('adoperate_add_intent');
    }

    public function editintentAction()
    {
        $adId = (int)$this->reqVar('id',0);
        $dosubmit = daddslashes($this->postVar('dosubmit',''));
        if($adId>0){
            $adRe = $this->adModel->getAd(array('id'=>$adId));
            if($adRe && !empty($dosubmit)){
                $adSave['pack_name'] = daddslashes($this->postVar('pack_name',''));
                $adSave['click_url'] = daddslashes($this->postVar('click_url',''));
                $adSave['top']  = (int)$this->postVar('top',0);
                $adSave['tips'] = daddslashes($this->postVar('tips',''));
                $adSave['price'] = (int)$this->postVar('price',0);
                $adSave['start_date'] = daddslashes($this->postVar('start_date',''));
                $adSave['end_date'] = daddslashes($this->postVar('end_date',''));
                $adSave['status'] = 1;
                $fileUpload = $_FILES['file_uplode'];

                if(empty($adSave['pack_name']) && in_array($adRe['action'],array('external','uninstall'))){
                    $this->redirect('该类别需要填写包名!', '', 3);
                    die();
                }elseif(empty($adSave['click_url']) && in_array($adRe['action'],array('web'))){
                    $this->redirect('该类别需要填写包名!', '', 3);
                    die();
                }

                if(!empty($fileUpload['name'])){
                    $newFilename = '';
                    $urlImages = explode("/",$adRe['url_images']);
                    if(!empty($urlImages) && in_array('hbdata',$urlImages)){
                        $urlImagesLastStr = end($urlImages);
                        $tagArr = explode("_",$urlImagesLastStr);
                        $tagNum = substr($tagArr[0],6,1);
                        if(is_numeric($tagNum)){
                            $tagNum = $tagNum + 1;
                            $newFilename = 'intent'.$tagNum.'_'.$adId;
                        }
                    }else{
                        $newFilename = 'intent0_'.$adId;
                    }

                    $adImagesUrl = $this->uploadFile($fileUpload,$newFilename);
                    if(empty($adImagesUrl)){
                        $this->redirect('上传失败,请联系管理员!', '', 3);
                        die();
                    }else{
                        $adSave['url_images'] = $adImagesUrl;
                    }
                }

                $this->adModel->saveAd($adId,$adSave);

                //操作日志记录
                $logAdd = $adSave;
                $logAdd['id'] = $adId;
                $this->oplog($logAdd);
                $this->redirect('', '/admin/adoperate/', 0);
            }
            $this->assign('adRe', $adRe);
        }
        $this->assign("adIntent", $this->configModel['ad_intent']);
        $this->getViewer()->needLayout(false);
        $this->render('adoperate_edit_intent');
    }

    public function openAction()
    {
        $adId = (int)$this->reqVar('id',0);
        if($adId>0){
            $this->adModel->openAd($adId);

            //操作日志记录
            $logAdd['id'] = $adId;
            $this->oplog($logAdd);
        }
        $this->redirect('', '/admin/adoperate/', 0);
    }

    public function shutAction()
    {
        $adId = (int)$this->reqVar('id',0);
        if($adId>0){
            $this->adModel->shutAd($adId);

            //操作日志记录
            $logAdd['id'] = $adId;
            $this->oplog($logAdd);
        }
        $this->redirect('', '/admin/adoperate/', 0);
    }

    private function uploadFile($filename,$newFilename)
    {
        if(empty($filename) || empty($newFilename)){
            return false;
        }

        $path="../data/ad/"; //上传路径
        if(!file_exists($path)){
            mkdir("$path", 0700);
        }
        //允许上传的文件格式
        $tp = array("image/gif","image/pjpeg","image/jpeg","image/jpg","image/png");
        if(!in_array($filename["type"],$tp)){
            return false;
        }

        $flag = 0;
        if($filename["name"] && !empty($newFilename)){
            $imgType = explode("/",$filename["type"]);
            if(in_array($imgType[1],array('pjpeg','jpeg','jpg'))){
                $imgType[1] = 'jpg';
            }
            $file2name = $newFilename.'.'.$imgType[1];
            $file2 = $path.$file2name;
            $flag = 1;
        }

        $result = false;
        if($flag)
            $result = move_uploaded_file($filename["tmp_name"],$file2);

        if($result){
            return _PHOTO_URL_.'/hbdata/ad/'.$file2name;
        }else{
            return false;
        }
    }

    public function ajaxadAction()
    {
        $adId = daddslashes($this->getVar('ad_id',''));
        if(!empty($adId)){
            $adRe = $this->adModel->getAd(array('ad_id'=>$adId));
            if(!$adRe){
                exit("1");
            }
        }
        exit("0");
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
