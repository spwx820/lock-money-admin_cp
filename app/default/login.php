<?php
/**
 * 登录操作
 *
 * @category   Leb
 * @package    Leb_Action
 * @author     lihui
 * @version    $Id: login.php 2014-09-03 9:58:00 lihui
 * @copyright (c) 2014 dianjoy.com
 * @license
 */
class loginController extends Application
{
    const VALIDATE_CODE='ZHUAN_ADMIN_LOGIN_CODE';
    private $adminModel;
    private $codeModel;

    public function  execute($plugins)
    {
        $this->adminModel = $this->loadAppModel('Admin');
        $this->codeModel  = $this->loadAppModel('Login_code');
    }

    /**
     * 登录
     */
    public function indexAction()
    {
        $go = $this->postVar('go_action');
        $email = $this->postVar('z_username');
        $password = $this->postVar('z_password');
        $validate_code = $this->postVar('z_code');

        $go = daddslashes($go);
        $email = daddslashes($email);
        $password = daddslashes($password);
        $validate_code = daddslashes($validate_code);

        if($go == 'login'){
            //获取redis缓存code select = 6
            $redis = Leb_Dao_Redis::getInstance();
            $redisCode = $redis->get(self::VALIDATE_CODE);

            if(!$email){
                $this->assign('error_msg', '请填写用户！');
            }elseif(!$password){
                $this->assign('error_msg', '请填写密码！');
            }elseif(!$validate_code){
                $this->assign('error_msg', '请填写验证码！');
            }else{
                preg_match ("#(?=^.*?[a-z])(?=^.*?[A-Z])(?=^.*?\d)^(.{10,16})$#", $password,$matches);
                if(!empty($matches)){
                    if(strcasecmp($validate_code,$redisCode)){
                        $this->lockUser($email,$validate_code);

                        $this->assign('error_msg', '验证码错误！');

                    }elseif($this->adminModel->login($email, $password)){
                        if(1 == $this->adminModel->is_pause || 2 == $this->adminModel->is_pause){
                            $this->assign('error_msg', '账号或密码错误,如不能正常登录请联系管理员');
                        }else{
                            $this->redirect('', '/admin/default/', 0);
                        }
                    }else{
                        $this->assign('error_msg', '账号或密码错误,如不能正常登录请联系管理员!');
                    }
                }
                else
                    $this->assign('error_msg', '账号或密码错误,如不能正常登录请联系管理员!');
            }
        }

        $this->assign('username', $email);
        $this->getViewer()->needLayout(false);
        $this->render('login');
    }

    /**
     * 判断锁定用户
     */
    public function lockUser($email,$code)
    {
        $reBack = FALSE;
        if(!empty($email) && !empty($code)){
            $codeSet['email'] = $email;
            $codeSet['start_time'] = time() - 3600;
            $codeCount = $this->codeModel->getCodeCount($codeSet);
            if($codeCount > 10){
                //锁用户操作
                $adminSet['email'] = $email;
                $adminInfo = $this->adminModel->getAdmin($adminSet);
                if($adminInfo){
                    $this->adminModel->lockUser($adminInfo['id']);
                }
            }else{
                $codeAdd['email'] = $email;
                $codeAdd['code']  = $code;
                $codeAdd['createtime']= time();
                $codeAdd['login_ip']  = $_SERVER['REMOTE_ADDR'];
                $this->codeModel->addCode($codeAdd);
            }

            $reBack = TRUE;
        }
        return $reBack;
    }

    /**
     * 注销
     */
    public function logoutAction()
    {
        $this->adminModel->logout();
        $this->redirect('', '/default/login/', 0);
    }

    /**
     * 生成指定长度随机字符串
     */
    private function randString($len)
    {
        $str="abcdefghijkmnpqrstuvwxyzABCDEFGHIJKLMNPQRSTUVWXYZ123456789";
        $s = "";
        $str_len = strlen($str);
        for($i=0; $i < $len; $i++){
            $s .= $str[rand(0, $str_len - 1)];
        }
        return strtoupper($s);
    }

    /**
     * 输出60*20验证码图片
     */
    public function codeAction()
    {
        $this->getViewer()->needLayout(false);
        $width = 84;    //验证码图片的宽度
        $height = 24;   //验证码图片的高度

        $code = $this->randstring(4);

        //redis缓存code
        $redis = Leb_Dao_Redis::getInstance();
        $redis->setex(self::VALIDATE_CODE,300,$code); //

//        var_dump($code);die();

//        setcookie(self::VALIDATE_CODE, $code, time()+300, '/', $_SERVER['SERVER_NAME']);
        @header("Content-Type:image/png");
        $img = @imagecreate($width, $height);
        imagecolorallocate($img, 255, 255, 255);
        imagerectangle($img, 0, 0, $width-1, $height-1, imagecolorallocate($img, 41, 163, 238));

        $len = strlen($code);
        for($i = 0; $i < $len; $i++){
            $font = mt_rand(5, 100);
            $x = mt_rand(1, 8) + $width * $i / 4;
            $y = mt_rand(1, $height / 4);
            $color = imagecolorallocate($img, mt_rand(0, 100), mt_rand(0, 150),mt_rand(0, 200));
            imagestring($img, $font, $x, $y, $code[$i], $color);
        }

        for ($i=0; $i < 100/$len; $i++){
            imagesetpixel($img, rand(1, $width), rand(1, $height), mt_rand(50, 200));
        }

        imagepng($img);
        imagedestroy($img);
    }

}