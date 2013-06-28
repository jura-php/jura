<?php
class URI
{
	private static $segments;
	private static $uri;
	private static $isManager;

	public static function full()
	{
		return Request::fullURI();
	}

	public static function current()
	{
		if (!is_null(self::$uri))
		{
			return self::$uri;
		}


		$uri = trim(Request::pathInfo(), "/");
		$uri = $uri ? $uri : "/";
		self::$uri = $uri;

		$segments = array_diff(explode("/", trim($uri, "/")), array(""));

		if (array_get($segments, 0) == "manager")
		{
			self::$isManager = true;
			array_shift($segments);
		}

		self::$segments = $segments;

		return $uri;
	}

	public static function is($pattern)
	{
		return Str::is($pattern, self::current());
	}

	public static function segment($index, $default = null)
	{
		self::current();

		return array_get(self::$segments, $index - 1, $default);
	}

	public static function segments()
	{
		self::current();

		return self::$segments;
	}

	public static function isManager()
	{
		self::current();

		return self::$isManager;
	}
}


?>