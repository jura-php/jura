<?php
class Config
{
	private static $items = array();

	public static function load($group, $loadGeneric = false, $storeItems = false)
	{
		//TODO: Carregar os configs de manager, se for..

		$envConfigPath = J_APPPATH . "config" . DS . strtolower(Request::env()) . DS . $group . EXT;
		$exists = file_exists($envConfigPath);
		$result = null;
		$resultEnv = null;

		if ($loadGeneric || !$exists)
		{
			$result = require J_APPPATH . "config" . DS . $group . EXT;
		}

		if ($exists)
		{
			$resultEnv = require $envConfigPath;
		}

		if ($result && is_array($result))
		{
			if ($resultEnv && is_array($resultEnv))
			{
				$result = array_merge($result, $resultEnv);
			}

			if ($storeItems)
			{
				self::$items[$group] = $result;
			}

			return $result;
		}
	}

	public static function item($group, $name)
	{
		if (isset(self::$items[$group][$name]))
		{
			return self::$items[$group][$name];
		}

		return false;
	}
}
?>