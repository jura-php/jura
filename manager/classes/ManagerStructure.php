<?php
class ManagerStructure
{
	public static function routes()
	{

		//TODO: Load routes here

		Router::register("GET", "manager/api/config/", function () {
			return Response::json(static::config());
		});
	}

	public static function config()
	{
		$modules = include J_APIPATH . "manager/config/modules.php";

		//TODO: Return menu and modules structures...

		$config = array();

		return $config;
	}
}
?>