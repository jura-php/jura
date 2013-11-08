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

	/**
	 * Returns a rote tha maches the method and URI
	 * @param  string $method
	 * @param  string $uri
	 * @return mixed
	 */
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

	public static function restful($uri, $name)
	{
		$methods = array(
			"restIndex#GET#/",
			"restGet#GET#/(:num)",
			"restNew#GET#/new",
			"restCreate#POST#/",
			"restUpdate#PUT#/(:num)",
			"restDelete#DELETE#/(:num)"
		);

		$uri = trim($uri, "/");

		$pieces = explode(DS, $name);
		$className = $pieces[count($pieces) - 1] = ucfirst(array_last($pieces)) . "Controller";
		$path = J_APPPATH . "controllers" . DS . trim(implode(DS, $pieces), DS) . EXT;

		if (Request::isLocal())
		{
			if (!File::exists($path))
			{
				trigger_error("File <b>" . $path . "</b> doesn't exists");
			}
		}

		require $path;

		$instance = new $className();

		foreach ($methods as $method)
		{
			$method = explode("#", $method);

			if (method_exists($instance, $method[0]))
			{
				Router::register($method[1], $uri . $method[2], $name . "@" . $method[0]);
			}
		}
	}

	/**
	 * Register a route with the router.
	 *
	 * You can enable the cache on any call just by setting enableCache as true. The expiration time is expressed in seconds, default is 2 days.
	 * 
	 * <code>
	 *		//Register a route with a callback function
	 *		Router::register("GET", "/", function() { return "Index!"; });
	 *
	 *		//Register multiple routes with a view
	 *		Router::register("GET", array("/", "index/"), "index");
	 *		
	 *		//Register a route with a controller's method
	 *		Router::register("GET,POST", "/", "controller@method");
	 * </code>
	 *
	 * @param  string        $method
	 * @param  string|array  $route
	 * @param  mixed         $action
	 * @param  bool          $enableCache
	 * @param  int           $cacheExpirationTime
	 * @return void
	 */
	public static function register($method, $route, $action, $enableCache = false, $cacheExpirationTime = 172800)
	{
		if (is_string($method) && Str::contains($method, ","))
		{
			$method = explode(",", $method);
			foreach ($method as $v)
			{
				self::register($v, $route, $action);
			}

			return;
		}

		if (is_string($route) && Str::contains($route, ","))
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

			if (empty($uri))
			{
				$uri = "/";
			}

			if ($uri{0} == "(")
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