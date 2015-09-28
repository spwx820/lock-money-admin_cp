<?php
class Plugin_pushcontent
{

    //快网提供的接口用户名
    private $user_name = '';
    //快网提供的接口用户KEY
    private $user_key = '';
    //快网提供的接口用户名密码
    private $password = '';
    private $host = 'cs.fastweb.com.cn';
    private $host_url = '/interface/push_content.php';
    private $host_port = 80;
    private $call_result = array(
        'result' => 'error',
        'detail_info' => '',
        'data_list' => array(),
    );

    /**
     * 提交更新内容.
     * @ param array $content_arr,要提交的更新内容,必须是数组
     * */
    private function postData($content_arr = NULL) {
        $resp_str = '';
        $errno = 0;
        $errstr = '';
        $timeout = 10;
        $post_str = json_encode($content_arr);
        $fp = fsockopen($this->host, $this->host_port, $errno, $errstr, $timeout);
        if (!$fp) {
            $this->call_result['detail_info'] = 'destination_host_connect_failed';
            return $this->call_result;
        }
        $content_length = strlen($post_str);
        $post_header = "POST " . $this->host_url . " HTTP/1.1\r\n";
        //请注意，在POST数据的时候，POST内容的Content-Type不能设置为 application/x-www-form-urlencoded，否则无法正确调用
        /* $post_header .= "Content-Type: application/x-www-form-urlencoded\r\n";不要有这行代码 */
        $post_header .= "User-Agent: fastweb interface\r\n";
        $post_header .= "Host: " . $this->host . "\r\n";
        $post_header .= "Content-Length: " . $content_length . "\r\n";
        $post_header .= "Connection: close\r\n\r\n";
        $post_header .= $post_str . "\r\n\r\n";
        fwrite($fp, $post_header);
        while (!feof($fp)) {
            $resp_str .= fgets($fp, 512);
        }
        fclose($fp);
        return ($resp_str);
    }

    /**
     * 生成调用的验证码
     * @return string
     */
    private function getCheckCode() {
        return md5(date('Ymd') . $this->user_name . $this->user_key . $this->password);
    }

    private function analyResult($ret_str) {
        $ret_str = str_replace('\\', '', $ret_str);
        $out = array();
        preg_match('/\{.*\}/', $ret_str, $out);
        if (!empty($out)) {
            $result = json_decode($out[0], true);
        } else {
            $this->call_result['detail_info'] = 'api_internal_error';
            $result = $this->call_result;
        }
        return $result;
    }

    /**
     * 设置接口用户信息
     * @param string $user_name
     * @param string $password
     * @param string $user_key
     */
    public function setUserInfo($user_name, $password, $user_key) {
        $this->user_name = $user_name;
        $this->password = $password;
        $this->user_key = $user_key;
    }

    /**
     * 设置服务器信息
     * @param string $host
     * @param string $host_url
     * @param string $port
     */
    public function setHostInfo($host, $host_url, $port) {
        $this->host = $host;
        $this->host_url = $host_url;
        $this->host_port = $port;
    }

    /**
     * 调用内容推送接口
     * @param array $url_arr
     * @param array $dir_arr
     * @return array
     */
    public function pushData($url_arr, $dir_arr = NULL) {
        if (empty($url_arr) && empty($dir_arr)) {
            $this->call_result['detail_info'] = 'no_url_data';
            return($this->call_result);
        }
        if (empty($this->user_name) || empty($this->user_key) || empty($this->password)) {
            $this->call_result['detail_info'] = 'user_info_is_not_correct';
            return($this->call_result);
        }
        if (empty($this->host) || empty($this->host_url) || empty($this->host_port)) {
            $this->call_result['detail_info'] = 'destination_host_info_error';
            return($this->call_result);
        }
        $post_data = array(
            'user_name' => $this->user_name,
            'check_code' => $this->getCheckCode(),
            'url_list' => $url_arr,
            'dir_list' => $dir_arr
        );
        $ret_str = $this->postData($post_data);
        return $this->analyResult($ret_str);
    }

}