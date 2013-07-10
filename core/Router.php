<?php
class Router
{
	private static $routes = array();
	private static $fallback = array();

	private static $patterns = array(
		'(:num)' => '([0-9]+)',
		'(:any)' => '([a-zA-Z0-9\.\-_%=]+)',
		'(:segment)' => '([^/]+)',
		'(:all)' => '(.*)',
	);

	public static $optional = array(
		'/(:num?)' => '(?:/([0-9]+)',
		'/(:any?)' => '(?:/([a-zA-Z0-9\.\-_%=]+)',
		'/(:segment?)' => '(?:/([^/]+)',
		'/(:all?)' => '(?:/(.*)',
	);

	public static function route($method, $uri)
	{
		Config::load("routes", false);

		$routes = (array)self::method($method);

		if (array_key_exists($uri, $routes))
		{
			$action = $routes[$uri];

			return new Route($method, $uri, $action);
		}

		if (!is_null($route = self::match($method, $uri)))
		{
			return $route;
		}
	}

	public static function register($method, $route, $action, $enableCache = false, $cacheExpirationTime = 172800)
	{
		if (Str::contains($method, ","))
		{
			$method = explode(",", $method);
			foreach ($method as $v)
			{
				self::register($v, $route, $action);
			}

			return;
		}

		if (Str::contains($route, ","))
		{
			$route = explode(",", $route);
			foreach ($route as $v)
			{
				self::register($method, $v, $action);
			}

			return;
		}

		foreach ((array)$route as $uri)
		{
			if ($method == "*")
			{
				$methods = Request::availableMethods();
				foreach ($methods as $v)
				{
					self::register($v, $route, $action);
				}

				continue;
			}

			$uri = trim($uri, "/");

			if ($uri == "")
			{
				$uri = "/";
			}

			if ($uri{0} == '(')
			{
				$routes =& self::$fallback;
			}
			else
			{
				$routes =& self::$routes;
			}

			if (!isset($routes[$method]))
			{
				$routes[$method] = array();
			}

			if (is_string($action))
			{
				$action = array("uses" => $action);
			}
			else if (is_callable($action))
			{
				$action = array("handler" => $action);
			}

			$action["cacheEnabled"] = $enableCache;
			$action["cacheExpirationTime"] = $cacheExpirationTime;

			$routes[$method][$uri] = (array)$action;
		}
	}

	private static function method($method)
	{
		$routes = array_get(self::$routes, $method, array());

		return array_merge($routes, array_get(self::$fallback, $method, array()));
	}

	private static function match($method, $uri)
	{
		foreach (self::method($method) as $route => $action)
		{
			if (Str::contains($route, "("))
			{
				list($search, $replace) = array_divide(self::$optional);

				$key = str_replace($search, $replace, $route, $count);

				if ($count > 0)
				{
					$key .= str_repeat(')?', $count);
				}

				$pattern = "#^" . strtr($key, self::$patterns) . "$#u";

				if (preg_match($pattern, $uri, $parameters))
				{
					return new Route($method, $route, $action, array_slice($parameters, 1));
				}
			}
		}
	}


}
?>