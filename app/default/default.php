<?php
/**
 * 过期默认显示
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
    }

    public function indexAction()
    {
        $this->redirect('', '/default/login/', 0);
    }

}