<?php
class ManagerStructure
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
			return Response::json(static::config());
		});
	}

	public static function config()
	{
		static::loadModules();

		//TODO: Return user login state..

		$config = array();

		$config["modules"] = static::$modules;

		return $config;
	}

	private static function loadModules()
	{
		if (is_null(static::$modules))
		{
			$modules = include J_APIPATH . "manager/config/modules.php";
			$objects = array();

			foreach ($modules as &$module)
			{
				$className = $module["class"];

				include_once J_APIPATH . "manager/modules/" . $className . ".php";
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