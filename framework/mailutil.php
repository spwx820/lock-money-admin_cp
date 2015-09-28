<?php

/**
 * 邮箱验证工具类
 * @author ziyuan@leju.com 
 * @version 1.0
 * @copyright leju.com, Inc, 1 November, 2012
 */

class MailUtil
{
    private $mxs = array();
    public $debug = false;

    public function getHost($email)
    {
        $email = trim(strtolower($email));
        $mailparts = explode("@", $email);
        $hostname = $mailparts[1];
        return $hostname;
    }

    public function get_mxrr($email)
    {
        $hostname = $this->getHost($email);

        if(isset($this->mxs[$hostname]) && $this->mxs[$hostname] && count($this->mxs[$hostname]) && is_array($this->mxs[$hostname]))
        {
            return $this->mxs[$hostname];
        }

        $this->mxs[$hostname] = 0;
        if(getmxrr($hostname, $mx_records, $mx_weight) && count($mx_weight) && count($mx_records))
        {
            $mxs = array();
            for($i=0; $i<count($mx_records); $i++){
                $mxs[$mx_weight[$i]] = $mx_records[$i];
            }

            ksort($mxs, SORT_NUMERIC);
            reset($mxs);
            if($mxs && count($mxs))
            {
                $this->mxs[$hostname] = $mxs;
            }
        }

        return $this->mxs[$hostname];
    }

    public function send_command($fp, $out)
    {
        fwrite($fp, $out . "\r\n");
        return $this->get_data($fp);
    }

    public function get_data($fp)
    {
        $s="";
        stream_set_timeout($fp, 2);
        for($i=0;$i<2;$i++)
            $s.=fgets($fp, 1024);
        return $s;
    }

    public function exists($email, $from='support@microsoft.com')
    {
        if(!$email || !preg_match('/^(\w)+(\.\w+)*@(\w)+((\.\w+)+)$/', $email))
        {
            return false;
        }

        $mxs = $this->get_mxrr($email);
        if(!$mxs || empty($mxs))
        {
            return false;
        }

        $hostname = $this->getHost($from);
        $b_server_found = false;
        foreach($mxs as $k => $item)
        {
            if($b_server_found)
            {
                break;
            }

            $this->info("valid {$email}");
            $this->info("connect to {$item}");

            //try connection on port 25
            $fp = @fsockopen($item, 25, $errno, $errstr, 2);
            if(!$fp)
            {
                $this->info("connect to {$item} failed");
                break;
            }

            $this->info("<=HELO {$hostname}");
            $ms_resp = "";
            // say HELO to mailserver
            $ms_resp .= $this->send_command($fp, "HELO {$hostname}");
            $this->info("=> {$ms_resp}");

            // initialize sending mail
            $this->info("<=MAIL FROM:<{$from}>");
            $ms_resp .= $this->send_command($fp, "MAIL FROM:<{$from}>");

            // try receipent address, will return 250 when ok..
            $this->info("<=RCPT TO:<{$email}>");
            $rcpt_text = $this->send_command($fp, "RCPT TO:<{$email}>");

            $ms_resp .= $rcpt_text;
            $this->info("==> {$ms_resp}");
            if(substr( $rcpt_text, 0, 3) == "250")
            {
                $b_server_found = true;
            }

            // quit mail server connection
            $ms_resp .= $this->send_command($fp, "QUIT");
            fclose($fp);
        }

        return $b_server_found;
    }

    private function info($str)
    {
        if($this->debug)
        {
            $time = date('H:i:s');
            $line = "[{$time}]\t{$str}\r\n";
            echo $line;
            $file = str_ireplace('.php', '.log', __FILE__);
            file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
        }
    }
}
