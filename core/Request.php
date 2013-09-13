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
			return Request::clearValue($v);
		}, $_POST);

		//Remove /public/index.html from path_info..
		foreach (array("PATH_INFO", "ORIG_PATH_INFO", "PATH_TRANSLATED", "PHP_SELF") as $k)
		{
			if (isset($_SERVER[$k]))
			{
				$_SERVER[$k] = str_replace("/public/index.html", "/", $_SERVER[$k]);
			}
		}

		static::$server = $_SERVER;
		static::$get = $_GET;
		static::$post = $_POST;

		$_GET = null;
		$_POST = null;
		$_SERVER = null;
		$_REQUEST = null;

		//Detect environment
		$list = require J_PATH . "config" . DS . "environments" . EXT;
		$env = "";
		$envWithWildcard = array_first($list);
		$hosts = array(array_get(static::$server, "HTTP_HOST", "localhost"), array_get(static::$server, "SERVER_NAME", "localhost"), array_get(static::$server, "SERVER_ADDR", "localhost"), gethostname());

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

		static::$env = $env;

		//Detect method
		$method = strtoupper(array_get(static::$server, "REQUEST_METHOD", "GET"));

		if ($method == "POST" && static::hasReq("_method"))
		{
			$methodReq = static::req("_method", "POST");

			if (array_search($methodReq, static::$availableMethods) !== false)
			{
				$method = $methodReq;
			}
		}

		static::$method = $method;
	}

	public static function env()
	{
		return static::$env;
	}

	public static function isLocal()
	{
		if (is_null(static::$isLocal))
		{
			static::$isLocal = (static::$env == J_LOCAL_ENV);
		}

		return static::$isLocal;
	}

	public static function isPreview()
	{
		if (is_null(static::$isPreview))
		{
			static::$isPreview = (static::$env == J_PREVIEW_ENV);
		}

		return static::$isPreview;
	}

	public static function method()
	{
		return static::$method;
	}

	public static function get($key, $default = "")
	{
		if (isset(static::$get[$key]))
		{
			return static::$get[$key];
		}
		else
		{
			return $default;
		}
	}

	public static function hasGet($key)
	{
		return isset(static::$get[$key]);
	}

	public static function post($key, $default = "")
	{
		if (isset(static::$post[$key]))
		{
			return static::$post[$key];
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
		$has = isset(static::$post[$key]);

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
		return static::post($key, static::get($key, $default));
	}

	public static function hasReq($key)
	{
		return static::hasGet($key) || static::hasPost($key);
	}

	public static function pathInfo()
	{
		if (!is_null(static::$pathInfo))
		{
			return static::$pathInfo;
		}

		$pathInfo = array_get(static::$server, "PATH_INFO", "/");

		if (empty($pathInfo) || $pathInfo == "/")
		{
			$pathInfo = array_get(static::$server, "ORIG_PATH_INFO", "/");
		}

		if (empty($pathInfo))
		{
			$pathInfo = "/";
		}

		static::$pathInfo = $pathInfo;

		return $pathInfo;
	}

	public static function ip()
	{
		return array_get(static::$server, "REMOTE_ADDR", "");
	}

	public static function fullURI()
	{
		return array_get(static::$server, "REQUEST_URI", "");
	}

	public static function rootURL()
	{
		if (is_null(static::$rootURL))
		{
			$protocol = strtolower(array_get(static::$server, "SERVER_PROTOCOL", "http"));
			$protocol = substr($protocol, 0, strpos($protocol, "/")) . (static::isSecure() ? "s" : "");

			$port = array_get(static::$server, "SERVER_PORT", "80");
			$port = ($port == "80") ? "" : (":" . $port);

			$uri = static::fullURI();
			$pathInfo = static::pathInfo();
			if ($pathInfo != "/")
			{
				$uri = substr($uri, 0, strpos($uri, $pathInfo));
			}

			static::$rootURL = Str::finish($protocol . "://" . array_get(static::$server, "SERVER_NAME", "localhost") . $port . $uri, "/");
		}

		return static::$rootURL;
	}

	public static function isSecure()
	{
		if (is_null(static::$isSecure))
		{
			static::$isSecure = isset(static::$server["HTTPS"]) && (static::$server["HTTPS"] == "on" || static::$server["HTTPS"] == 1);
		}

		return static::$isSecure;
	}

	public static function credentials()
	{
		if (!isset(static::$server["PHP_AUTH_USER"]) || !isset(static::$server["PHP_AUTH_PW"]))
		{
			return false;
		}

		return array(static::$server["PHP_AUTH_USER"], static::$server["PHP_AUTH_PW"]);
	}

	public static function clearValue($value)
	{
		if (is_array($value))
		{
			array_map(function ($v) {
				return static::clearValue($v);
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
		return static::$availableMethods;
	}
}

?>