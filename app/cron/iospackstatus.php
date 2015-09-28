<?php
/**
 * 批量打Android包运行程序（升级包后使用）
 *
 * @category   Leb
 * @package    Leb_Action
 * @author     lihui
 * @version    $Id: channel.php 2014-10-5 10:30:00 lihui
 * @copyright (c) 2014 dianjoy.com
 * @license
 */
class iospackstatusController extends Application
{
    private $versionModel;

    public function execute($plugins)
    {
        $this->versionModel = $this->loadAppModel('Version_set');

    }

    public function indexAction()
    {
    }

    public function cronAction()
    {
        $pack_list = $this->versionModel->query("SELECT * FROM a_package_log WHERE pk_os = 2;");

        foreach ($pack_list as $var)
        {
            $path = _ROOT_ . "package/";
            if (1 == $var['is_hidden_invite'] && !empty($var['uid']))
            {
                $var['c'] = 'c_' . $var['uid'];
            } elseif (1 == $var['is_hidden_invite'])
            {
                $var['uid'] = 'c';
            }

            $versionRe = $this->versionModel->getIosVersion();
            if (empty($versionRe['version']))
            {
                return array('status' => -1, 'error' => '版本号无法获取');
            }

            if (1 == intval($var['is_hidden_invite']) and $var['uid'] != 'c')
            {
                $ipaName = $this->pakName($var['channel'], $var['uid'], 'c') . "_" . trim($versionRe['version']) . ".ipa";
            } else if (0 == intval($var['is_hidden_invite']) and $var['uid'] == '0')
            {
                $ipaName = $this->pakName($var['channel'], '', '0') . "_" . trim($versionRe['version']) . ".ipa";
            } else if (0 == intval($var['is_hidden_invite']) and $var['uid'] != '0')
            {
                $ipaName = $this->pakName($var['channel'], $var['uid'], '0') . "_" . trim($versionRe['version']) . ".ipa";
            } else
            {
                $ipaName = $this->pakName($var['channel'], '', 'c') . "_" . trim($versionRe['version']) . ".ipa";
            }

            $sql = "UPDATE a_package_log SET status = 1 WHERE uid = '{$var['uid']}' AND channel = '{$var['channel']}';";
            $this->versionModel->query($sql);

            if ((file_exists($path . $ipaName) and filesize($path . $ipaName) > 4000000 and filesize($path . $ipaName) < 9000000))
            {
                $sql = "UPDATE a_package_log SET status = 1 WHERE uid = '{$var['uid']}' AND channel = '{$var['channel']}' AND pk_os = 2;";
                $this->versionModel->query($sql);

                var_dump($var['channel'] . " " . $var['uid']);
                var_dump($path . $ipaName);
                var_dump(filesize($path . $ipaName));

                echo '<br>';

            } else
            {
                $sql = "UPDATE a_package_log SET status = -1 WHERE uid = '{$var['uid']}' AND channel = '{$var['channel']}' AND pk_os = 2;";
                $this->versionModel->query($sql);

                echo "error  ";
                var_dump($var['channel'] . " " . $var['uid']);
                var_dump($path . $ipaName);
                var_dump(filesize($path . $ipaName));

                echo '<br>';

            }
        }
    }

    private function pakName($channel, $invite, $c = '')
    {
        if ($c == 'c' && !empty($invite))
        {
            $pakName = $channel . '-c_' . $invite;
        } elseif ($c == 'c')
        {
            $pakName = $channel . '-c';
        } elseif (!empty($invite))
        {
            $pakName = $channel . '-' . $invite;
        } else
        {
            $pakName = $channel;
        }
        return $pakName;
    }


}

