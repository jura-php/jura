<?php
class Log
{
	/**
	 * Log an exception to the log file.
	 *
	 * @param  Exception  $e
	 * @return void
	 */
	public static function exception($e)
	{
		static::write("error", $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
	}

	/**
	 * Write a message to the log file.
	 *
	 * <code>
	 *		// Write an "error" message to the log file
	 *		Log::write('error', 'Something went horribly wrong!');
	 *
	 *		// Log an arrays data
	 *		Log::write('info', array('name' => 'Sawny', 'passwd' => '1234', array(1337, 21, 0)));
	 *      //Result: Array ( [name] => Sawny [passwd] => 1234 [0] => Array ( [0] => 1337 [1] => 21 [2] => 0 ) )
	 * </code>
	 *
	 * @param  string  $type
	 * @param  string  $message
	 * @return void
	 */
	public static function write($type, $message)
	{
		if (is_array($message) || is_object($message))
		{
			$message = print_r($message, true);
		}

		$trace = debug_backtrace();

		foreach($trace as $item)
		{
			if (isset($item["class"]) AND $item["class"] == __CLASS__)
			{
				continue;
			}

			$caller = $item;

			break;
		}

		$function = $caller["function"];
		if (isset($caller["class"]))
		{
			$class = $caller["class"] . "::";
		}
		else
		{
			$class = "";
		}

		File::mkdir(J_APPPATH . "storage" . DS . "logs" . DS);
		File::append(J_APPPATH . "storage" . DS . "logs" . DS . date("Y-m-d") . ".log", date("Y-m-d H:i:s")." ".Str::upper($type)." - " . $class . $function . " - " . $message . CRLF);
	}
}
?>