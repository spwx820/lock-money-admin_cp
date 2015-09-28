<?php
/**
 * Curl based HTTP Client 
 * Simple but effective OOP wrapper around Curl php lib.
 * Contains common methods needed 
 * for getting data from url, setting referrer, credentials, 
 * sending post data, managing cookies, etc.
 * 
 * Samle usage:
 * $curl = &new Curl_HTTP_Client();
 * $useragent = "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)";
 * $curl->set_user_agent($useragent);
 * $curl->store_cookies("/tmp/cookies.txt");
 * $post_data = array('login' => 'pera', 'password' => 'joe');
 * $html_data = $curl->send_post_data(http://www.foo.com/login.php, $post_data);
 */

class curlhttp extends CSnsService  implements ICurlhttp
{
	/**
	 * Curl handler
	 * @access private
	 * @var resource
	 */
	var $ch ;

	/**
	 * set debug to true in order to get usefull output
	 * @access private
	 * @var string
	 */
	var $debug = false;

	/**
	 * Contain last error message if error occured
	 * @access private
	 * @var string
	 */
	var $error_msg;


	/**
	 * http头信息
	 * @var string
	 */
	var $http_head;
	
	/**
	 * Init Curl session	 
	 * @access public
	 */
	function init()
	{
		// initialize curl handle
		$this->ch = curl_init();

		//set various options

		//set error in case http return code bigger than 300
		curl_setopt($this->ch, CURLOPT_FAILONERROR, true);

		// allow redirects
		curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, true);
		
		// use gzip if possible
		curl_setopt($this->ch,CURLOPT_ENCODING , 'gzip, deflate');

