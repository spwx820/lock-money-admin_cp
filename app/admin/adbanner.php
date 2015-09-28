<?php
/**
 * 广告banner运营
 *
 * @category   Leb
 * @package    Leb_Action
 * @author     lihui
 * @version    $Id: adbanner.php 2014-12-30 14:58:00 lihui
 * @copyright (c) 2014 dianjoy.com
 * @license
 */
class adbannerController extends Application
{
    private $configModel;
    private $adbannerModel;
    private $channelSetModel;
    private $operateLogModel;

    public function  execute($plugins)
    {
        $this->configModel = C('global.php');
        $this->adbannerModel = $this->loadModel('Adbanner');
        $this->channelSetModel = $this->loadModel('Channel_set');
        $this->operateLogModel = $this->loadModel('Operate_log');
    }

    public function indexAction()
    {
        $page = (int)$this->reqVar('page',1);
        $actionOs = daddslashes(trim($this->reqVar('action_os','')));
        $actionStatus = daddslashes(trim($this->reqVar('action_status','')));
        $dateNow = date("Y-m-d H:i:s",time());

        $pageUrl = "/admin/adbanner/";
        if(is_numeric($actionOs)){
            $bannerSet['os_type'] = $actionOs;
            $pageUrl .= "&action_os=$actionOs";
        }

        if(is_numeric($actionStatus)){
            $bannerSet['st'] = $actionStatus;
            $pageUrl .= "&action_status=$actionStatus";
        }
        $bannerSet['orderby'] = " seq desc";
        $bannerList  = $this->adbannerModel->getAdBannerList($bannerSet,$page,60);
        if($bannerList){
            $i = ($page - 1) * 60 + 1;
            foreach($bannerList as $key=>$val){
                if($val['end_time'] >=  $dateNow){
                    $bannerList[$key]['isvalid'] = 1;
                }else{
                    $bannerList[$key]['isvalid'] = 0;
                }
                $bannerList[$key]['num'] = $i;
                $i++;
            }
        }
        $bannerCount = $this->adbannerModel->getAdBannerCount($bannerSet);
        $bannerPages = pages($bannerCount,$page,60,$pageUrl,$array = array());

        $this->assign('bannerList', $bannerList);
        $this->assign('bannerPages', $bannerPages);
        $this->assign("publicOs", $this->configModel['public_os']);
        $this->assign("publicStatus", $this->configModel['public_status']);
        $this->assign("actionOs", $actionOs);
        $this->assign("actionStatus", $actionStatus);
        $this->assign('page', $page);

        $this->getViewer()->needLayout(false);
        $this->render('adbanner_list');
    }

