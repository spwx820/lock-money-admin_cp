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
 */
class File {
	public static function init ($file) {
		return function ($info, $buffered = false) use ($file) {
			$f = fopen ($file, 'a+');
			if (! $f) {
				throw new \LogicException ('Could not open file for writing');
			}
	
			if (! flock ($f, LOCK_EX | LOCK_NB)) {
				throw new \RuntimeException ('Could not lock file');
			}
	
			fwrite ($f, ($buffered)
				? $info
				: vsprintf (\Leb_Analog::$format, $info));
			flock ($f, LOCK_UN);
			fclose ($f);
		};
	}
}