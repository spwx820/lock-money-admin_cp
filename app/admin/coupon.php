<?php
/**
 * 优惠券coupon运营
 *
 * @category   Leb
 * @package    Leb_Action
 * @author     lihui
 * @version    $Id: coupon.php 2014-12-30 14:58:00 lihui
 * @copyright (c) 2014 dianjoy.com
 * @license
 */
class couponController extends Application
{
    private $coupon_model;
    private $coupon_data_model;
    private $list_from_file;
    private $userModel;



    public function  execute($plugins)
    {
        $this->coupon_model = $this->loadModel('coupon');
        $this->coupon_data_model = $this->loadModel('coupon_data');
        $this->userModel = $this->loadAppModel('User');
    }

    public function cpa_indexAction()
    {
        $page_header = $this->get_page_header(2);

        $page = (int)$this->reqVar('page', 1);
        $coupon_list_page = array();

        $_ist = $this->userModel->query("SELECT * FROM z_gamemsg_config WHERE status >= 0 ORDER BY id DESC;");
        $num = 0;
        $page_num = 60 * $page - 60;
        $page_limit = 60 * $page;

        if ($_ist)
        {
            foreach ($_ist as $val)
            {
                $num += 1;
                if ($num > $page_num && $num < $page_limit) // 获取有效广告优惠券
                {
                    $val['is_password'] = 'NA';
                    $coupon_num = intval($this->userModel->query("SELECT COUNT(*) FROM z_gamemsg_data WHERE config_id = '{$val['id']}' AND (uid = '' );")[0]['COUNT(*)']);
                    $val['coupon_num'] = $coupon_num;
                    $val['name'] = $val['ad_id'];
                    $val['os_type'] = 'iOS/Android';

                    $coupon_list_page[] = $val;
                }
            }
        }

        $pageUrl = "/admin/coupon/cpa_index";
        $coupon_count = $this->userModel->query("SELECT COUNT(*) FROM z_coupon_config ORDER BY id DESC;")[0]['COUNT(*)'];
        $couponPages = pages(intval($coupon_count), $page, 60, $pageUrl, $array = array());
        $this->assign('coupon_pages', $couponPages);
        $this->assign('coupon_list_page', $coupon_list_page);
        $this->assign('page', $page);
        $this->assign('page_header', $page_header);
        $this->assign('list_type', 2);
        $this->assign('adminId', UID);

        $this->getViewer()->needLayout(false);
        $this->render('coupon_list');
    }

    public function get_page_header($num)
    {
        $key = [];
        for ($ii = 1; $ii <= 6; $ii ++ )
        {
            $key[$ii] = 'off';
            if($ii == $num)
                $key[$ii] = 'on';
        }
        return '<div class="subnav">
    <h2 class="title-1 line-x f14 fb blue lh28">优惠券</h2>
    <div class="content-menu ib-a blue line-x">
        <a href="/admin/coupon/activity_index" class="'. $key[1] . '"> <em>活动优惠券管理</em> </a>
        <span>|</span>
        <a href="/admin/coupon/cpa_index" class="'. $key[2] . '"> <em>广告优惠券管理</em> </a>
        <span>|</span>
        <a href="/admin/coupon/user_index" class="'. $key[3] . '"> <em>用户优惠券管理</em> </a>
        <span>|</span>
        <a href="/admin/coupon/activity_add" class="'. $key[4] . '" > <em>活动优惠券添加</em> </a>
        <span>|</span>
        <a href="/admin/coupon/cpa_add" class="'. $key[5] . '" > <em>广告优惠券添加</em> </a>
        <span>|</span>
        <a href="/admin/coupon/user_add" class="'. $key[6] . '" > <em>用户优惠券添加</em> </a>
    </div>
</div>';
    }

    public function activity_indexAction()
    {
        $page_header = $this->get_page_header(1);

        $page = (int)$this->reqVar('page', 1);
        $dateNow = date("Y-m-d H:i:s", time());
        $_list = $this->userModel->query("SELECT * FROM z_coupon_config WHERE status >= 0 ORDER BY id DESC;");
        $num = 0;
        $page_num = 60 * $page - 60;
        $page_limit = 60 * $page;
        $coupon_list_page = array();

        if ($_list)
        {
            foreach ($_list as $val)
            {
                $num += 1;
                if ($num > $page_num && $num < $page_limit) // 获取有效活动优惠券
                {
                    $is_password = !empty($this->userModel->query("SELECT pwd FROM z_coupon_data WHERE coupon_id = '{$val['id']}' LIMIT 1;")[0]['pwd']) ? '是': '否';
                    $num_c_u = intval($this->userModel->query("SELECT COUNT(*) FROM z_coupon_data WHERE coupon_id = '{$val['id']}' AND (pnum != '' OR uid != '' );")[0]['COUNT(*)']);
                    $num_c_u = !empty($num_c_u) ? $num_c_u : 0;
                    $coupon_num = intval($val['code_amt']) - $num_c_u;
                    $coupon_num = $coupon_num == -1 ? 0 : $coupon_num;
                    $val['is_password'] = $is_password;
                    $val['coupon_num'] = $coupon_num;
                    $val['os_type'] = 'iOS/Android';
                    $coupon_list_page[] = $val;
                }
            }
        }

        $pageUrl = "/admin/coupon/activity_index";
        $coupon_count = $this->userModel->query("SELECT COUNT(*) FROM z_coupon_config ORDER BY id DESC;")[0]['COUNT(*)'];
        $couponPages = pages(intval($coupon_count), $page, 60, $pageUrl, $array = array());
        $this->assign('coupon_pages', $couponPages);
        $this->assign('coupon_list_page', $coupon_list_page);
        $this->assign('page', $page);
        $this->assign('page_header', $page_header);
        $this->assign('list_type', 1);
        $this->assign('adminId', UID);

        $this->getViewer()->needLayout(false);
        $this->render('coupon_list');
    }