    public function addAction()
    {
        $dosubmit = daddslashes($this->postVar('dosubmit',''));
        $startDate = daddslashes($this->postVar('start_date',''));
        $endDate  = daddslashes($this->postVar('end_date',''));
        $channel = daddslashes(trim($this->postVar('channel','')));
        $dateNow = date("Y-m-d H:i:s",time());

        $bannerAdd['name'] = daddslashes(trim($this->postVar('banner_name','')));
        $bannerAdd['os_type'] = daddslashes(trim($this->postVar('os_type','')));
        $bannerAdd['channel_type'] = (int)$this->postVar('channel_type',0);
        $bannerAdd['open_type'] = (int)$this->postVar('open_type', 0);
        $bannerAdd['memo'] = daddslashes(trim($this->postVar('memo','')));
        $bannerAdd['ctime'] = $bannerAdd['utime'] = date("Y-m-d H:i:s",time());
        $bannerAdd['start_time'] = $startDate." 00:00:00";
        $bannerAdd['end_time'] = $endDate." 23:59:59";
        $bannerAdd['st'] = 0;
        $fileUpload = $_FILES['file_uplode'];
        if(!empty($dosubmit)){
            $clickUrl = daddslashes(trim($this->postVar('click_url','')));
            if(!empty($clickUrl) && strstr($clickUrl,'thread') && (strstr($clickUrl,_SITE_URL_) || strstr($clickUrl,'hongbaosuoping.com'))){
                $bannerAdd['click_url'] = _SITE_URL_.'/webview.php?referer='.urlencode($clickUrl);
            }elseif($clickUrl == _SITE_URL_ || $clickUrl == _SITE_URL_."/" || strstr($clickUrl,'hongbaosuoping.com')){
                $bannerAdd['click_url'] = _SITE_URL_.'/webview.php?referer='.urlencode($clickUrl);
            }elseif(!empty($clickUrl) && strstr($clickUrl,'baidu_wallet')){
                $bannerAdd['click_url'] = _SITE_URL_.'/client/validate_app.php?referer='.urlencode($clickUrl);
            }else{
                $bannerAdd['click_url'] = $clickUrl;
            }

            //渠道号判断
            $channelSuccCount = $channelFailCount = 0;
            $bannerAdd['channel'] = $channelFailStr = '';
            $callbackStr = "添加成功";
            if(!empty($channel)){
                $channelSucc = $channelFail = array();
                $channelExp = explode("|",$channel);
                foreach($channelExp as $key=>$val){
                    $channelSet['channel'] = $val;
                    $channelSet['status'] = 1;
                    if($this->channelSetModel->getChannelSet($channelSet)){
                        $channelSucc[] = $val;
                    }else{
                        $channelFail[] = $val;
                    }
                }

                $channelSuccCount = count($channelSucc);
                $channelFailCount = count($channelFail);

                if(!empty($channelSucc)){
                    $bannerAdd['channel'] = trim(implode("|",$channelSucc),"|");
                }elseif(!empty($channelFail)){
                    $channelFailStr = trim(implode("|",$channelFail),"|");
                }
                if(empty($channelSucc)){
                    $this->redirect('渠道号无效，请确认填写!', '', 3);
                    die();
                }

                //返回信息
                $callbackStr = "渠道号成功数$channelSuccCount,失败数$channelFailCount";
                if(!empty($channelFailStr)){
                    $callbackStr .= ",失败渠道号为：$channelFailStr";
                }
            }elseif(empty($bannerAdd['channel_type'])){
                $this->redirect('请填写渠道号!', '', 3);
                die();
            }

            list($width_img, $height_img) = getimagesize($fileUpload['tmp_name']);
            if(empty($bannerAdd['name'])){
                $this->redirect('请填写广告名称!', '', 3);
                die();
            }elseif(!isset($bannerAdd['os_type'])){
                $this->redirect('请选择操作系统!', '', 3);
                die();
            }elseif(!isset($bannerAdd['click_url'])){
                $this->redirect('来源地址不能为空!', '', 3);
                die();
            }elseif(empty($fileUpload['name'])){
                $this->redirect('请上传图片!', '', 3);
                die();
            }elseif($fileUpload['size'] > 204800){
                $this->redirect('图片大小不能超过200KB', '', 3);
                die();
            }elseif($width_img>1280 || $height_img>640){
                $this->redirect('图片尺寸超出限制', '', 3);
                die();
            }elseif(empty($bannerAdd['start_time']) || empty($bannerAdd['end_time'])){
                $this->redirect('上、下架时间不能为空!', '', 3);
                die();
            }elseif($bannerAdd['end_time'] < $bannerAdd['start_time'] || $dateNow > $bannerAdd['end_time']){
                $this->redirect('下架时间不能小于上架时间或当前时间!', '', 3);
                die();
            }elseif(empty($bannerAdd['memo'])){
                $this->redirect('banner说明不能为空!', '', 3);
                die();
            }else{
                $bannerAdd['seq'] = 1;
                $seqRe = $this->adbannerModel->query("SELECT max(seq) as num FROM z_ad_banner LIMIT 1");
                if($seqRe){
                    $seqNum = !empty($seqRe[0]['num'])?$seqRe[0]['num']:0;
                    $bannerAdd['seq'] = $seqNum + 1;
                }

                $bannerId = $this->adbannerModel->addAdBanner($bannerAdd);
                if(!empty($bannerId)){
                    $bannerSave['pic_url'] = $this->uploadFile($fileUpload,$bannerId);
                    if(!empty($bannerSave['pic_url'])){
                        $this->adbannerModel->saveAdBanner($bannerId,$bannerSave);

                        //操作日志记录
                        $this->oplog($bannerAdd);
                        $this->redirect($callbackStr, '/admin/adbanner/', 3);
                    }else{
                        $this->redirect('上传失败,请联系管理员!', '', 3);
                        die();
                    }
                }
            }
        }
        $this->assign("publicOs", $this->configModel['public_os']);
        $this->getViewer()->needLayout(false);
        $this->render('adbanner_add');
    }

