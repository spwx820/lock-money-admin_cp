<?php

/**
 * Analog - PHP 5.3+ logging class
 *
 * Copyright (c) 2012 Johnny Broadway
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

/**
 * A short and simple logging class for based on the idea of using closures for
 * configurability and extensibility. Functions as a static class, but you can
 * completely control the formatting and writing of log messages through closures.
 *
 * By default, this class will write to a file named /tmp/log.txt using a format
 * "machine - date - level - message\n".
 *
 * I wrote this because I wanted something simple and small like KLogger, and
 * preferably not torn out of a wider framework if possible. After searching,
 * I wasn't happy with the single-purpose libraries I found. With KLogger for
 * example, I didn't want an object instance but rather a static class, and I
 * wanted more flexibility in the back-end.
 *
 * I also found that the ones that had really flexible back-ends supported a lot
 * that I could never personally foresee needing, and could be easier to extend
 * with new back-ends that may be needed over time. Closures seem a natural fit for
 * this kind of thing.
 *
 * What about Analog, the logfile analyzer? Well, since it hasn't been updated
 * since 2004, I think it's safe to call a single-file PHP logging class the
 * same thing without it being considered stepping on toes :)
 *
 * Usage:
 *
 *     <?php
 *     
 *     require_once ('Analog.php');
 *     
 *     // Default logging to /tmp/analog.txt
 *     Leb_Analog::log ('Log this error');
 *     
 *     // Log to a MongoDB log collection
 *     Leb_Analog::handler (function ($info) {
 *         static $conn = null;
 *         if (! $conn) {
 *             $conn = new Mongo ('localhost:27017');
 *         }
 *         $conn->mydb->log->insert ($info);
 *     });
 *     
 *     // Log an alert
 *     Leb_Analog::log ('The sky is falling!', Leb_Analog::ALERT);
 *     
 *     // Log some debug info
 *     Leb_Analog::log ('Debugging info', Leb_Analog::DEBUG);
 *     
 *     ?>
 *
 * @package Analog
 * @author Johnny Broadway
 */
class Leb_Analog {
	/**
	 * List of severity levels.
	 */
	const URGENT   = 0; // It's an emergency
	const ALERT    = 1; // Immediate action required
	const CRITICAL = 2; // Critical conditions
	const ERROR    = 3; // An error occurred
	const WARNING  = 4; // Something unexpected happening
	const NOTICE   = 5; // Something worth noting
	const INFO     = 6; // Information, not an error
	const DEBUG    = 7; // Debugging messages

	/**
	 * The default format for log messages (machine, date, level, message)
	 * written to a file. To change the order of items in the string,
	 * use `%1$s` references.
	 */
	public static $format = "%s - %s - %d - %s\n";

	/**
	 * The method of saving the log output. See Analog::handler()
	 * for details on setting this.
	 */
	private static $handler = null;

	/**
	 * The name of the current machine, defaults to $_SERVER['SERVER_ADDR']
	 * on first call to format_message(), or 'localhost' if $_SERVER['SERVER_ADDR']
	 * is not set (e.g., during CLI use).
	 */
	public static $machine = null;

    public static $config = null;// array();
    public static $verbose = self::ERROR;
    public static $app = 'none';

    public static $counter = 0;

	/**
	 * Handler getter/setter. If no handler is provided, it will set it to
	 * sys_get_temp_dir() . '/analog.txt' as a default. Usage:
	 *
	 *    Leb_Analog::handler ('my_log.txt');
	 *
	 * Using a closure:
	 *
	 *     Leb_Analog::handler (function ($msg) {
	 *         return error_log ($msg);
	 *     });
 	 */

	public static function handler ($handler = false) {
		if ($handler) {
			self::$handler = $handler;
		} elseif (! self::$handler) {
            if (empty(self::$config)) {
                if (file_exists(dirname(__FILE__) . '/../config/log.php')) {
                    self::$config = include(dirname(__FILE__) . '/../config/log.php');
                }
            }
            if (empty(self::$config) || !isset(self::$config['type'])) {
                require_once('Analog/File.php');
                self::$handler = realpath (sys_get_temp_dir ()) . DIRECTORY_SEPARATOR . 'analog.txt';
            } else {
                if (isset(self::$config['app'])) {
                    self::$app = self::$config['app'];
                }
                $expire = '';
                if (isset(self::$config['expire'])) {
                    $expire = intval(self::$config['expire']);
                }

                switch (self::$config['type']) {
                case 'memcache':
                    require_once(dirname(__FILE__) . '/Analog/couchbase.php');
                    $cacher = \Leb_Dao_Memcache::getInstance('applog');
                    if (empty($expire)) {
                        self::$handler = \Analog\Couchbase::init($cacher);
                    } else {
                        self::$handler = \Analog\Couchbase::init($cacher, $expire);
                    }
                    break;
                case 'firephp':
                    require_once(dirname(__FILE__) . '/Analog/FirePHP.php');
                    self::$handler = \Analog\FirePHP::init();
                    break;
                default:
                    require_once('Analog/File.php');
                    self::$handler = realpath (sys_get_temp_dir ()) . DIRECTORY_SEPARATOR . 'analog.txt';                    
                }

                if (isset(self::$config['verbose'])) {
                    switch (self::$config['verbose']) {
                    case 'error':
                        break;
                    case 'info':
                        self::$verbose = self::INFO;
                        break;
                    case 'debug':
                        self::$verbose = self::DEBUG;
                        break;
                    }
                }

            }
		}
		return self::$handler;
	}

	/**
	 * Get the log info as an associative array.
	 */
	private static function get_struct ($message, $caty, $level) {
		if (self::$machine === null) {
			self::$machine = (isset ($_SERVER['SERVER_ADDR'])) ? $_SERVER['SERVER_ADDR'] : 'localhost';
		}

		$data = array (
            'app' => self::$app,
			'machine' => self::$machine,
			'date' => gmdate ('Y-m-d H:i:s'),
            'caty' => $caty,
			'level' => $level,
			'message' => $message,
		);

        if (isset($GLOBALS) && isset($GLOBALS['APPLICATION'])) {
            $data['APPLICATION'] = $GLOBALS['APPLICATION'];
            $data['CONTROLLER'] = $GLOBALS['CONTROLLER'];
            $data['ACTION'] = $GLOBALS['ACTION'];
        }

        if (isset($_SERVER['REMOTE_ADDR']) ) {
            $data['REMOTE_ADDR'] = $_SERVER['REMOTE_ADDR'];
        }

        // print_r($data);
        
        return $data;
	}

	/**
	 * Write a raw message to the log using a function or the default
	 * file logging.
	 */
	private static function write ($struct) {
   		// $handler = self::handler ();
        $handler = self::$handler;

		if (! $handler instanceof \Closure) {
			$handler = \Analog\File::init ($handler);
		}
		return $handler ($struct);
	}

	/**
	 * This is the main function you will call to log messages.
	 * Defaults to severity level Analog::ERROR.
	 * Usage:
	 *
	 *     Leb_Analog::log ('Debug info', 'user', Leb_Analog::DEBUG);
	 */
	public static function log ($message, $caty = 'none', $level = 3) {
        if (!self::$handler) self::handler();
        if ($level <= self::$verbose) {
            return self::write (self::get_struct ($message, $caty, $level));
        }
        return true;
	}
}