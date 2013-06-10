<?php
class Route
{
	private $method;
	private $uri;
	private $action;
	private $controller;
	private $controllerAction;
	private $parameters;

	public function __construct($method, $uri, $action, $parameters = array())
	{
		$this->method = $method;
		$this->uri = $uri;
		$this->action = $action;

		$defaults = (array)array_get($action, 'defaults');
		if (count($defaults) > count($parameters))
		{
			$defaults = array_slice($defaults, count($parameters));
			$parameters = array_merge($parameters, $defaults);
		}

		$this->parameters = $parameters;
	}

	public function call()
	{
		//TODO: Call filters, cache etc..

		return $this->response();
	}

	public function response()
	{
		ob_start();

		$content = self::process($this);

		return ob_get_clean() . $content;
	}

	private static function process($route)
	{
		$uses = array_get($route->action, 'uses');

		if (!is_null($uses))
		{
			//controller
			if (strpos($uses, "@") > -1)
			{
				list($name, $method) = explode("@", $uses);

				$pieces = explode(DS, $name);
				$className = $pieces[count($pieces) - 1] = ucfirst(array_last($pieces)) . "Controller";
				$path = J_APPPATH . "controllers" . DS . trim(implode(DS, $pieces), DS) . EXT;

				if (Request::isLocal())
				{
					if (!File::exists($path))
					{
						echo "File <b>" . $path . "</b> doesn't exists"; //TODO: Put this on error class
						die();
					}
				}

				require $path;

				$class = new $className();

				if (Request::isLocal())
				{
					if (!method_exists($class, $method))
					{
						echo "Method <b>" . $method . "</b> doesn't exists on class <b>" . $className . "</b>"; //TODO: Put this on error class..
						die();
					}
				}

				return call_user_func_array(array(&$class, $method), $route->parameters);
			}
			//view
			else
			{
				$path = J_APPPATH . "views" . DS . $uses . EXT;

				if (Request::isLocal())
				{
					if (!File::exists($path))
					{
						echo "File <b>" . $path . "</b> doesn't exists"; //TODO: Put this on error class
						die();
					}
				}

				require $path;
			}
		}

		//closure function
		$handler = array_first($route->action, function($key, $value)
		{
			return is_callable($value);
		});

		if (!is_null($handler))
		{
			return call_user_func_array($handler, $route->parameters);
		}
	}
}

?>