    public function editAction()
    {
        $bannerId = (int)$this->reqVar('id',0);
        $dosubmit = daddslashes(trim($this->postVar('dosubmit','')));
        $channel = daddslashes(trim($this->postVar('channel','')));
        $startDate= daddslashes($this->postVar('start_date',''));
        $endDate  = daddslashes($this->postVar('end_date',''));
        $dateNow = date("Y-m-d H:i:s",time());
        $page = (int)$this->getVar('page',1);
        if($bannerId > 0){
            $bannerRe = $this->adbannerModel->getAdBanner(array('id'=>$bannerId));
            if($bannerRe && !empty($dosubmit)){
                $bannerSave['name'] = daddslashes(trim($this->postVar('banner_name','')));
                $bannerSave['os_type'] = daddslashes(trim($this->postVar('os_type','')));
                $bannerSave['channel_type'] = (int)$this->postVar('channel_type',0);
                $bannerSave['open_type'] = (int)$this->postVar('open_type', 0);
                $bannerSave['memo'] = daddslashes(trim($this->postVar('memo','')));
                $bannerSave['utime'] = date("Y-m-d H:i:s",time());

                $bannerSave['start_time'] = $startDate." 00:00:00";
                $bannerSave['end_time'] = $endDate." 23:59:59";
                $bannerSave['st'] = 0;

                $clickUrl = daddslashes(trim($this->postVar('click_url','')));
                if(!empty($clickUrl) && strstr($clickUrl,'thread') && (strstr($clickUrl,_SITE_URL_) || strstr($clickUrl,'hongbaosuoping.com'))){
                    $bannerSave['click_url'] = _SITE_URL_.'/webview.php?referer='.urlencode($clickUrl);
                }elseif($clickUrl == _SITE_URL_ || $clickUrl == _SITE_URL_."/" || strstr($clickUrl,'hongbaosuoping.com')){
                    $bannerSave['click_url'] = _SITE_URL_.'/webview.php?referer='.urlencode($clickUrl);
                }elseif(!empty($clickUrl) && strstr($clickUrl,'baidu_wallet')){
                    $bannerSave['click_url'] = _SITE_URL_.'/client/validate_app.php?referer='.urlencode($clickUrl);
                }else{
                    $bannerSave['click_url'] = $clickUrl;
                }

                //渠道号判断
                $channelSuccCount = $channelFailCount = 0;
                $bannerSave['channel'] = $channelFailStr = '';
                $callbackStr = "编辑成功";
                if(!empty($channel)){
                    $channelSucc = $channelFail = array();
                    $channelExp = explode("|",$channel);
                    foreach($channelExp as $key=>$val){
                        $channelSet['channel'] = $val;
                        $channelSet['status'] = 1;
                        if($this->channelSetModel->getChannelSet($channelSet)){
                            $channelSucc[] = $val;
                        }else{
                            $channelFail[] = $val;
                        }
                    }

                    $channelSuccCount = count($channelSucc);
                    $channelFailCount = count($channelFail);

                    if(!empty($channelSucc)){
                        $bannerSave['channel'] = trim(implode("|",$channelSucc),"|");
                    }elseif(!empty($channelFail)){
                        $channelFailStr = trim(implode("|",$channelFail),"|");
                    }
                    if(empty($channelSucc)){
                        $this->redirect('渠道号无效，请确认填写!', '', 3);
                        die();
                    }

                    //返回信息
                    $callbackStr = "渠道号成功数$channelSuccCount,失败数$channelFailCount";
                    if(!empty($channelFailStr)){
                        $callbackStr .= ",失败渠道号为：$channelFailStr";
                    }
                }elseif(empty($bannerSave['channel_type'])){
                    $this->redirect('请填写渠道号!', '', 3);
                    die();
                }

                if(empty($bannerSave['name'])){
                    $this->redirect('请填写广告名称!', '', 3);
                    die();
                }elseif(!isset($bannerSave['os_type'])){
                    $this->redirect('请选择操作系统!', '', 3);
                    die();
                }elseif(!isset($bannerSave['click_url'])){
                    $this->redirect('来源地址不能为空!', '', 3);
                    die();
                }elseif(empty($bannerSave['start_time']) || empty($bannerSave['end_time'])){
                    $this->redirect('上、下架时间不能为空!', '', 3);
                    die();
                }elseif($bannerSave['end_time'] < $bannerSave['start_time'] || $dateNow > $bannerSave['end_time']){
                    $this->redirect('下架时间不能小于上架时间或当前时间!', '', 3);
                    die();
                }elseif(empty($bannerSave['memo'])){
                    $this->redirect('banner说明不能为空!', '', 3);
                    die();
                }

                $fileUpload = $_FILES['file_uplode'];
                if(!empty($fileUpload['name'])){
                    list($width_img, $height_img) = getimagesize($fileUpload['tmp_name']);
                    if($fileUpload['size'] > 204800){
                        $this->redirect('图片大小不能超过200KB', '', 3);
                        die();
                    }elseif($width_img>1280 || $height_img>640){
                        $this->redirect('图片尺寸超出限制', '', 3);
                        die();
                    }

                    $bannerImagesUrl = $this->uploadFile($fileUpload,$bannerId);
                    if(empty($bannerImagesUrl)){
                        $this->redirect('上传失败,请联系管理员!', '', 3);
                        die();
                    }else{
                        $bannerSave['pic_url'] = $bannerImagesUrl;
                    }
                }
                $this->adbannerModel->saveAdBanner($bannerId,$bannerSave);

                //操作日志记录
                $logAdd = $bannerSave;
                $logAdd['id'] = $bannerId;
                $this->oplog($logAdd);
                $this->redirect($callbackStr, '/admin/adbanner/?page='.$page, 3);
            }

            $bannerRe['start_date'] = $bannerRe['end_date'] = '';
            if(!empty($bannerRe['start_time'])){
                $bannerRe['start_date'] = substr($bannerRe['start_time'],0,10);
            }
            if(!empty($bannerRe['end_time'])){
                $bannerRe['end_date'] = substr($bannerRe['end_time'],0,10);
            }

            if(!empty($bannerRe['click_url']) && strstr($bannerRe['click_url'],'referer') && strstr($bannerRe['click_url'],'thread')){
                $clickUrlArr = explode("referer=", $bannerRe['click_url']);
                $bannerRe['click_url'] = !empty($bannerRe['click_url']) ? urldecode($clickUrlArr[1]) : '';
            }
            $this->assign('bannerRe', $bannerRe);
        }

        $this->assign("publicOs", $this->configModel['public_os']);
        $this->getViewer()->needLayout(false);
        $this->render('adbanner_edit');
    }

