<?php

namespace Analog;

/**
 * Append to the specified log file. Does the same thing as the default
 * handling.
 *
 * Usage:
 *
 *     $log_file = 'log.txt';
 *     Leb_Analog::handler (\Leb_Analog\File::init ($log_file));
 *     
 *     Leb_Analog::log ('Log me');
 *
 * Note: Uses Leb_Analog::$format for the appending format.
 * Need Couchbase 'applog' bucket
 */

class Couchbase {
	public static function init ($pcacher = null, $expire = 0) {
		return function ($info, $buffered = false) use ($pcacher, $expire) {
            if (!$pcacher) {
                $pcacher = \Leb_Dao_Memcache::getInstance('applog');
            }
			if (! $pcacher) {
				throw new \LogicException ('Invalid cacher object.');
			}
            
            // 8 = 4 + 2 + 2
            //     t + p + c
            // 64 = 32 + 10 + 15 + 7
            //     t + mt + p + c
            $bptime = explode(' ', microtime(false));
            $pid = function_exists('posix_getpid') ? posix_getpid() : getmypid();
            // $uid = (time() << 16 | ($pid % 0xFFFF)) << 16 | ((++ \Leb_Analog::$counter) % 0xFFFF);
            $uid = ($bptime[1] << 10) | intval($bptime[0] * 1000);
            $uid = $uid << 15 | ($pid % 0xFFFE);
            $uid = $uid << 7 | ((++ \Leb_Analog::$counter) % 0xFE);
            
            // $uuid = sprintf('%u', $uid);
            // var_dump($bptime[1], intval($bptime[0] * 1000), $pid, \Leb_Analog::$counter, $uid, $uuid);
            // exit;

            $bret = true;
            $bret = $pcacher->set($uid, json_encode($info), array('expire'=>$expire));
            if ($bret) {
                return $uid;
            }
            return $bret;
		};
	}
}