    public function user_indexAction()
    {
        $page_header = $this->get_page_header(3);

        $page = (int)$this->reqVar('page', 1);
        $dateNow = date("Y-m-d H:i:s", time());
        $_list = $this->userModel->query("SELECT * FROM z_user_coupon_config WHERE status >= 0 ORDER BY id DESC;");
        $num = 0;
        $page_num = 60 * $page - 60;
        $page_limit = 60 * $page;
        $coupon_list_page = array();

        if ($_list)
        {
            foreach ($_list as $val)
            {
                $num += 1;
                if ($num > $page_num && $num < $page_limit ) // 获取有效活动优惠券
                {
                    $is_password = !empty($this->userModel->query("SELECT pwd FROM z_user_coupon_data WHERE coupon_id = '{$val['id']}' LIMIT 1;")[0]['pwd']) ? '是': '否';
                    $num_c_u = intval($this->userModel->query("SELECT COUNT(*) FROM z_user_coupon_data WHERE coupon_id = '{$val['id']}' AND (pnum != '' OR uid != '' );")[0]['COUNT(*)']);
                    $num_c_u = !empty($num_c_u) ? $num_c_u : 0;
                    $coupon_num = intval($val['code_amt']) - $num_c_u;
                    $num_ready = intval($this->userModel->query("SELECT COUNT(*) FROM z_user_coupon_data WHERE coupon_id = '{$val['id']}' AND (pnum != '' OR uid != '' ) AND is_send = 0;")[0]['COUNT(*)']);

                    $val['is_password'] = $is_password;
                    $val['coupon_num'] = $coupon_num;
                    $val['num_ready'] = $num_ready;

                    $coupon_list_page[] = $val;
                }
            }
        }

        $pageUrl = "/admin/coupon/user_index";
        $coupon_count = $this->userModel->query("SELECT COUNT(*) FROM z_coupon_config ORDER BY id DESC;")[0]['COUNT(*)'];
        $couponPages = pages(intval($coupon_count), $page, 60, $pageUrl, $array = array());
        $this->assign('coupon_pages', $couponPages);
        $this->assign('coupon_list_page', $coupon_list_page);
        $this->assign('page', $page);
        $this->assign('page_header', $page_header);
        $this->assign('list_type', 3);
        $this->assign('adminId', UID);

        $this->getViewer()->needLayout(false);
        $this->render('coupon_list');
    }