    public function topAction()
    {
        $bannerId = (int)$this->reqVar('id',0);
        if($bannerId > 0){
            $seqNum = 0;
            $seqRe = $this->adbannerModel->query("SELECT max(seq) as num FROM z_ad_banner LIMIT 1");
            if($seqRe){
                $seqNum = !empty($seqRe[0]['num'])?$seqRe[0]['num']:0;
                $seqNum = $seqNum + 1;

                $bannerSave['seq'] = $seqNum;
                $bannerSave['utime'] = date("Y-m-d H:i:s",time());
                $this->adbannerModel->saveAdBanner($bannerId,$bannerSave);

                //清理redis缓存
                $this->clearRedis();

                //操作日志记录
                $logAdd['id'] = $bannerId;
                $this->oplog($logAdd);
            }
        }
        $this->redirect('', '/admin/adbanner/', 0);
    }

    public function openAction()
    {
        $adId = (int)$this->reqVar('id',0);
        $page = (int)$this->getVar('page',1);
        if($adId>0){
            $this->adbannerModel->openAdBanner($adId);

            //清理redis缓存
            $this->clearRedis();

            //操作日志记录
            $logAdd['id'] = $adId;
            $this->oplog($logAdd);
        }
        $this->redirect('', '/admin/adbanner/?page='.$page, 0);
    }

