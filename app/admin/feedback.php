<?php
/**
 * 后台反馈管理
 *
 * @category   Leb
 * @package    Leb_Action
 * @author     lihui
 * @version    $Id: feedback.php 2014-09-30 10:58:00 lihui
 * @copyright (c) 2014 dianjoy.com
 * @license
 */
class feedbackController extends Application
{
    private $feedbackModel;

    public function  execute($plugins)
    {
        $this->feedbackModel = $this->loadModel('Feedback');
    }

    public function indexAction()
    {
        $page = (int)$this->reqVar('page',1);

        $feedbackSet = array();
        $feedbackList = $this->feedbackModel->getFeedbackList($feedbackSet,$page,20);
        $feedbackCount = $this->feedbackModel->getFeedbackCount($feedbackSet);
        $feedbackPages = pages($feedbackCount,$page,20,'',$array = array());

        $this->assign('feedbackList', $feedbackList);
        $this->assign('feedbackPages', $feedbackPages);

        $this->getViewer()->needLayout(false);
        $this->render('feedback_list');
    }

}