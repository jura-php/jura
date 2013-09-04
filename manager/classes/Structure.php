<?php
class Structure
{
	private static $modules = null;
	private static $modulesObjects = null;

	public static function routes()
	{
		static::loadModules();

		foreach (static::$modulesObjects as $object)
		{
			$object->routes();
		}

		//Load login routes.. login, logoff, etc..

		Router::register("GET", "manager/api/config/", function () {
			header("Content-Type: text/javascript; charset=utf-8");

			$config = array();
			$config["api_url"] = URL::to("api/");

			return "window.config = " . json_encode($config);
		});

		Router::register("GET", "manager/api/structure/", function () {
			return Response::json(static::modules());
		});

		Router::register("POST", "manager/api/token/", function () {
			return User::generateToken();
		});

		Router::register("POST", "manager/api/token/renew/", function () {
			return User::renewToken();
		});

		Router::register("GET", "manager/api/logout/", function () {
			return User::logout();
		});
	}

	public static function modules()
	{
		static::loadModules();

		$config = array();

		$config["user"] = User::profile();

		$config["modules"] = static::$modules;

		return $config;
	}

	private static function loadModules()
	{
		if (is_null(static::$modules))
		{
			$modules = include J_MANAGERPATH . "config/modules.php";
			$objects = array();

			foreach ($modules as &$module)
			{
				if (isset($module["class"]))
				{
					$className = $module["class"];

					include_once J_MANAGERPATH . "modules/" . $className . ".php";

					if (isset($module["params"]))
					{
						$c = new $className($module["params"]);
					}
					else
					{
						$c = new $className();
					}

					$objects[] = $c;
					$module = $c->config($module);
				}
				else if (array_search("separator", $module) !== false)
				{
					$module = array(
						"menu" => "side",
						"type" => "separator"
					);
				}
			}

			static::$modules = $modules;
			static::$modulesObjects = $objects;
		}
	}
}
?>