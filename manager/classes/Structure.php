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

			if (Request::isLocal())
			{
				if (@DB::query("select id from " . J_TP . "manager_users LIMIT 1;")->success === false)
				{
					DB::query("CREATE TABLE `" . J_TP . "manager_users` (
								`id` int(11) unsigned NOT NULL AUTO_INCREMENT,
								`name` varchar(255) DEFAULT NULL,
								`email` varchar(255) DEFAULT NULL,
								`username` varchar(255) DEFAULT NULL,
								`password` varchar(40) DEFAULT NULL,
								`active` int(11) DEFAULT NULL,
								PRIMARY KEY (`id`)
							) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

					$user = ORM::make("manager_users");
					$user->name = "Joy Interactive";
					$user->email = "dev@joy-interactive.com";
					$user->username = "joy";
					$user->password = "202cb962ac59075b964b07152d234b70";
					$user->active = 1;
					$user->save();
				}

				if (@DB::query("select id from " . J_TP . "manager_tokens LIMIT 1;")->success === false)
				{
					DB::query("CREATE TABLE `" . J_TP . "manager_tokens` (
								`id` int(40) NOT NULL AUTO_INCREMENT,
								`userID` int(11) DEFAULT NULL,
								`token` varchar(100) DEFAULT NULL,
								`expirationDate` datetime DEFAULT NULL,
								PRIMARY KEY (`id`)
							) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");
				}
				
			}

			$config = array();
			$config["api_url"] = URL::to("api/");

			return "window.config = " . json_encode($config);
		});

		Router::register("GET", "manager/api/structure/", function () {
			return Response::json(Structure::modules());
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

		Router::register("GET", "manager/api/customJS/", function () {
			$path = J_MANAGERPATH . "custom.js";
			if (file_exists($path))
			{
				return File::get($path);
			}
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
				if ((is_string($module) && $module = "separator") || (array_search("separator", $module) !== false))
				{
					$module = array(
						"menu" => "side",
						"type" => "separator"
					);
				}
				else if (isset($module["class"]))
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
			}

			static::$modules = $modules;
			static::$modulesObjects = $objects;
		}
	}
}
?>