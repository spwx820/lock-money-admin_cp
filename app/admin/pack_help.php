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
class pack_helpController extends Application
{
    private $versionModel;
    private $packageUpModel;

    public function execute($plugins)
    {
    }

    public function indexAction()
    {
        $os_type = daddslashes($this->reqVar('os_type', ''));

        if($os_type == 'android')
        {
            $android = "on";
            $ios = "";
        }
        else{
            $android = "";
            $ios = "on";
        }

        $this->assign('os_type', $os_type);
        $this->assign('android', $android);
        $this->assign('ios', $ios);


        $this->getViewer()->needLayout(false);
        $this->render('pack_help');
    }

}