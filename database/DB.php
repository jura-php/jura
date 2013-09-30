<?php
class DB
{
	private static $connections = array();

	public static function init()
	{
		if (!defined("J_TP"))
		{
			$keys = array_keys(Config::group("databases"));
			if (count($keys) > 0)
			{
				$name = $keys[0];
				$config = Config::item("databases", $name);

				define("J_TP", $config["tablePrefix"]);
			}
		}
	}

	public static function conn($name = null)
	{
		if (is_null($name))
		{
			$keys = array_keys(Config::group("databases"));
			if (count($keys) > 0)
			{
				$name = $keys[0];
			}
		}

		if (!isset(static::$connections[$name]))
		{
			$config = Config::item("databases", $name);

			switch ($config["type"])
			{
				case "mysql":
					include_once(J_SYSTEMPATH . "database" . DS . "mysql" . DS . "MysqlDB" . EXT);
					include_once(J_SYSTEMPATH . "database" . DS . "mysql" . DS . "MysqlRecordSet" . EXT);

					static::$connections[$name] = new MysqlDB($config);

					break;
				default:
					trigger_error("Database type <b>'" . $config["type"] . "'</b> not suported.");

					break;
			}
		}

		return static::$connections[$name];
	}

	public static function __callStatic($method, $parameters)
	{
		return call_user_func_array(array(static::conn(), $method), $parameters);
	}
}
?>