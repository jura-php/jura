<?php
class Error
{
	public static function exception($exception, $trace = true)
	{
		static::log($exception);

		ob_get_level() and ob_end_clean();

		$message = $exception->getMessage();
		$file = $exception->getFile();
		$code = $exception->getCode();

		$response = "<html>\n<h2>Unhandled Exception</h2>\n<h3>Message:</h3>\n<pre>(" . $code . ") " . $message . "</pre>\n<h3>Location:</h3>\n<pre>" . $file . " on line " . $exception->getLine() . "</pre>\n";

		if ($trace)
		{
			$response .= "<h3>Stack Trace:</h3>\n<pre>" . $exception->getTraceAsString() . "</pre>\n";
		}
		$response .= "</html>";

		Response::code(500);

		if (Config::item("errors", "show", false))
		{
			echo $response;
		}

		if (Config::item("errors", "email", false) && !Request::isLocal() && !Request::isPreview())
		{
			$e = new Email();
			$e->from = Config::item("errors", "emailFrom", "dev@joy-interactive.com");
			$e->to = Config::item("errors", "emailTo", "dev@joy-interactive.com");
			$e->subject = URL::root() . " - erro!";
			$e->content = $response;
			$e->send();
		}

		return exit(1);
	}

	public static function native($code, $error, $file, $line)
	{
		if (error_reporting() === 0) return;

		$exception = new \ErrorException($error, $code, 0, $file, $line);

		if (in_array($code, Config::item("errors", "ignore", array())))
		{
			return static::log($exception);
		}

		static::exception($exception);
	}

	private static function log($exception)
	{
		if (Config::item("errors", "log", false))
		{
			Log::exception($exception);
		}
	}
}
?>