    public function shutAction()
    {
        $adId = (int)$this->reqVar('id',0);
        $page = (int)$this->getVar('page',1);
        if($adId>0){
            $this->adbannerModel->shutAdBanner($adId);

            //清理redis缓存
            $this->clearRedis();

            //操作日志记录
            $logAdd['id'] = $adId;
            $this->oplog($logAdd);
        }
        $this->redirect('', '/admin/adbanner/?page='.$page, 0);
    }

    private function uploadFile($filename,$tag)
    {
        if(empty($filename) && empty($tag)){
            return false;
        }

        $path="../data/adbanner/"; //上传路径
        if(!file_exists($path)){
            mkdir("$path", 0700);
        }
        //允许上传的文件格式
        $tp = array("image/pjpeg","image/jpeg","image/jpg","image/png");
        if(!in_array($filename["type"],$tp)){
            return false;
        }

        $flag = 0;
        if($filename["name"] && !empty($tag)){
            $imgType = explode("/",$filename["type"]);
            if(in_array($imgType[1],array('pjpeg','jpeg','jpg'))){
                $imgType[1] = 'jpg';
            }
//          $random =rand(10000,99999);
//          $file2name = strtoupper(md5($path.date("YmdHis", time()).$random.$tag)).'.'.$imgType[1]; //随机文件名
            $file2name = md5_file($filename["tmp_name"]).'.'.$imgType[1];
            $file2 = $path.$file2name;
            $flag = 1;
        }
        $result = false;
        if($flag)
            $result = move_uploaded_file($filename["tmp_name"],$file2);


        if($result){
            return _PHOTO_URL_.'/hbdata/adbanner/'.$file2name;
        }else{
            return false;
        }
    }

    private function clearRedis()
    {
        //清理redis缓存
        $redis = Leb_Dao_Redis::getInstance();

        $bannerAndroidKey  = '_ZHUAN_AD_BANNER_android';
        $bannerAndroid = $redis->get($bannerAndroidKey);
        if($bannerAndroid)
            $redis->del($bannerAndroidKey);

        $bannerIosKey  = '_ZHUAN_AD_BANNER_ios';
        $bannerIos = $redis->get($bannerIosKey);
        if($bannerIos)
            $redis->del($bannerIosKey);

        $bannerAndroidDatKey  = '_ZHUAN_AD_BANNER_android_dat';
        $bannerAndroidDat = $redis->get($bannerAndroidDatKey);
        if($bannerAndroidDat)
            $redis->del($bannerAndroidDatKey);

        $bannerIosDatKey  = '_ZHUAN_AD_BANNER_ios_dat';
        $bannerIosDat = $redis->get($bannerIosDatKey);
        if($bannerIosDat)
            $redis->del($bannerIosDatKey);
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