    public function activity_addAction()
    {
        $page_header = $this->get_page_header(4);
        $this->assign('page_header', $page_header);

        $dosubmit = daddslashes($this->postVar('dosubmit', ''));
        $dateNow = date("Y-m-d H:i:s", time());
        $couponAdd['name'] = daddslashes(trim($this->postVar('coupon_name', '')));

        $couponAdd['day_limit_pnum'] = daddslashes(trim($this->postVar('day_limit_pnum', 0)));
        $couponAdd['all_limit_pnum'] = daddslashes(trim($this->postVar('all_limit_pnum', 0)));
        $couponAdd['day_limit_uid'] = daddslashes(trim($this->postVar('day_limit_uid', 0)));
        $couponAdd['all_limit_uid'] = daddslashes(trim($this->postVar('all_limit_uid', 0)));

        $couponAdd['msg_title'] = daddslashes(trim($this->postVar('msg_title', '')));
        $couponAdd['msg_content'] = daddslashes(trim($this->postVar('msg_content', '')));

        $couponAdd['ctime'] = $couponAdd['utime'] = date("Y-m-d H:i:s", time());
        $starttime = daddslashes($this->postVar('start_time',''));
        $endtime  = daddslashes($this->postVar('end_time',''));
        $couponAdd['start_time'] = $starttime;
        $couponAdd['end_time'] = $endtime;

        $fileUpload = $_FILES['file_uplode'];
        if (!empty($dosubmit))
        {
            if (empty($couponAdd['name']))
            {
//                $this->redirect('请填写优惠券名称!', '', 3);
                echo "<script type='text/javascript'>alert('请填写优惠券名称！');</script>";

//                die();
            } elseif (empty($fileUpload['name']))
            {
                echo "<script type='text/javascript'>alert('请上传优惠券文件！');</script>";

//                $this->redirect('请上传优惠券文件!', '', 3);
//                die();
            } elseif ($fileUpload['size'] > 2048000)
            {
                echo "<script type='text/javascript'>alert('优惠券文件大小不能超过2000KB！');</script>";

//                $this->redirect('优惠券文件大小不能超过200KB', '', 3);
//                die();
            } else
            {
                if (!empty($couponAdd['name']))
                {
                    $file_url = $this->uploadFile($fileUpload);
                    if (!empty($file_url))
                    {
                        $num = count($this->list_from_file)/2;
                        $is_password = !empty($this->list_from_file[1]);

                        $sql = "INSERT INTO z_coupon_config (name, start_time, end_time, code_amt, day_limit_pnum, all_limit_pnum,
                                                  day_limit_uid, all_limit_uid, msg_title, msg_content, share_msg, info_notify, atime, status, z_status, o_status )
                                                  VALUES('{$couponAdd['name']}', '{$couponAdd['start_time']}', '{$couponAdd['end_time']}', $num, {$couponAdd['day_limit_pnum'] }, {$couponAdd['all_limit_pnum']},
                                                  {$couponAdd['day_limit_uid'] }, {$couponAdd['all_limit_uid']}, '{$couponAdd['msg_title']}', '{$couponAdd['msg_content']}',
                                                  '', 0, '{$couponAdd['ctime']}', 0, 1, 1)
                                                    ";
                        $this->userModel->execute($sql);

                        $coupon_id = intval($this->userModel->query("SELECT id FROM z_coupon_config ORDER BY id DESC LIMIT 1;")[0]['id']);
                        $this->userModel->execute("UPDATE z_coupon_config SET activity_id = $coupon_id WHERE id = $coupon_id;");

                        $sql = "SELECT code FROM  z_coupon_data WHERE coupon_id = $coupon_id";  // 去重
                        $coupon_list_c = $this->userModel->query($sql);
                        $coupon_list = [];
                        foreach ($coupon_list_c as $var)
                        {
                            $coupon_list[] = $var['code'];
                        }
                        $coupon_list = array_diff($this->list_from_file, $coupon_list);

                        foreach ($coupon_list as $key => $var)
                        {
                            $sql = "INSERT INTO z_coupon_data (coupon_id, code, pwd, ctime)
                                                  VALUES($coupon_id, '$var', '$key', '{$couponAdd['ctime']}');
                                                    ";
                            $this->userModel->execute($sql);
                        }

                        $num = count($coupon_list);
                        $this->redirect("添加{$num}张券码成功", '/admin/coupon/activity_index', 1);
                    } else
                    {
                        $this->redirect('上传失败,请联系管理员!', '/admin/coupon/activity_index', 3);
                        die();
                    }
                }
            }
        }
        $this->assign("couponAdd", $couponAdd);
        $this->getViewer()->needLayout(false);
        $this->render('coupon_activity_add');
    }



    public function user_addAction()
    {
        $page_header = $this->get_page_header(6);
        $this->assign('page_header', $page_header);

        $dosubmit = daddslashes($this->postVar('dosubmit', ''));
        $dateNow = date("Y-m-d H:i:s", time());
        $couponAdd['name'] = daddslashes(trim($this->postVar('coupon_name', '')));

        $couponAdd['msg_title'] = daddslashes(trim($this->postVar('msg_title', '')));
        $couponAdd['msg_content'] = daddslashes(trim($this->postVar('msg_content', '')));
        $couponAdd['os_type'] = daddslashes(trim($this->postVar('os_type', 'iOS')));

        $couponAdd['ctime'] = $couponAdd['utime'] = date("Y-m-d H:i:s", time());
        $starttime = daddslashes($this->postVar('start_time',''));
        $endtime  = daddslashes($this->postVar('end_time',''));
        $couponAdd['start_time'] = $starttime;
        $couponAdd['end_time'] = $endtime;

        $fileUpload = $_FILES['file_uplode'];
        if (!empty($dosubmit))
        {
            if (empty($couponAdd['name']))
            {
//                $this->redirect('请填写优惠券名称!', '', 3);
                echo "<script type='text/javascript'>alert('请填写优惠券名称！');</script>";
//                die();
            } elseif (empty($fileUpload['name']))
            {
                echo "<script type='text/javascript'>alert('请上传优惠券文件！');</script>";

//                $this->redirect('请上传优惠券文件!', '', 3);
//                die();
            } elseif ($fileUpload['size'] > 2048000)
            {
                echo "<script type='text/javascript'>alert('优惠券文件大小不能超过2000KB！');</script>";

//                $this->redirect('优惠券文件大小不能超过200KB', '', 3);
//                die();
            } else
            {
                if (!empty($couponAdd['name']))
                {
                    $file_url = $this->uploadFile($fileUpload);
                    if (!empty($file_url))
                    {
                        $num = count($this->list_from_file) / 2;
                        $is_password = !empty($this->list_from_file[1]);

                        $sql = "INSERT INTO z_user_coupon_config ( name, start_time, end_time, code_amt, msg_title, msg_content, atime, status, o_status, os_type)
                                                  VALUES('{$couponAdd['name']}', '{$couponAdd['start_time']}', '{$couponAdd['end_time']}', $num,
                                                   '{$couponAdd['msg_title']}', '{$couponAdd['msg_content']}',
                                                  '{$couponAdd['ctime']}', 0, 1, '{$couponAdd['os_type']}')
                                                    ";
                        $this->userModel->execute($sql);

                        $coupon_id = intval($this->userModel->query("SELECT id FROM z_user_coupon_config ORDER BY id DESC LIMIT 1;")[0]['id']);
                        $ii = 0;

                        $sql = "SELECT code FROM  z_user_coupon_data WHERE coupon_id = $coupon_id";  // 去重
                        $coupon_list_c = $this->userModel->query($sql);
                        $coupon_list = [];
                        foreach ($coupon_list_c as $var)
                        {
                            $coupon_list[] = $var['code'];
                        }
                        $coupon_list = array_diff($this->list_from_file, $coupon_list);

                        foreach ($coupon_list as $key => $var)
                        {
                            $sql = "INSERT INTO z_user_coupon_data (coupon_id, code, pwd, ctime)
                                                  VALUES($coupon_id, '$var', '$key', '{$couponAdd['ctime']}');
                                                    ";
                            $this->userModel->execute($sql);
                        }
                        $num = count($coupon_list);
                        $this->redirect("添加{$num}张券码成功", '/admin/coupon/user_index', 1);
                    } else
                    {
                        $this->redirect('上传失败,请联系管理员!', '/admin/coupon/user_index', 3);
                        die();
                    }
                }
            }
        }
        $this->assign("couponAdd", $couponAdd);
        $this->getViewer()->needLayout(false);
        $this->render('coupon_user_add');
    }


    public function cpa_addAction()
    {
        $page_header = $this->get_page_header(5);
        $this->assign('page_header', $page_header);

        $dosubmit = daddslashes($this->postVar('dosubmit', ''));
        $couponAdd['ad_id'] = daddslashes(trim($this->postVar('ad_id', '')));
        $couponAdd['msg_title'] = daddslashes(trim($this->postVar('msg_title', '')));
        $couponAdd['msg_content'] = daddslashes(trim($this->postVar('msg_content', '')));
        $couponAdd['ctime'] = $couponAdd['utime'] = date("Y-m-d H:i:s", time());
        $couponAdd['share_msg'] = daddslashes(trim($this->postVar('share_msg', '')));

        $couponAdd['code_amt'] = intval(daddslashes(trim($this->postVar('code_amt', 0))));
        $couponAdd['msg_type'] = (int)$this->postVar('msg_type', 0);
        $couponAdd['info_notify'] = (int)$this->postVar('info_notify', 0);
        $fileUpload = $_FILES['file_uplode'];

        if (!empty($dosubmit))
        {
            if (empty($couponAdd['ad_id']))
            {
                echo "<script type='text/javascript'>alert('请填写广告优惠券名称！');</script>";
            } elseif (!is_numeric($couponAdd['code_amt']))
            {
                echo "<script type='text/javascript'>alert('请填写正确的优惠券起始数量！');</script>";
            } else
            {
                if (!empty($couponAdd['ad_id']))
                {
                    if($couponAdd['msg_type'] == 1)
                        $file_url = $this->uploadFile($fileUpload);
                    else
                        $file_url = "固定格式";

                    if (!empty($file_url))
                    {
                        $num = count($this->list_from_file);

                        $sql = "INSERT INTO z_gamemsg_config (ad_id, msg_title, msg_type, msg_content, code_amt, share_msg, info_notify, atime, status, z_status, o_status )
                                                  VALUES('{$couponAdd['ad_id']}', '{$couponAdd['msg_title']}', '{$couponAdd['msg_type']}', '{$couponAdd['msg_content']}', '$num', '{$couponAdd['share_msg']}',
                                                  '{$couponAdd['info_notify']}', '{$couponAdd['ctime']}', 0, 1, 1)
                                                    ";  // status 记录状态 1 禁用 0 启用, z_status 墙状态 0 禁用 1 启用 , o_status 自身状态 0 禁用(游戏码耗尽) 1 启用

                        $this->userModel->execute($sql);

                        if ($couponAdd['msg_type'] == 1)
                        {
                            $coupon_id = intval($this->userModel->query("SELECT id FROM z_gamemsg_config ORDER BY id DESC LIMIT 1;")[0]['id']);

                            $sql = "SELECT code FROM  z_gamemsg_data WHERE config_id = $coupon_id";  // 去重
                            $coupon_list_c = $this->userModel->query($sql);
                            $coupon_list = [];
                            foreach ($coupon_list_c as $var)
                            {
                                $coupon_list[] = $var['code'];
                            }
                            $coupon_list = array_diff($this->list_from_file, $coupon_list);

                            foreach ($coupon_list as $key => $var)
                            {
                                $sql = "INSERT INTO z_gamemsg_data (config_id, code, ctime)
                                                  VALUES($coupon_id, '$var', '{$couponAdd['ctime']}');
                                                    ";
                                $this->userModel->execute($sql);
                            }
                            $num = count($coupon_list);
                        }
                        else{
                            $num = 0;
                        }

                        $this->redirect("添加{$num}张券码成功", '/admin/coupon/cpa_index', 3);
                    }else
                    {
                        $this->redirect('上传失败,请联系管理员!', '/admin/coupon/cpa_index', 3);
                        die();
                    }
                }
            }
        }
        $this->assign("couponAdd", $couponAdd);
        $this->getViewer()->needLayout(false);
        $this->render('coupon_cpa_add');
    }



    public function coupon_code_addAction()
    {
        $coupon_id = (int)$this->reqVar('id', -1);
        $list_type = (int)$this->reqVar('list_type', -1);

        $page_header = $this->get_page_header($list_type);
        $this->assign('page_header', $page_header);
        $do_submit = daddslashes(trim($this->postVar('dosubmit', '')));
        $ctime = date("Y-m-d H:i:s", time());
        $fileUpload = $_FILES['file_uplode'];

        if($list_type == 1)
            $coupon_res = $this->userModel->query("SELECT * FROM z_coupon_config WHERE id = $coupon_id;")[0];
        else if($list_type == 2)
            $coupon_res = $this->userModel->query("SELECT * FROM z_gamemsg_config WHERE id = $coupon_id;")[0];
        else if($list_type == 3)
            $coupon_res = $this->userModel->query("SELECT * FROM z_user_coupon_config WHERE id = $coupon_id;")[0];

        if ($coupon_id >= 0)
        {
            if ($coupon_res && !empty($do_submit))
            {
                if (empty($fileUpload['name']))
                {
                    $this->redirect('请上传优惠券文件!', '', 3);
                    die();
                } elseif ($fileUpload['size'] > 2048000)
                {
                    $this->redirect('优惠券文件大小不能超过2000KB', '', 3);
                    die();
                } else
                {
                    $file_url = $this->uploadFile($fileUpload);
                    if (!empty($file_url))
                    {
                        $ii = 0;
                        if ($list_type == 1)
                        {
                            $sql = "SELECT code FROM  z_coupon_data WHERE coupon_id = $coupon_id";
                            $coupon_list_c = $this->userModel->query($sql);
                            $coupon_list = [];
                            foreach ($coupon_list_c as $var)
                            {
                                $coupon_list[] = $var['code'];
                            }
                            $coupon_list = array_diff($this->list_from_file, $coupon_list);
                        } else if ($list_type == 2)
                        {
                            $sql = "SELECT code FROM  z_gamemsg_data WHERE config_id = $coupon_id";
                            $coupon_list_c = $this->userModel->query($sql);
                            $coupon_list = [];
                            foreach ($coupon_list_c as $var)
                            {
                                $coupon_list[] = $var['code'];
                            }
                            $coupon_list = array_diff($this->list_from_file, $coupon_list);
                        } else if ($list_type == 3)
                        {
                            $sql = "SELECT code FROM  z_user_coupon_data WHERE coupon_id = $coupon_id";
                            $coupon_list_c = $this->userModel->query($sql);
                            $coupon_list = [];
                            foreach ($coupon_list_c as $var)
                            {
                                $coupon_list[] = $var['code'];
                            }
                            $coupon_list = array_diff($this->list_from_file, $coupon_list);
                        }
                        foreach ($coupon_list as $key => $var)
                        {
                                if ($list_type == 1)
                                    $sql = "INSERT INTO z_coupon_data (coupon_id, code, pwd, ctime)
                                                  VALUES($coupon_id, '$var', '$key', '{$ctime}');";
                                else if ($list_type == 2)
                                    $sql = "INSERT INTO z_gamemsg_data (config_id, code, ctime)
                                                  VALUES($coupon_id, '$var', '{$ctime}');";
                                else if ($list_type == 3)
                                    $sql = "INSERT INTO z_user_coupon_data (coupon_id, code, pwd, ctime)
                                                  VALUES($coupon_id, '$var', '$key', '{$ctime}');";

                                $this->userModel->execute($sql);
                            $ii += 1;
                        }
                        $num_n = count($coupon_list);
                        if ($list_type == 1)
                        {
                            $num = intval($this->userModel->query("SELECT COUNT(*) FROM z_coupon_data WHERE coupon_id = $coupon_id;")[0]['COUNT(*)']);
                            $this->userModel->execute("UPDATE z_coupon_config SET code_amt = $num, status = 0 WHERE id = $coupon_id;");
                            $this->redirect("添加{$num_n}张券码成功", '/admin/coupon/activity_index', 1);
                        } else if ($list_type == 2)
                        {
                            $num = intval($this->userModel->query("SELECT COUNT(*) FROM z_gamemsg_data WHERE config_id = $coupon_id;")[0]['COUNT(*)']);
                            $this->userModel->execute("UPDATE z_gamemsg_config SET code_amt = $num, status = 0 WHERE id = $coupon_id;");
                            $this->redirect("添加{$num_n}张券码成功", '/admin/coupon/cpa_index', 1);
                        } else if ($list_type == 3)
                        {
                            $num = intval($this->userModel->query("SELECT COUNT(*) FROM z_user_coupon_data WHERE coupon_id = $coupon_id;")[0]['COUNT(*)']);
                            $this->userModel->execute("UPDATE z_user_coupon_config SET code_amt = $num, status = 0 WHERE id = $coupon_id;");
                            $this->redirect("添加{$num_n}张券码成功", '/admin/coupon/user_index', 1);
                        }
                    }
                }
            }
        }
        if($list_type == 2)
            $coupon_res['name'] = $coupon_res['ad_id'];

        $this->assign('coupon_res', $coupon_res);
        $this->assign('list_type', $list_type);
        $this->getViewer()->needLayout(false);
        $this->render('coupon_code_add');
    }

    public function add_user_idAction()
    {
        $page_header = $this->get_page_header(3);
        $this->assign('page_header', $page_header);

        $coupon_id = (int)$this->reqVar('id', -1);

        $do_submit = daddslashes(trim($this->postVar('dosubmit', '')));
        $fileUpload = $_FILES['user_id_upload'];

        if ($coupon_id >= 0)
        {
            $coupon_res = $this->userModel->query("SELECT * FROM z_user_coupon_config WHERE id = $coupon_id;")[0];
            if ($coupon_res && !empty($do_submit))
            {
                if (empty($fileUpload['name']))
                {
                    $this->redirect('请上传用户uid列表文件!', '', 3);
                    die();
                } elseif ($fileUpload['size'] > 2048000)
                {
                    $this->redirect('用户列表文件大小不能超过2000KB', '', 3);
                    die();
                } else
                {
                    $file_url = $this->uploadFile($fileUpload);
                    if (empty($file_url))
                    {
                        $this->redirect("上传失败", '/admin/coupon/user_index', 1);
                        die();
                    }

                    $valid_num = intval($this->userModel->query("SELECT COUNT(*) FROM z_user_coupon_data WHERE coupon_id = $coupon_id AND (pnum = '' AND uid = '' )")[0]["COUNT(*)"]);
                    $user_num = count($this->list_from_file);

                    if ($valid_num < $user_num)
                    {
                        $this->redirect("剩余的有效券码数量为{$valid_num}张, 你上传的用户数为{$user_num},券码数量不足,请核实!", '/admin/coupon/user_index', 0);
                    }

                    $sql = "SELECT uid FROM  z_user_coupon_data WHERE coupon_id = $coupon_id AND  uid != ''";  // 去重
                    $uid_list_c = $this->userModel->query($sql);
                    $uid_list = [];
                    foreach ($uid_list_c as $var)
                    {
                        $uid_list[] = strval($var['uid']);
                    }

                    $uid_list = array_diff($this->list_from_file, $uid_list);
                    $uid_list = array_unique($uid_list);

                    foreach ($uid_list as $key => $var)
                    {
                        $sql = "UPDATE z_user_coupon_data SET uid = '$var'  WHERE coupon_id = $coupon_id AND  pnum = '' AND uid = '' LIMIT 1";
                        $this->userModel->execute($sql);
                    }
                    $num = count($uid_list);
                    $this->redirect("添加{$num}条用户uid成功", '/admin/coupon/user_index', 1);
                }
            }
            $this->assign('coupon_res', $coupon_res);
        }
        $this->getViewer()->needLayout(false);
        $this->render('coupon_add_user_id');
    }


    public function send_user_couponAction()
    {
        $coupon_id = (int)$this->reqVar('id', -1);
        $coupon_list = $this->userModel->query("SELECT * FROM z_user_coupon_data WHERE coupon_id = $coupon_id AND (pnum != '' OR uid != '' ) AND is_send = 0;");
        $coupon = $this->userModel->query("SELECT * FROM z_user_coupon_config WHERE id = $coupon_id;")[0];

        $num_fail = 0;
        $num_all = count($coupon_list);
        if ($coupon_list)
        {
            foreach ($coupon_list as $key => $val)
            {
                $uidIds = array();
                if (!is_numeric($val['uid']))
                {
                    $sql = "UPDATE z_user_coupon_data SET is_send = -1 WHERE id = {$val['id']}";
                    $this->userModel->execute($sql);
                    $num_fail ++;
                }
                $userSet['uid'] = $val['uid'];
                $userSet['status'] = 1;
                $userRe = $this->userModel->getUser($userSet);
                if ($userRe)
                    $uidIds[$val['uid']] = $val['id'];
                if (empty($uidIds))
                {
                    $sql = "UPDATE z_user_coupon_data SET is_send = -1 WHERE id = {$val['id']}";
                    $this->userModel->execute($sql);
                    $num_fail ++;
                }
                $msg_content_c = str_replace("{code}", $val["code"], $coupon['msg_content']);
                $msg_content = str_replace("{pwd}", $val["pwd"], $msg_content_c);

                //发送消息
                $sendData['uids_orderids'] = $uidIds;
                $sendData['info_title'] = $coupon['msg_title'];
                $sendData['content'] = $msg_content;
                $sendData['share_msg'] = '';
                $sendData['info_notify'] = 0;
                $sendData['end_time'] = $coupon['end_time'];
                $sendData['os_type'] = $coupon['os_type'];
                $sendData['click_url'] = "";
                $sendData['button_text'] = "";

                $apiData = json_encode($sendData);
                $apiData = urlencode($apiData);

                $apiSendJsonRe = file_get_contents(_API_URL_ . "/admin_user_send_msg.do?data={$apiData}");

                $apiSendRe = json_decode($apiSendJsonRe, true);

                if(!empty($apiSendRe['data']['wrong_orderids'])){
                    $sql = "UPDATE z_user_coupon_data SET is_send = -1 WHERE id = {$val['id']}";
                    $this->userModel->execute($sql);
//                    $err_message = var_export($apiSendRe['data']['wrong_orderids']);
//                    $this->redirect("错误码:{$err_message}, 发送失败", '/admin/coupon/user_index', 1);
                    $num_fail ++;
                }
                else
                {
                    $sql = "UPDATE z_user_coupon_data SET is_send = 1 WHERE id = {$val['id']}";
                    $this->userModel->execute($sql);
                }
            }
        }
        $num_succ = $num_all-$num_fail;
        $this->redirect("{$num_succ}个发送成功, {$num_fail}个发送失败", '/admin/coupon/user_index', 0);

    }

    public function deleteAction()
    {
        $page_header = $this->get_page_header(0);
        $this->assign('page_header', $page_header);

        $coupon_id = (int)$this->reqVar('id', -1);
        $list_type = (int)$this->reqVar('list_type', -1);

        $this->getViewer()->needLayout(false);

        if($list_type ==1)
        {
            $this->userModel->execute("UPDATE z_coupon_config SET status = -1 WHERE id = $coupon_id;");
            $this->redirect("删除成功", '/admin/coupon/activity_index', 1);
        }
        if($list_type ==2)
        {
            $this->userModel->execute("UPDATE z_gamemsg_config SET status = -1 WHERE id = $coupon_id;");
            $this->redirect("删除成功", '/admin/coupon/cpa_index', 1);
        }
        if($list_type ==3)
        {
            $this->userModel->execute("UPDATE z_user_coupon_config SET status = -1 WHERE id = $coupon_id;");
            $this->redirect("删除成功", '/admin/coupon/user_index', 1);
        }
    }

    public function auditAction()
    {
        $page_header = $this->get_page_header(0);
        $this->assign('page_header', $page_header);

        $coupon_id = (int)$this->reqVar('id', -1);
        $list_type = (int)$this->reqVar('list_type', -1);

        $this->getViewer()->needLayout(false);

        if($list_type ==1)
        {
            $this->userModel->execute("UPDATE z_coupon_config SET status = 1 WHERE id = $coupon_id;");
            $this->redirect("操作成功", '/admin/coupon/activity_index', 1);
        }
        if($list_type ==2)
        {
            $this->userModel->execute("UPDATE z_gamemsg_config SET status = 1 WHERE id = $coupon_id;");
            $this->redirect("操作成功", '/admin/coupon/cpa_index', 1);
        }
        if($list_type ==3)
        {
            $this->userModel->execute("UPDATE z_user_coupon_config SET status = 1 WHERE id = $coupon_id;");
            $this->redirect("操作成功", '/admin/coupon/user_index', 1);
        }
    }


    public function detailAction()
    {
        $list_type = (int)$this->reqVar('list_type', -1);
        $this->assign('list_type', $list_type);
        $page_header = $this->get_page_header($list_type);
        $this->assign('page_header', $page_header);

        $pageUrl = "/admin/coupon/detail";
        $page = (int)$this->reqVar('page',1);
        $coupon_id = (int)$this->reqVar('id', 1);
        $this->assign('page', $page);

        if($list_type == 1)
        {
            $coupon_count = intval($this->userModel->query("SELECT COUNT(*) FROM z_coupon_data WHERE coupon_id = $coupon_id;")[0]["COUNT(*)"]);
            $coupon_pages = pages($coupon_count, $page, 60, $pageUrl, $array = array("id" => $coupon_id, "list_type" => $list_type));
            $this->assign('coupon_pages', $coupon_pages);

            $limit_start = ($page-1) * 60 ;
            $_coupon = $this->userModel->query("SELECT * FROM z_coupon_config WHERE id = $coupon_id ;")[0];
            $_list_c = $this->userModel->query("SELECT * FROM z_coupon_data WHERE coupon_id = $coupon_id LIMIT $limit_start, 60;");

            $_list = [];
            foreach($_list_c as $var)
            {
                $var["is_send"] = $var["uid"] != "" ? "发送成功" : "等待发送";
                $_list[] = $var;
            }
        }
        if($list_type == 2)
        {
            $coupon_count = intval($this->userModel->query("SELECT COUNT(*) FROM z_gamemsg_data WHERE config_id = $coupon_id;")[0]["COUNT(*)"]);
            $coupon_pages = pages($coupon_count, $page, 60, $pageUrl, array("id" => $coupon_id, "list_type" => $list_type));
            $this->assign('coupon_pages', $coupon_pages);
            $limit_start = ($page-1) * 60 ;

            $_coupon = $this->userModel->query("SELECT * FROM z_gamemsg_config WHERE id = $coupon_id;")[0];
            $_list_c = $this->userModel->query("SELECT * FROM z_gamemsg_data WHERE config_id = $coupon_id LIMIT $limit_start, 60;");
            $_coupon['start_time'] = "NA";
            $_coupon['end_time'] = "NA";

            $_list = [];
            foreach($_list_c as $var)
            {
                $var["pwd"] = "NA";
                $var["is_send"] = $var["uid"] != "" ? "发送成功" : "等待发送";
                $_list[] = $var;
            }

        }
        if($list_type == 3)
        {
            $coupon_count = intval($this->userModel->query("SELECT COUNT(*) FROM z_user_coupon_data WHERE coupon_id = $coupon_id;")[0]["COUNT(*)"]);
            $coupon_pages = pages($coupon_count, $page, 60, $pageUrl, $array = array("id" => $coupon_id, "list_type" => $list_type));
            $this->assign('coupon_pages', $coupon_pages);
            $limit_start = ($page-1) * 60 ;

            $_coupon = $this->userModel->query("SELECT * FROM z_user_coupon_config WHERE id = $coupon_id;")[0];
            $_list_c = $this->userModel->query("SELECT * FROM z_user_coupon_data WHERE coupon_id = $coupon_id LIMIT $limit_start, 60;");
            $num_succ = intval($this->userModel->query("SELECT COUNT(*) FROM z_user_coupon_data WHERE coupon_id = $coupon_id AND is_send = 1;")[0]["COUNT(*)"]);
            $num_fail = intval($this->userModel->query("SELECT COUNT(*) FROM z_user_coupon_data WHERE coupon_id = $coupon_id AND is_send = -1;")[0]["COUNT(*)"]);

            foreach($_list_c as $var)
            {
                $var["share_msg"] = "NA";
                $is_send = "";
                if(intval($var["is_send"]) == 1)
                    $is_send = "发送成功";
                elseif (intval($var["is_send"]) == 0)
                    $is_send = "等待发送";
                elseif (intval($var["is_send"]) == -1)
                    $is_send = "发送失败";

                $var["is_send"] = $is_send;
                $_list[] = $var;
            }
        }

        $_coupon['succ_rate'] = $num_succ / ($num_fail + $num_succ + 0.0);
        $_coupon['info_notify'] = $_coupon['info_notify'] == 1 ? "是" : "否";
        $_coupon['status'] = $_coupon['status'] == 1 ? "待发送" : "处理成功";

        $this->assign('_coupon', $_coupon);
        $this->assign('_list', $_list);
        $this->assign('adminId', UID);
        $this->assign('_SITE_URL_', _PHOTO_URL_);
        $this->assign('_coupon_id', $_coupon['id']);

        $this->getViewer()->needLayout(false);
        $this->render('coupon_detail');
    }



    public function pauseAction()
    {
        $list_type = (int)$this->reqVar('list_type', -1);
        $this->assign('list_type', $list_type);
        $page_header = $this->get_page_header($list_type);
        $this->assign('page_header', $page_header);

        $coupon_id = (int)$this->reqVar('id', 1);

        if($list_type ==1)
        {
            $this->userModel->execute("UPDATE z_coupon_config SET status = 0 WHERE id = $coupon_id;");
            $this->redirect("操作成功", '/admin/coupon/activity_index', 1);
        }
        if($list_type ==2)
        {
            $this->userModel->execute("UPDATE z_gamemsg_config SET status = 0 WHERE id = $coupon_id;");
            $this->redirect("操作成功", '/admin/coupon/cpa_index', 1);
        }

    }



    private function uploadFile($file_upload)
    {
        if (empty($file_upload))
        {
            return false;
        }
        $path = dirname(dirname(__FILE__)) . "/data/coupon/"; //上传路径
        if (!file_exists($path))
        {
            mkdir("$path", 0700);
        }

        //允许上传的文件格式
        $tp = array("text/plain", "text/json", "text/csv", "application/octet-stream");
        if (!in_array($file_upload["type"], $tp))
        {
            return false;
        }
        $result = false;

        if ($file_upload["name"])
        {
            $tmp_name = explode(".", $file_upload["name"]);
            $file2name = md5_file($file_upload["tmp_name"]) . '.' . $tmp_name[1];
            $file2 = $path . $file2name;
            $result = move_uploaded_file($file_upload["tmp_name"], $file2);

            // parse coupon
            $handle = @fopen($file2, "r");
            if ($handle)
            {
                $this->list_from_file = array();
                while (!feof($handle))
                {
                    $buffer = fgets($handle, 4096);   // 按行读取
                    $buffer = trim($buffer);
                    if(empty($buffer))
                        continue;
                    $buffer_c1 = explode(" ", $buffer);
                    $buffer_c2 = explode(",", $buffer);
                    $buffer_c = explode("\t", $buffer);
                    $buffer = count($buffer_c1)>count($buffer_c) ? $buffer_c1 : $buffer_c;
                    $buffer = count($buffer)>count($buffer_c2) ? $buffer : $buffer_c2;

                    if(count($buffer)>=2)
                    {
                        $this->list_from_file[$buffer[1]] = $buffer[0];
                    }
                    else
                    {
                        $this->list_from_file[] = $buffer[0];
                    }
                }
                fclose($handle);
            }
        }
        if ($result)
        {
            return _PHOTO_URL_ . '/hbdata/coupon/' . $file2name;
        } else
        {
            return false;
        }
    }

    private function oplog($addContent)
    {
        if (empty($addContent))
        {
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



    public function excelAction()
    {
        $id = intval($this->reqVar('id', 0));
        $list_type = intval(daddslashes($this->reqVar('list_type', 0)));

        $excelContent = $this->payExcelTemplate($id, $list_type);
        if (empty($excelContent))
        {
            $this->redirect('导出失败,没有导出内容!', '', 1);
            die();
        }
//        $excelData = iconv('utf-8', 'gbk', $excelContent);
        $excelData = $excelContent;
        header('Content-type:application/vnd.ms-excel;charset=utf-8');
        header("Content-Disposition:filename=coupon_send.csv");
        echo $excelData;
        // header("Content-Disposition:filename=" . iconv('utf-8','gbk',"支付宝待付款记录".date("YmdHi",time())) . ".csv");
    }

    private function payExcelTemplate($coupon_id, $list_type)
    {

        if ($list_type == 1)
        {
            $exchange1List = $this->userModel->query("SELECT * FROM z_coupon_data WHERE coupon_id = $coupon_id ORDER BY id DESC");

        } else if ($list_type == 2)
        {
            $exchange1List = $this->userModel->query("SELECT * FROM z_gamemsg_data WHERE config_id = $coupon_id ORDER BY id DESC");

        } else if ($list_type == 3)
        {
            $exchange1List = $this->userModel->query("SELECT * FROM z_user_coupon_data WHERE coupon_id = $coupon_id ORDER BY id DESC");
        }

        if (!$exchange1List) return;

        $replaceArr = array("・", "&nbsp;", " ", "•");
        $excelContent = "'ID, 优惠券ID, 券码, 密码, 用户UID, 发送状态, 上传时间\r\n";
        foreach ($exchange1List as $key => $val)
        {
            if ($list_type == 2)
                $val['coupon_id'] = $val['config_id'];

            $coupon_id = str_replace($replaceArr, "", $val['coupon_id']);
            $code = str_replace($replaceArr, "", $val['code']);
            $pwd = str_replace($replaceArr, "", $val['pwd']);
            $uid = str_replace($replaceArr, "", $val['uid']);
            $is_send_c = intval($val['uid']);
            if($is_send_c == 0)
                $is_send = "未发送";
            elseif($is_send_c == 1)
                $is_send = "发送成功";
            elseif($is_send_c == -1)
                $is_send = "发送失败";
            $ctime = str_replace($replaceArr, "", $val['ctime']);

            $excelContent .= $val['id'] . ',' . $coupon_id . ',' . $code . ',' . $pwd . ',' . $uid . ',' . $is_send . ',' . $ctime . "\r\n";
        }
        return $excelContent;
    }


}
