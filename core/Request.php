<?php
class Request
{
	private static $availableMethods = array("GET", "PUT", "POST", "DELETE", "PATCH", "HEAD", "OPTIONS");

	private static $env;
	private static $method;
	private static $rootURL = null;
	private static $pathInfo = null;
	private static $isSecure = null;
	private static $isLocal = null;
	private static $isPreview = null;
	public static $route = null;

	private static $get;
	private static $post;
	private static $server;

	private static $postPayload = null;

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

		//Remove /public/index.html from path_info..
		foreach (array("PATH_INFO", "PATH_TRANSLATED", "PHP_SELF") as $k)
		{
			if (isset($_SERVER[$k]))
			{
				$_SERVER[$k] = str_replace("/public/index.html", "/", $_SERVER[$k]);
			}
		}

		self::$server = $_SERVER;
		self::$get = $_GET;
		self::$post = $_POST;

		$_GET = null;
		$_POST = null;
		$_SERVER = null;
		$_REQUEST = null;

		//Detect environment
		$list = require J_PATH . "config" . DS . "environments" . EXT;
		$env = "";
		$envWithWildcard = array_first($list);
		$hosts = array(array_get(self::$server, "HTTP_HOST", "localhost"), gethostname());

		foreach ($hosts as $host)
		{
			foreach ($list as $k => $v)
			{
				foreach ((array)$v as $hostname)
				{
					if ($hostname != "" && ($hostname == $host))
					{
						$env = $k;

						break;
					}
					else if ($hostname == "*")
					{
						$envWithWildcard = $k;
					}
				}

				if (!empty($env))
				{
					break;
				}
			}

			if (!empty($env))
			{
				break;
			}
		}

		if (empty($env))
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

	public static function isPreview()
	{
		if (is_null(self::$isPreview))
		{
			self::$isPreview = (self::$env == J_PREVIEW_ENV);
		}

		return self::$isPreview;
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

	public static function post($key, $default = "")
	{
		if (isset(self::$post[$key]))
		{
			return self::$post[$key];
		}
		else
		{
			static::loadPostPayload();

			if (isset(static::$postPayload->{$key}))
			{
				return static::$postPayload->{$key};
			}
		}

		return $default;
	}

	public static function hasPost($key)
	{
		$has = isset(self::$post[$key]);

		if (!$has)
		{
			static::loadPostPayload();

			$has = isset(static::$postPayload->{$key});
		}

		return $has;
	}

	private static function loadPostPayload()
	{
		if (is_null(static::$postPayload))
		{
			$payload = @file_get_contents('php://input');
			if ($payload && $payload = json_decode($payload))
			{
				static::$postPayload = $payload;
			}
			else
			{
				static::$postPayload = new stdClass();
			}
		}
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

		if (empty($pathInfo))
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

	public static function rootURL()
	{
		if (is_null(self::$rootURL))
		{
			$protocol = strtolower(array_get(self::$server, "SERVER_PROTOCOL", "http"));
			$protocol = substr($protocol, 0, strpos($protocol, "/")) . (static::isSecure() ? "s" : "");

			$port = array_get(self::$server, "SERVER_PORT", "80");
			$port = ($port == "80") ? "" : (":" . $port);

			$uri = self::fullURI();
			$pathInfo = self::pathInfo();
			if ($pathInfo != "/")
			{
				$uri = substr($uri, 0, strpos($uri, $pathInfo));
			}

			self::$rootURL = Str::finish($protocol . "://" . array_get(self::$server, "SERVER_NAME", "localhost") . $port . $uri, "/");
		}

		return self::$rootURL;
	}

	public static function isSecure()
	{
		if (is_null(self::$isSecure))
		{
			self::$isSecure = isset(self::$server["HTTPS"]) && (self::$server["HTTPS"] == "on" || self::$server["HTTPS"] == 1);
		}

		return self::$isSecure;
	}

	public static function credentials()
	{
		if (!isset(self::$server["PHP_AUTH_USER"]) || !isset(self::$server["PHP_AUTH_PW"]))
		{
			return false;
		}

		return array(self::$server["PHP_AUTH_USER"], self::$server["PHP_AUTH_PW"]);
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
		$value = Str::removeInvisible($value);

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