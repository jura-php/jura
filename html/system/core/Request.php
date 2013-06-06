<?php
class Request
{
	private static $availableMethods = array("GET", "PUT", "POST", "DELETE");

	private static $env;
	private static $method;
	private static $pathInfo = null;
	private static $isSecure = null;
	private static $isLocal = null;
	public static $route = null;

	private static $get;
	private static $post;
	private static $server;

	public static function init()
	{
		//Sanitize inputs
		//.Remove magic quotes
		if (magic_quotes())
		{
			$magics = array(&$_GET, &$_POST, &$_COOKIE, &$_REQUEST);

			foreach ($magics as &$magic)
			{
				$magic = array_strip_slashes($magic);
			}
		}

		//.Unset globals
		foreach (array($_GET, $_POST) as $global)
		{
			if (is_array($global))
			{
				foreach ($global as $k => $v)
				{
					global $$k;
					$$k = NULL;
				}
			}
		}

		//.Clean post input
		array_map(function ($v) {
			return self::clearValue($v);
		}, $_POST);

		self::$server = $_SERVER;
		self::$get = $_GET;
		self::$post = $_POST;

		$_GET = null;
		$_POST = null;
		$_SERVER = null;

		//Detect environment
		$list = require J_APPPATH . "config" . DS . "environments" . EXT;
		$host = self::$server["HTTP_HOST"];
		$host2 = gethostname();
		$env = "";
		$envWithWildcard = array_first($list);

		foreach ($list as $k => $v)
		{
			foreach ((array)$v as $hostname)
			{
				if ($hostname != "" && ($hostname == $host || $hostname == $host2))
				{
					$env = $k;

					break;
				}
				else if ($hostname == "*")
				{
					$envWithWildcard = $k;
				}
			}

			if ($env != "")
			{
				break;
			}
		}

		if ($env == "")
		{
			$env = $envWithWildcard;
		}

		self::$env = $env;

		//Detect method
		$method = strtoupper(array_get(self::$server, "REQUEST_METHOD", "GET"));

		if ($method == "POST" && self::hasReq("_method"))
		{
			$methodReq = self::req("_method", "POST");

			if (array_search($methodReq, self::$availableMethods) !== false)
			{
				$method = $methodReq;
			}
		}

		self::$method = $method;
	}

	public static function env()
	{
		return self::$env;
	}

	public static function isLocal()
	{
		if (is_null(self::$isLocal))
		{
			self::$isLocal = (self::$env == J_LOCAL_ENV);
		}

		return self::$isLocal;
	}

	public static function method()
	{
		return self::$method;
	}

	public static function get($key, $default = "")
	{
		if (isset(self::$get[$key]))
		{
			return self::$get[$key];
		}
		else
		{
			return $default;
		}
	}

	public static function hasGet($key)
	{
		return isset(self::$get[$key]);
	}

	public static function post($key, $default = "", $checkPayload = false)
	{
		if (isset(self::$post[$key]))
		{
			return self::$post[$key];
		}
		else if ($checkPayload)
		{
			$payload = @file_get_contents('php://input');
            if ($payload && $payload = json_decode($payload))
            {
                    if (isset($payload->{$key}))
                    {
                    	return $payload->{$key};
                    }
            }
		}

		return $default;
	}

	public static function hasPost($key)
	{
		return isset(self::$post[$key]);
	}

	public static function req($key, $default = "")
	{
		return self::post($key, self::get($key, $default));
	}

	public static function hasReq($key)
	{
		return self::hasGet($key) || self::hasPost($key);
	}

	public static function pathInfo()
	{
		if (!is_null(self::$pathInfo))
		{
			return self::$pathInfo;
		}

		$pathInfo = array_get(self::$server, "PATH_INFO", "/");

		if ($pathInfo == "")
		{
			$pathInfo = "/";
		}

		self::$pathInfo = $pathInfo;

		return $pathInfo;
	}

	public static function ip()
	{
		return array_get(self::$server, "REMOTE_ADDR", "");
	}

	public static function fullURI()
	{
		return array_get(self::$server, "REQUEST_URI", "");
	}

	public static function isSecure()
	{
		if (is_null(self::$isSecure))
		{
			self::$isSecure = isset(self::$server["HTTPS"]) && (self::$server["HTTPS"] === "On" || self::$server["HTTPS"] == 1);
		}

		return self::$isSecure;
	}

	private static function clearValue($value)
	{
		if (is_array($value))
		{
			array_map(function ($v) {
				return self::clearValue($v);
			}, $value);

			return $value;
		}

		//Remove control chars
		$value = str_remove_invisible($value);

		//Standardize newlines
		if (strpos($value, "\r") !== false)
		{
			$value = str_replace(array("\r\n", "\r", "\r\n\n"), CRLF, $value);
		}

		return $value;
	}

	public static function availableMethods()
	{
		return self::$availableMethods;
	}
}

?>