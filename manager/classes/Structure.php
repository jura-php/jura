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
	}

	public static function modules()
	{
		static::loadModules();

		$config = array();

		//TODO: Return user login state..
		//TEMP
		/*$config["user"] = array(
				"name" => "Guilherme Medeiros",
				"gravatar_hash" => "1577c5579fd5b4c5c80aec42b1744728"
			);*/
		//----

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
				$className = $module["class"];

				include_once J_MANAGERPATH . "modules/" . $className . ".php";
				$c = new $className();
				$objects[] = $c;
				$module = $c->config($module);
			}

			static::$modules = $modules;
			static::$modulesObjects = $objects;
		}
	}
}
?>