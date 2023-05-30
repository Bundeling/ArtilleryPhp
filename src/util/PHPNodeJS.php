<?php

namespace ArtilleryPhp\util;

/**
 * PHP Node JS integration class
 *
 * @abstract Class is wrapper for executing JavaScript via NodeJS command line ("node")
 * @link https://github.com/dincek/PHPNodeJS/
 * @author Dean Gostiša <dean@black.si>
 * @access public
 * @license http://creativecommons.org/licenses/by-sa/3.0/ Creative Commons Attribution-ShareAlike 3.0 Unported (CC BY-SA 3.0)
 * @copyright (c) 2013, Dean Gostiša
 * @version 1.0
 * @example PHPNodeJSExample.php Examples for using this class
 */
class PHPNodeJS {

	private $NodePath;
	private $PHPNodeJSWrapper;
	private $debug = false;
	private $timeZone;

	/**
	 * Initialize PHPNodeJS
	 *
	 * @param boolean $debug Enable or disable debug messages
	 */
	public function __construct($debug = false, $timeZone = 'Europe/Ljubljana') {
		$this->debug = $debug;
		$this->timeZone = $timeZone;
		date_default_timezone_set($this->timeZone);
		if ($this->debug) {
			self::DebugMsg('Searching for path of executable Node.JS ("node")...');
		}
		$this->NodePath = trim(shell_exec('which node'));
		if (!file_exists($this->NodePath)) {
			self::DebugMsg('Node.JS is not installed on server. Please fix that.');
			die();
		}
		if ($this->debug) {
			self::DebugMsg('Path of Node.JS: ' . $this->NodePath);
			self::DebugMsg('Current Execution Path: ' . dirname(__FILE__) . '/');
			self::DebugMsg();
		}
	}

	/**
	 * Run JavaScript
	 *
	 * @param string    $javascript_code    JavaScript Code To Run
	 * @param string    $function_name      JavaScript Function To Call [optional]
	 * @param array     $args JavaScript    Function Arguments [optional]
	 * @param boolean   $jQuery             Load jQuery library [defailt: false]
	 *
	 * @return string   Result of executing JavaScript
	 */
	public function run($javascript_code, $function_name = '', $args = array(), $jQuery = false) {
		$tmpFile = tempnam(dirname(__FILE__), 'PHPNodeJSWrapper');
		$this->PHPNodeJSWrapper = $tmpFile . '.js';
		$this->SetJSWrapper($jQuery);
		for ($i = 0; $i < count($args); $i++) {
			$args[$i] = escapeshellarg($args[$i]);
		}
		if ($this->debug) {
			self::DebugMsg('Running Javascript with parameters:');
			self::DebugMsg('Javascript Code: ' . $javascript_code);
			if ($function_name != '') {
				self::DebugMsg('Calling JavaScript Function with Parameters: ' . $function_name . '(' . implode(', ', $args) . ')');
			}
			self::DebugMsg('Enabled special libs: jQuery(' . ($jQuery ? 'TRUE' : 'FALSE').')');
			self::DebugMsg();
		}
		$command = 'cd ' . dirname(__FILE__) . '/ && ' . $this->NodePath . ' ' . $this->PHPNodeJSWrapper . ' ';
		$command .= escapeshellarg($javascript_code);
		if ($function_name != '') {
			$command .= ' ' . escapeshellarg($function_name) . ' ' . escapeshellarg('[' . implode(', ', $args) . ']');
		}
		if ($this->debug) {
			self::DebugMsg('Executing shell command:');
			self::DebugMsg($command);
		}
		echo $command;
		$result = shell_exec($command);
		if ($this->debug) {
			self::DebugMsg('Result:');
			self::DebugMsg($result);
			self::DebugMsg();
		}
		$this->CleanJSWrapper();
		return $result;
	}

	/**
	 * Create JS Wrapper for executing JavaScript Code
	 *
	 * @param boolean $jQuery Load jQuery library [defailt: false]
	 */
	private function SetJSWrapper($jQuery = false) {
		if ($this->debug) {
			self::DebugMsg('Setting JS Wrapper for executing custom JavaScript function...');
			self::DebugMsg('JS Wrapper Path: ' . $this->PHPNodeJSWrapper);
		}
		ob_start();
		?>
		<script type="text/javascript">
			<?php
			if ($jQuery) {
			?>
            try {
                require.resolve("jquery");
            } catch (e) {
                console.error("jQuery is not found. Try: npm install jquery");
                process.exit(e.code);
            }
            var jQuery = require("jquery");
			<?php
			}
			?>

            function PHPNodeJSWrapper(func, func_name, args) {
                eval(func);
                if (func_name && func_name !== '') {
                    args_string = "[";
                    for (var i = 0; i < args.length; i++) {
                        args_string += "'" + args[i] + "'";
                        if (i !== (args.length - 1)) {
                            args_string += ",";
                        }
                    }
                    args_string += "]";
                    var call = func_name + ".apply(this, " + args_string + ");";
                    return eval(call);
                }
            }
            var function_code = process.argv[2];
            var function_name = '';
            if (process.argv[3]) {
                function_name = process.argv[3];
            }
            var arguments = [];
            if (process.argv[4]) {
                arguments = eval(process.argv[4]);
            }
            if (function_name !== '') {
                console.log(PHPNodeJSWrapper(function_code, function_name, arguments));
            } else {
                PHPNodeJSWrapper(function_code);
            }
		</script>
		<?php
		$data = strtr(ob_get_clean(), array('<script type="text/javascript">' => '', '</script>' => ''));
		file_put_contents($this->PHPNodeJSWrapper, $data);
		if ($this->debug) {
			self::DebugMsg('JS Wrapper prepared successfully.');
			self::DebugMsg();
		}
	}

	/**
	 * Cleanup JS Wrapper
	 */
	private function CleanJSWrapper() {
		if ($this->debug) {
			self::DebugMsg('Cleanup JS Wrapper...');
		}
		unlink($this->PHPNodeJSWrapper);
		unlink(mb_substr($this->PHPNodeJSWrapper, 0, -3, 'UTF-8'));
		$this->PHPNodeJSWrapper = null;
		if ($this->debug) {
			self::DebugMsg('Cleanup JS Wrapper is finished.');
			self::DebugMsg();
		}
	}

	/**
	 * Output Debug Message based on user interface (CLI, Apache)
	 *
	 * @param string $msg Debug Message
	 */
	private static function DebugMsg($msg = '') {
		if ($msg == '') {
			echo PHP_SAPI == 'cli' ? '' : '<br />', "\n";
		} else {
			echo date('Y-m-d H:i:s'), ' :: ', $msg, PHP_SAPI == 'cli' ? '' : '<br />', "\n";
		}
	}

}
