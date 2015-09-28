<?php
/**
 * 后台管理员操作记录
 *
 * @category   Leb
 * @package    Leb_Action
 * @author     lihui
 * @version    $Id: manage.php 2015-01-19 18:20:00 lihui
 * @copyright (c) 2015 dianjoy.com
 * @license
 */
class operateController extends Application
{
    private $operateModel;

    public function execute($plugins)
    {
        $this->operateModel = $this->loadModel('Operate_log');
    }

    public function indexAction()
    {
        $keyword = daddslashes($this->reqVar('keyword', ''));
        $startTime = daddslashes($this->reqVar('start_time', ''));
        $endTime = daddslashes($this->reqVar('end_time', ''));
        $page = (int)$this->reqVar('page', 1);

        $pageUrl = "/admin/operate/";
        if (!empty($keyword))
        {
            $opertaeSet['operat'] = $keyword;
            $pageUrl .= "?keyword=$keyword";
        }

        if (!empty($startTime))
        {
            $opertaeSet['start_time'] = $startTime;
            $pageUrl .= !empty($keyword) ? "&" : "?";
            $pageUrl .= "&start_time=$startTime";
        }
        if (!empty($endTime))
        {
            $opertaeSet['end_time'] = $endTime;
            if (!empty($keyword) || !empty($startTime))
            {
                $pageUrl .= "&end_time=$endTime";
            } else
            {
                $pageUrl .= "?end_time=$endTime";
            }
        }
        $operateList = $this->operateModel->getOpLogList($opertaeSet, $page, 20);
        if ($operateList)
        {
            foreach ($operateList as $key => $val)
            {
                $contentStr = '';
                $content = json_decode($val['content'], true);
//                die();
                if (is_array($content))
                {
                    foreach ($content as $ckey => $cval)
                    {
                        $contentStr .= $ckey . "/" . $cval . ",";
                    }
                    $contentStr = trim($contentStr, ",");
                    $contentSub = cn_substr($contentStr, 20);
                    $operateList[$key]['content'] = $contentStr;
                    $operateList[$key]['content_sub'] = $contentSub;
                } else
                {
                    $operateList[$key]['content'] = $operateList[$key]['content_sub'] = $content;
                }
//                $operateList[$key]['content_sub'] = $val['content'];
            }
        }
        $operateCount = $this->operateModel->getOpLogCount($opertaeSet);
        $operatePages = pages($operateCount, $page, 20, $pageUrl, array());

        $this->assign('keyword', $keyword);
        $this->assign('startTime', $startTime);
        $this->assign('endTime', $endTime);
        $this->assign('operateList', $operateList);
        $this->assign('operatePages', $operatePages);
        $this->assign("page", $page);

        $this->getViewer()->needLayout(false);
        $this->render('operate_list');
    }

}