		// do not veryfy ssl
		// this is important for windows
		// as well for being able to access pages with non valid cert
		curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, 0);
	}

	/**
	 * Set username/pass for basic http auth
	 * @param string user
	 * @param string pass
	 * @access public
	 */
	function set_credentials($username,$password)
	{
		curl_setopt($this->ch, CURLOPT_USERPWD, "$username:$password");
	}

	/**
	 * Set referrer
	 * @param string referrer url 
	 * @access public
	 */
	function set_referrer($referrer_url)
	{
		curl_setopt($this->ch, CURLOPT_REFERER, $referrer_url);
	}

	/**
	 * Set client's useragent
	 * @param string user agent
	 * @access public
	 */
	function set_user_agent($useragent)
	{
		curl_setopt($this->ch, CURLOPT_USERAGENT, $useragent);
	}
	
	/**
	 * Set client's header
	 * @param array userheader
	 * @access public
	 */
	function set_user_header($userheader)
	{
		curl_setopt($this->ch, CURLOPT_HTTPHEADER, $userheader);
	}

	/**
	 * Set to receive output headers in all output functions
	 * @param boolean true to include all response headers with output, false otherwise
	 * @access public
	 */
	function include_response_headers($value)
	{
		curl_setopt($this->ch, CURLOPT_HEADER, $value);
	}


	/**
	 * Set proxy to use for each curl request 代理通道
	 * @param string proxy
	 * @access public
	 */
	function set_proxy($proxy)
	{
		curl_setopt($this->ch, CURLOPT_PROXY, $proxy);
	}



	/**
	 * Send post data to target URL	 
	 * return data returned from url or false if error occured
	 * @param string url
	 * @param mixed post data (assoc array ie. $foo['post_var_name'] = $value or as string like var=val1&var2=val2)
	 * @param string ip address to bind (default null)
	 * @param int timeout in sec for complete curl operation (default 10)
	 * @return string data
	 * @access public
	 */
	function send_post_data($url, $postdata, $ip=null, $timeout=10)
	{
		//set various curl options first

		// set url to post to
		curl_setopt($this->ch, CURLOPT_URL,$url);
		
		//set head to print out;
		curl_setopt($this->ch, CURLOPT_HEADER, 1);
		curl_setopt($this->ch, CURLOPT_FAILONERROR, false);
		//curl_setopt($this->ch, CURLOPT_CONNECTTIMEOUT, 3);
		
		// return into a variable rather than displaying it
		curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);

		//bind to specific ip address if it is sent trough arguments
		if($ip)
		{
			if($this->debug)
			{
				echo "Binding to ip $ip\n";
			}
			curl_setopt($this->ch,CURLOPT_INTERFACE,$ip);
		}

		//set curl function timeout to $timeout
		curl_setopt($this->ch, CURLOPT_TIMEOUT, $timeout);

		//set method to post
		curl_setopt($this->ch, CURLOPT_POST, true);
		
		//generate post string
		$post_array = array();
		if(is_array($postdata))
		{		
			foreach($postdata as $key=>$value)
			{
				$post_array[] = urlencode($key) . "=" . urlencode($value);
			}

			$post_string = implode("&",$post_array);

			if($this->debug)
			{
				echo "Url: $url\nPost String: $post_string\n";
			}
		}
		else 
		{
			$post_string = $postdata;
		}
		
		// set post string
		curl_setopt($this->ch, CURLOPT_POSTFIELDS, $post_string);
		
		//and finally send curl request
		$result = curl_exec($this->ch);
		
		//modify on 2009-12-07
		$rsArr = split("\n[ \t]*\r", $result);
		$rsArr = array_values(array_filter($rsArr));
		$this->http_head = $rsArr[0];
		$len = count($rsArr);
		
		if(strstr($rsArr[1], "HTTP/1.1")) {
			$this->http_head = $rsArr[1];
			$result = $rsArr[2];
			for( $i = 3; $i < $len; $i ++ ) {
				$result .= $rsArr[$i];
			}
		}
		else {
			$result = $rsArr[1];
			for( $i = 2; $i < $len; $i ++ ) {
				$result .= $rsArr[$i];
			}
		}
		unset($rsArr);
		//modify end
		
		if(curl_errno($this->ch))
		{
			if($this->debug)
			{
				echo "Error Occured in Curl\n";
				echo "Error number: " .curl_errno($this->ch) ."\n";
				echo "Error message: " .curl_error($this->ch)."\n";
			}
			return false;
		}
		else
		{
			return $result;
		}
	}

	/**
	 * fetch data from target URL	 
	 * return data returned from url or false if error occured
	 * @param string url	 
	 * @param string ip address to bind (default null)
	 * @param int timeout in sec for complete curl operation (default 5)
	 * @return string data
	 * @access public
	 */
	function fetch_url($url, $ip=null, $timeout=5)
	{
		// set url to post to
		curl_setopt($this->ch, CURLOPT_URL,$url);

		//set method to get
		curl_setopt($this->ch, CURLOPT_HTTPGET,true);

		// return into a variable rather than displaying it
		curl_setopt($this->ch, CURLOPT_RETURNTRANSFER,true);

		//bind to specific ip address if it is sent trough arguments
		if($ip)
		{
			if($this->debug)
			{
				echo "Binding to ip $ip\n";
			}
			curl_setopt($this->ch,CURLOPT_INTERFACE,$ip);
		}

		//set curl function timeout to $timeout
		curl_setopt($this->ch, CURLOPT_TIMEOUT, $timeout);

		//and finally send curl request
		$result = curl_exec($this->ch);

		if(curl_errno($this->ch))
		{
			if($this->debug)
			{
				echo "Error Occured in Curl\n";
				echo "Error number: " .curl_errno($this->ch) ."\n";
				echo "Error message: " .curl_error($this->ch)."\n";
			}

			return false;
		}
		else
		{
			return $result;
		}
	}

	/**
	 * Fetch data from target URL
	 * and store it directly to file	 	 
	 * @param string url	 
	 * @param resource value stream resource(ie. fopen)
	 * @param string ip address to bind (default null)
	 * @param int timeout in sec for complete curl operation (default 5)
	 * @return boolean true on success false othervise
	 * @access public
	 */
	function fetch_into_file($url, $fp, $ip=null, $timeout=5)
	{
		// set url to post to
		curl_setopt($this->ch, CURLOPT_URL,$url);

		//set method to get
		curl_setopt($this->ch, CURLOPT_HTTPGET, true);

		// store data into file rather than displaying it
		curl_setopt($this->ch, CURLOPT_FILE, $fp);

		//bind to specific ip address if it is sent trough arguments
		if($ip)
		{
			if($this->debug)
			{
				echo "Binding to ip $ip\n";
			}
			curl_setopt($this->ch, CURLOPT_INTERFACE, $ip);
		}

		//set curl function timeout to $timeout
		curl_setopt($this->ch, CURLOPT_TIMEOUT, $timeout);

		//and finally send curl request
		$result = curl_exec($this->ch);

		if(curl_errno($this->ch))
		{
			if($this->debug)
			{
				echo "Error Occured in Curl\n";
				echo "Error number: " .curl_errno($this->ch) ."\n";
				echo "Error message: " .curl_error($this->ch)."\n";
			}

			return false;
		}
		else
		{
			return true;
		}
	}

	/**
	 * Send multipart post data to the target URL	 
	 * return data returned from url or false if error occured
	 * (contribution by vule nikolic, vule@dinke.net)
	 * @param string url
	 * @param array assoc post data array ie. $foo['post_var_name'] = $value
	 * @param array assoc $file_field_array, contains file_field name = value - path pairs
	 * @param string ip address to bind (default null)
	 * @param int timeout in sec for complete curl operation (default 30 sec)
	 * @return string data
	 * @access public
	 */
	function send_multipart_post_data($url, $postdata, $file_field_array=array(), $ip=null, $timeout=30)
	{
		//set various curl options first

		// set url to post to
		curl_setopt($this->ch, CURLOPT_URL, $url);
		
		// return into a variable rather than displaying it
		curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
		
		//bind to specific ip address if it is sent trough arguments
		if($ip)
		{
			if($this->debug)
			{
				echo "Binding to ip $ip\n";
			}
			curl_setopt($this->ch,CURLOPT_INTERFACE,$ip);
		}

		//set curl function timeout to $timeout
		curl_setopt($this->ch, CURLOPT_TIMEOUT, $timeout);

		//set method to post
		curl_setopt($this->ch, CURLOPT_POST, true);

		// disable Expect header
		// hack to make it working
		$headers = array("Expect: ");
		curl_setopt($this->ch, CURLOPT_HTTPHEADER, $headers);
		
		// initialize result post array
		$result_post = array();

		//generate post string
		$post_array = array();
		$post_string_array = array();
		if(!is_array($postdata))
		{
			return false;
		}

		foreach($postdata as $key=>$value)
		{
			$post_array[$key] = $value;
			$post_string_array[] = urlencode($key)."=".urlencode($value);
		}

		$post_string = implode("&",$post_string_array);


		if($this->debug)
		{
			echo "Post String: $post_string\n";
		}

		// set post string
		//curl_setopt($this->ch, CURLOPT_POSTFIELDS, $post_string);


		// set multipart form data - file array field-value pairs
		if(!empty($file_field_array))
		{
			foreach($file_field_array as $var_name => $var_value)
			{
				if(strpos(PHP_OS, "WIN") !== false) $var_value = str_replace("/", "\\", $var_value); // win hack
				$file_field_array[$var_name] = "@".$var_value;
			}
		}

		// set post data
		$result_post = array_merge($post_array, $file_field_array);
		curl_setopt($this->ch, CURLOPT_POSTFIELDS, $result_post);


		//and finally send curl request
		$result = curl_exec($this->ch);
		
		if(curl_errno($this->ch))
		{
			if($this->debug)
			{
				echo "Error Occured in Curl\n";
				echo "Error number: " .curl_errno($this->ch) ."\n";
				echo "Error message: " .curl_error($this->ch)."\n";
			}

			return false;
		}
		else
		{
			return $result;
		}
	}

	/**
	 * Set file location where cookie data will be stored and send on each new request
	 * @param string absolute path to cookie file (must be in writable dir)
	 * @access public
	 */
	function store_cookies($cookie_file)
	{
		// use cookies on each request (cookies stored in $cookie_file)
		curl_setopt ($this->ch, CURLOPT_COOKIEJAR, $cookie_file);
		curl_setopt ($this->ch, CURLOPT_COOKIEFILE, $cookie_file);
	}
	
	/**
	 * Set custom cookie
	 * @param string cookie
	 * @access public
	 */
	function set_cookie($cookie)
	{
		curl_setopt ($this->ch, CURLOPT_COOKIE, $cookie);
	}

	/**
	 * Get last URL info 
	 * usefull when original url was redirected to other location	
	 * @access public
	 * @return string url
	 */
	function get_effective_url()
	{
		return curl_getinfo($this->ch, CURLINFO_EFFECTIVE_URL);
	}

	
	/**
	 * Get http header
	 * @return array
	 * @version 2009-11-20 
	 */
	function get_http_info()
	{
		return curl_getinfo($this->ch);
	}
	
	/**
	 * Get http response code	 
	 * @access public
	 * @return int
	 */
	function get_http_response_code()
	{
		return curl_getinfo($this->ch, CURLINFO_HTTP_CODE);
	}

	/**
	 * Return last error message and error number
	 * @return string error msg
	 * @access public
	 */
	function get_error_msg()
	{
		$err = "Error number: " .curl_errno($this->ch) ."\n";
		$err .="Error message: " .curl_error($this->ch)."\n";

		return $err;
	}
	
	/**
	 * 获得HTTP头信息
	 * 
	 * @return array;
	 */
	function get_http_head() {
		
		$httpArr = split("\n", $this->http_head);
		$len = count($httpArr);
		//print_r($httpArr);
		for( $i = 1; $i < $len; $i ++ ) {
			$t = split(":", $httpArr[$i]);
			$tmpArr[$t[0]] = $t[1];
		}
		
		preg_match("/(.*)name=(.*)description=(.*)throwable=(.*)uri=(.*)/", $httpArr[0], $matches);
		
		$code = split(" ", trim($matches[1]));
		
		$tmpArr["error"] = array(
			'code' => $code[1],
			'verson' => $code[0], 
			'name' => trim($matches[2]),
			'description' => trim($matches[3]),
			'throwable' => trim($matches[4]),
			'uri' => trim($matches[5])
		);
		unset($matches);
		unset($httpArr);
		
		return $tmpArr;
	}
	
	/**
	 * Close curl session and free resource
	 * Usually no need to call this function directly
	 * in case you do you have to call init() to recreate curl
	 * @access public
	 */
	function close()
	{
		//close curl session and free up resources
		curl_close($this->ch);
	}
}
?>