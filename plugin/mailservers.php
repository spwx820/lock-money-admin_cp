<?php

/**
 * DPool Mail Service
 * $Author: steel $
 * $Date: 2008-09-05 $
 * $Id: mailservers.php $
*/

class MailServers
{
    var $host;
    var $url;
    var $user;
    var $passwd;
    var $timeout;
    var $errornos;
    var $error_msg;

    /**
     *  @user
     *  @passwd
     *  @return      void
     */
    function MailServers($user = '', $passwd = '')
    {    
        $this->user     = $user;
        $this->passwd   = $passwd;

//         $this->timeout  = isset($_SERVER['SINASRV_DPMAIL_TIMEOUT']) ? $_SERVER['SINASRV_DPMAIL_TIMEOUT'] : 4;
//         $this->host     = isset($_SERVER['SINASRV_DPMAIL_HOST']) ? $_SERVER['SINASRV_DPMAIL_HOST'] : '10.44.6.21';
//         $this->url      = isset($_SERVER['SINASRV_DPMAIL_URL']) ? $_SERVER['SINASRV_DPMAIL_URL'] : 'http://10.44.6.21/mailservice/api.php';
        
        $this->timeout  = 4;
        $this->host     = '10.44.6.21';
        $this->url      = 'http://10.44.6.21/mailservice/api.php';
        $this->errornos = array();
    }

    /**
     *  The Send Action
     *  @from            The header's from
     *  @recipients      Recipients 
     *  @subject         The Email's subject
     *  @content         The Email's content
     *  @options         The other params(array)
     */
    function send($from = '', $recipients = '', $subject = '', $content = '', $options = array())
    { 
        if(empty($this->host))
        {
            $this->errornos[] = 101;
            return false;
        }
        if(empty($this->url))
        {
            $this->errornos[] = 102;
            return false;
        }
        if(empty($this->timeout))
        {
            $this->errornos[] = 103;
            return false;
        }
        if(is_array($from))
        {
            $from = urlencode(serialize($from));
        }
        else
        {
            $from = urlencode(serialize((array)$from));
        }

        if(is_array($recipients))
        {
            $recipients = urlencode(serialize($recipients));
        }
        else
        {
            $recipients = urlencode(serialize(array((array)$recipients)));
        }
        
        $subject = urlencode($subject);
        $content = urlencode($content);
        $options = urlencode(serialize((array)$options));
        $post = "from=$from&recipients=$recipients&subject=$subject&content=$content&options=$options";

        $result = $this->MOpen($this->url, 0, $post, '', false, '', $this->timeout, true);
        $res = unserialize($result);
        if($res && $res['status'] < 100)
        {   
            return true;
        }
        elseif($res && $res['status'] > 100)
        {
            $this->errornos[] = $res['status'];
            return false;
        }
        else
        {
            $this->errornos[] = 999;
            $this->error_msg = $result;
            return false;
        }
    }

    /**
     *  Socket Open
     *  @param string $url
     *  @param int $limit
     *  @param string $post
     *  @param string $cookie
     *  @param bool $bysocket
     *  @param string $ip
     *  @param int $timeout
     *  @param bool $block
     *  @return string
     */
    function MOpen($url,$limit = 0,$post = '',$cookie = '',$bysocket = FALSE,$ip = '',$timeout = 10,$block = TRUE)
    {
        $return = '';
        $matches = parse_url($url);
        
        $host = $matches['host'];
        $path = $matches['path'];
        if(isset($matches['query']))
        {
            $path .= '?'.$matches['query'];
            if(isset($matches['fragment']))$path .= '#'.$matches['fragment'] ;
        }
        $path = $matches['path'] ? $path : '/';
        $port = !empty($matches['port']) ? $matches['port'] : 80;
	    $referer = "http://" . $_SERVER['SERVER_NAME'] . "/" . trim($_SERVER['PHP_SELF'], '/');
        if($post)
        {
            $out = "POST $path HTTP/1.0\r\n";
            $out .= "Accept: */*\r\n";
            $out .= "Referer: $referer\r\n";
            $out .= "Accept-Language: zh-cn\r\n";
            //$out .= "Accept-Charset: iso-8859-1\r\n";
            $out .= "Content-Type: application/x-www-form-urlencoded\r\n";
//             $out .= "User-Agent: $_SERVER[HTTP_USER_AGENT]\r\n";
            $out .= "Host: $host\r\n";
            $out .= 'Content-Length: '.strlen($post)."\r\n";
            $out .= "Connection: Close\r\n";
            $out .= "Cache-Control: no-cache\r\n";
            $out .= "Cookie: $cookie\r\n\r\n";
            $out .= $post;
        }
        else
        {
            $out = "GET $path HTTP/1.0\r\n";
            $out .= "Accept: */*\r\n";
            $out .= "Referer: $referer\r\n";
            $out .= "Accept-Language: zh-cn\r\n";
            //$out .= "Accept-Charset: UTF-8\r\n";
            $out .= "User-Agent: $_SERVER[HTTP_USER_AGENT]\r\n";
            $out .= "Host: $host\r\n";
            $out .= "Connection: Close\r\n";
            $out .= "Cookie: $cookie\r\n\r\n";
        }
        
        $fp = @fsockopen(($ip ? $ip : $host), $port, $errno, $errstr, $timeout);
        if(!$fp)
        {
            return ''; //note $errstr : $errno \r\n
        }
        else
        {
            stream_set_blocking($fp, $block);
            stream_set_timeout($fp, $timeout);
            @fwrite($fp, $out);
            $status = stream_get_meta_data($fp);
            if(!$status['timed_out'])
            {
                while (!feof($fp))
                {
                    if(($header = @fgets($fp)) && ($header == "\r\n" ||  $header == "\n"))
                    {
                        break;
                    }
                }

                $stop = false;
                while(!feof($fp) && !$stop)
                {
                    $data = fread($fp, ($limit == 0 || $limit > 8192 ? 8192 : $limit));
                    $return .= $data;
                    if($limit)
                    {
                        $limit -= strlen($data);
                        $stop = $limit <= 0;
                    }
                }
            }
            @fclose($fp);
            return $return;
        }
    }

    /**
     *
     * @access  public
     * @return  string
     */
    function errno()
    {
        if (!empty($this->errornos))
        {
            $len = count($this->errornos) - 1;
            return $this->errornos[$len];
        }
        else
        {
            return NULL;
        }
    }

    /**
     *
     * @access  public
     * @param   int       $errorno
     * @return  string
     */
    function error($errorno = '')
    {
        if(empty($errorno))
        {
            $errorno = $this->errno();
        }
        
        switch($errorno)
        {
            case 101 :
                return 'HOST is empty !';
            case 102 :
                return 'URL is empty !';
            case 103 :
                return 'The \'timeout\' is not assigned!';
            case 104 :
                return 'The recipients is empty !';
            case 105 :
                return 'The From-User is empty !';
            case 106 :
                return 'The From-User is invalid !';
            case 107 :
                return 'The SERVER_NAME is invalid !';
            case 999 :
                return $this->error_msg;
            default  :
                return '';
        }
    }
}
?>