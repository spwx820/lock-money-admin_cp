<?php
/**
 * 默认操作
 *
 * @category   Leb
 * @package    Leb_Action
 * @author     lihui
 * @version    $Id: default.php 2014-09-03 9:58:00 lihui
 * @copyright (c) 2014 dianjoy.com
 * @license
 */
class defaultController extends Application
{
    public function execute($plugins)
    {
        $header = get_page_header();
        $this->assign('header', $header);
        $side_bar_menu = $this->get_side_bar_menu();
        $this->assign('side_bar_menu', $side_bar_menu);
    }

    public function indexAction()
    {
        $m = C('global.php');

        $num = 3000;
        $curr_page = 10;
        $pages = pages_new($num, $curr_page, $perpage = 100, $url='/admin/index');
        $this->assign('pages', $pages);

        $date_range = get_date_range_picker("date_range");
        $this->assign('date_range', $date_range);

        $pay_type_list = get_select("pay_type", "兑换类型", $m['pay_type']);
        $this->assign('pay_type_list', $pay_type_list);

        $exchange_select = get_select("exchange_select", "类型", $m['exchange_select'], 100);
        $this->assign('exchange_select', $exchange_select);

        $this->getViewer()->needLayout(false);
        $this->render('default');
    }

    public function expireAction()
    {
        die("已过期,请重新登录!");
    }

}