<?php
/**
 * 获得验证码并验证
 *
 * @example
 * 1. 生成/captcha/
 * 	$captcha = new Plugin_Captcha();
 *  $captcha->image();
 *
 * 2. 显示 /reg/
 * <p><img src="visual-captcha.php" width="200" height="60" alt="Visual CAPTCHA" /></p>
 * <p><a href="audio-captcha.php">Can't see the image? Click for audible version</a></p>
 *
 * 3.检验 /areg/
 * <?php
 *
 * 	if (Plugin_Captcha::Validate($_POST['user_code'])) {
 * 		echo 'Valid code entered';
 * 	} else {
 * 		echo 'Invalid code entered';
 * 	}
 * 	?>
 *
 * 4. 手册，常用函数
 * SetWidth(int iWidth) - set the width of the CAPTCHA image. Defaults to 200px.
 * SetHeight(int iHeight) - set the height of the CAPTCHA image. Defaults to 50px.
 * SetNumChars(int iNumChars) - set the number of characters to display. Defaults to 5.
 * SetNumLines(int iNumLines) - set the number of interference lines to draw. Defaults to 70.
 * DisplayShadow(bool bShadow) - specify whether or not to display character shadows.
 * SetOwnerText(sting sOwnerText) - owner text to display at bottom of CAPTCHA image, discourages attempts to break your CAPTCHA through display on porn and other unsavoury sites.
 * SetCharSet(variant vCharSet) - specify the character set to select characters from. If left blank defaults to A-Z. Can be specified as an array of chracters e.g. array('1', 'G', '3') or as a string of characters and character ranges e.g. 'a-z,A-Z,0,3,7'.
 * CaseInsensitive(bool bCaseInsensitive) - specify whether or not to save user code preserving case. If setting to "false" you need to pass "false" as the second parameter to the "Validate" function when checking the user entered code.
 * SetBackgroundImages(variant vBackgroundImages) - specify one (a string) or more (an array) images to display instead of noise lines. If more than one image is specified the library selects one at random.
 * SetMinFontSize(int iMinFontSize) - specify the minimum font size to display. Defaults to 16.
 * SetMaxFontSize(int iMaxFontSize) - specify the maximum font size to display. Defaults to 25.
 * UseColour(bool bUseColour) - if true displays noise lines and characters in randomly selected colours.
 * SetFileType(string sFileType) - specify the output format jpeg, gif or png. Defaults to jpeg.
 * @category   Plugin
 * @package    Plugin_Captcha
 * @author 	   liuxp
 * @copyright
 * @license
 */
require_once 'Captcha/Captcha.inc.php';

class Plugin_Captcha extends Leb_Plugin_Abstract
{
	/**
	 * 单例
	 *
	 * @var Leb_Request
	 */
	static protected $_instance = null;

	/**
	 * 验证码对象
	 *
	 * @var Capcha
	 */
	protected $_captcha = null;

	/**
	 * 实例化本程序
	 * @param $args = func_get_args();
     * @return object of this class
	 */
	static public function getInstance()
	{
		if (!isset(self::$_instance)) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * 启动session
	 *
	 */
	public function __construct()
	{
		@session_start();
	}

    /**
     * 输出校验码图片二进制
     * @param int $length 输出几个字符
     * @param int $width 图片宽度
     * @param int $height 图片高度
     *
     */
    public function image($length=5, $width=200, $height=60, $minFontSize=12, $maxFontSize=16)
    {
    	$fonts = array(_PLUGIN_ . 'Captcha/VeraBd.ttf', _PLUGIN_ . 'Captcha/VeraIt.ttf', _PLUGIN_ . 'Captcha/Vera.ttf');
        $this->_captcha = new PhpCaptcha($fonts, $width, $height);
        $this->_captcha->SetMinFontSize($minFontSize);
        $this->_captcha->SetMaxFontSize($maxFontSize);
        $this->_captcha->UseColour(true);
        $this->_captcha->SetNumChars($length);
        $this->_captcha->Create();
    }

    /**
     * 输出声音二进制
     * 请自己扩展
     *
     * @since 1.2.0
     */
    public function audio()
    {
        $this->_captcha = new AudioPhpCaptcha('/usr/bin/flite', 'captcha/');
        $this->_captcha->Create();
    }

    /**
     * 校验
     *
     * @param String $text 输入文本
     * @param boolean $caseInsensitive 大小写注意
     * @return boolean true
     */
    public static function validate($text, $caseInsensitive=true){
        return PhpCaptcha::Validate($text, $caseInsensitive);
    }

    /**
     * 执行captcha相关方法
     *
     * @param string $method
     * @param array $arguments
     */
    public function __call($method, $arguments)
    {
    	if (!empty($this->_captcha)) {
    		return call_user_method(ucfirst($method), $this->_captcha, $arguments);
    	}
    }
}
