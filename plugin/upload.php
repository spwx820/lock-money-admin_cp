<?php
/**
 * 文件上传类
 * Author:mingyu@leju.sina.com.cn
 *
 */
class plugin_upload
{
	private $attachdir="";
	private $file_path='';
	private $file_size='';
	private $file_ext='';
	private $file_name='';
	private $max_file_size_in_bytes=0;
	private $is_sucess=false;
	private $error_msg='';
	private $extensions = array("jpg", "gif", "png","jpeg");//允许的扩展名
    private $file_mime = '';
    private $uploadErrors = array(
        0=>"There is no error, the file uploaded with success",
        1=>"The uploaded file exceeds the upload_max_filesize directive in php.ini",
        2=>"The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form",
        3=>"The uploaded file was only partially uploaded",
        4=>"No file was uploaded",
        6=>"Missing a temporary folder"
	);

	/**
	 * 设置图片根目录
	 * */
	public function set_attachdir($attachdir)
	{
		$this->attachdir=$attachdir;
	}


	/**
	 * 设置文件的扩展名
	 * */
	
	public function set_extensions($extensions)
	{
		$this->extensions=$extensions;
	}

	/**
	 * 得到文件扩展名
	 * */
	public function get_file_ext()
	{
		return $this->file_ext;
	}

    /**
	 * 设置文件的mime
	 * */

	public function set_file_mime($file_mime)
	{
		$this->file_mime = $file_mime;
	}

	/**
	 * 得到文件mime
	 * */
	public function get_file_mime()
	{
		return $this->file_mime;
	}

	/**
	 * 得到文件尺寸
	 * 
	 * */
	public function get_file_size()
	{
		return $this->file_size;
	}
	/**
	 * 得到文件名
	 * */
	public function get_file_name()
	{
		return $this->file_name;
	}
	/**
	 * 得到错误信息
	 * */
	public function get_error_msg()
	{
		return $this->error_msg;
	}
	/**
	 * 得到是否上传成功
	 * */
	public function get_is_sucess()
	{
		return $this->is_sucess;
	}
	/**
	 * 设置文件最大尺寸
	 * */
	public function set_max_file_size_in_bytes($max_file_size_in_bytes)
	{
		$this->max_file_size_in_bytes=$max_file_size_in_bytes;
	}
	/**
	 * 得到文件路径
	 * */
	public function get_file_path()
	{
		return $this->file_path;
	}
	/**
	 * 上传文件
	 * */
	public function upload()
	{
		$upload_name = "Filedata";
		if (!isset($_FILES[$upload_name])) {
			$this->handle_error("No upload found in \$_FILES for " . $upload_name);
		}else if (isset($_FILES[$upload_name]["error"]) && $_FILES[$upload_name]["error"] != 0) {
			$this->handle_error($this->uploadErrors[$_FILES[$upload_name]["error"]]);
		}else if (!isset($_FILES[$upload_name]["tmp_name"]) || !@is_uploaded_file($_FILES[$upload_name]["tmp_name"])) {
			$this->handle_error("Upload failed is_uploaded_file test.");
		}else if (!isset($_FILES[$upload_name]['name'])) {
			$this->handle_error("File has no name.");
		}
		
		$this->file_name=$_FILES[$upload_name]["name"];
		$file_size = @filesize($_FILES[$upload_name]["tmp_name"]);
		$this->file_size=$file_size;
		
		if (!$file_size || $file_size >$this->max_file_size_in_bytes) {
			$this->handle_error("File exceeds the maximum allowed size");
		}
	
		if ($file_size <= 0) {
			$this->handle_error("File size outside allowed lower bound");
		}
  //上线去掉感叹号  目前暂时测试用
        if (PHP_VERSION >= 5.3) {
            if (function_exists('finfo_open')) {
                $finfo = finfo_open(FILEINFO_MIME);
                $file_mime = finfo_file($finfo, $_FILES[$upload_name]['tmp_name']);
                finfo_close($finfo);
            } else {
                $this->handle_error("PECL fileinfo not install");
            }

        } else {
            $file_mime = mime_content_type($_FILES[$upload_name]['tmp_name']);
//              $file_info = getimagesize($_FILES[$upload_name]['tmp_name']);
//              $file_mime = $file_info['mime'];
//             $file_mime = 'image/jpeg';
        }

        if (false !== strpos($file_mime, ';')) {
            $file_mime = substr($file_mime, 0, strpos($file_mime, ';'));
        }

        $file_ext = $this->file_ext($_FILES[$upload_name]['name']);

        //判断mimetype是否正确
        if ($file_mime != plugin_mimetype::find_type($file_ext)) {
            $this->handle_error("$file_ext,Invalid file mimetype");
        }

        $this->set_file_mime($file_mime);

		$this->file_ext = $file_ext;

		$is_valid_extension = false;
        //判断项目是否允许上传该文件类型
        if (!in_array($file_ext, $this->extensions)) {
            $this->handle_error("Invalid file extension");
        }
        //获得hash目录
        $hashpath = $this->hashPath();
        $this->file_path=$this->attachdir.'/'.$hashpath['dir']."/".$hashpath['file'];
        //如果没有目录创建目录
        mkdir_p($this->attachdir.'/'.$hashpath['dir']);
		if(!@copy($_FILES[$upload_name]["tmp_name"],$this->file_path))
		{
			$this->handle_error("File could not be saved.");
		}
		$this->is_sucess=true;
	}
	/**
	 * 处理错误信息
	 * */
	public function handle_error($msg)
	{
		$this->is_sucess=false;
		$this->error_msg=$msg;
		throw new Exception($msg);
	}
	/**
	 *	得到文件扩展名 
	 * */
	public function  file_ext($filename) 
	{
		return strtolower(trim(substr(strrchr($filename, '.'), 1)));
	}
	/**
	 * 得到文件路径
	 * */
	public function get_random_file_name($fileext)
	{
		$file_name = time().'_'.$this->random(4).'.'.$fileext;
		return $file_name;
	}
    public function hashPath()
    {
        $hash_code = md5(uniqid().time());
        $hash_path = $this->hash_path($hash_code);
        return $hash_path;
    }

    public function hash_path($code, $step = 2, $depth = 3)
	{
		$t_path = array("dir" => "", "file" => "");
		$t_path["dir"] = substr($code, 0, 2) . '/' . substr($code, 2, 2) . '/' . substr($code, 4, 1);
		$t_path["file"] = substr($code, 5);
		return $t_path;
	}
	/**
	 * 产生随机字符
	 * */ 
	public function random($length, $numeric = 0)
	{
		PHP_VERSION < '4.2.0' ? mt_srand((double)microtime() * 1000000) : mt_srand();
		$seed = base_convert(md5(print_r($_SERVER, 1).microtime()), 16, $numeric ? 10 : 35);
		$seed = $numeric ? (str_replace('0', '', $seed).'012340567890') : ($seed.'zZ'.strtoupper($seed));
		$hash = '';
		$max = strlen($seed) - 1;
		for($i = 0; $i < $length; $i++) {
			$hash .= $seed[mt_rand(0, $max)];
		}
		return $hash;
	}
	/**
	 * 删除本地图片
	 * */
	public function del_file()
	{
		if(file_exists($this->file_path)){
			unlink($this->file_path);
		}
	}
}