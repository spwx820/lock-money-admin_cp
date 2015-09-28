<?php
/**
 * 错误码统计管理
 *
 * @category   Leb
 * @package    Leb_Action
 * @author     lihui
 * @version    $Id: errorcode.php 2014-10-17 9:58:00 lihui
 * @copyright (c) 2014 dianjoy.com
 * @license
 */
class errorcodeController extends Application
{
    private $errorcodeCountModel;
    private $errorcodeLogModel;
    private $errorcodeTypeModel;

    public function  execute($plugins)
    {
        $this->errorcodeCountModel = $this->loadAppModel('Errorcode_count');
        $this->errorcodeLogModel  = $this->loadAppModel('Errorcode_log');
        $this->errorcodeTypeModel = $this->loadAppModel('Errorcode_type');
    }

    public function indexAction()
    {
        $page = (int)$this->reqVar('page',1);

        $errorCodeSet = array();
        $errorCodeList = $this->errorcodeCountModel->getErrorcodeList($errorCodeSet,$page,50);
        if($errorCodeList){
            foreach($errorCodeList as $key=>$val){
                $errorCodeList[$key]['last_num_ratio'] = sprintf ( "%01.2f" ,($val['today_num'] - $val['last_num'])/$val['today_num']*100);
                $errorCodeList[$key]['last_hour_ratio'] = sprintf ( "%01.2f" ,$val['last_hour_avg_num']/$val['today_num']*100);
            }
        }

        $errorCodeeCount = $this->errorcodeCountModel->getErrorcodeCount($errorCodeSet);
        $errorCodePages = pages($errorCodeeCount,$page,50,'',$array = array());

        $this->assign('errorCodeList', $errorCodeList);
        $this->assign('errorCodePages', $errorCodePages);

        $this->getViewer()->needLayout(false);
        $this->render('errorcode_list');
    }

    public function typeAction()
    {
        $errorcode = (int)$this->reqVar('errorcode',0);
        $page = (int)$this->reqVar('page',1);
        if($errorcode){
            $errorCodeTypeSet['errorcode'] = $errorcode;
            $errorCodeTypeList = $this->errorcodeTypeModel->getErrorCodeTypeList($errorCodeTypeSet,$page,50);
            $errorCodeTypeCount =$this->errorcodeTypeModel->getErrorCodeTypeCount($errorCodeTypeSet);
            $errorCodeTypePages = pages($errorCodeTypeCount,$page,50,'',$array = array());

            $this->assign('errorCodeTypeList', $errorCodeTypeList);
            $this->assign('errorCodeTypePages', $errorCodeTypePages);
        }
        $this->assign('errorcode', $errorcode);
        $this->getViewer()->needLayout(false);
        $this->render('errorcode_type');
    }

    public function detailAction()
    {
        $errorcode = (int)$this->reqVar('errorcode',0);
        $page = (int)$this->reqVar('page',1);
        if($errorcode){
            $errorCodeLogSet['errorcode'] = $errorcode;
            $errorCodeLogList = $this->errorcodeLogModel->getErrorcodeLogList($errorCodeLogSet,$page,50);
            $errorCodeLogCount = $this->errorcodeLogModel->getErrorcodeLogCount($errorCodeLogSet);
            $errorCodeLogPages = pages($errorCodeLogCount,$page,50,'',$array = array());

            $this->assign('errorCodeLogList', $errorCodeLogList);
            $this->assign('errorCodeLogPages', $errorCodeLogPages);
        }
        $this->assign('errorcode', $errorcode);
        $this->getViewer()->needLayout(false);
        $this->render('errorcode');
    }

}
