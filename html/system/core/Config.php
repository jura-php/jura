<?php
class Config
{
	public static function load($name, $loadGeneric = false)
	{
		//TODO: Carregar os configs de manager, se for..

		$envConfigPath = J_APPPATH . "config" . DS . strtolower(Request::env()) . DS . $name . EXT;
		$exists = file_exists($envConfigPath);
		$result = null;
		$resultEnv = null;

		if ($loadGeneric || !$exists)
		{
			$result = require J_APPPATH . "config" . DS . $name . EXT;
		}

		if ($exists)
		{
			$resultEnv = require $envConfigPath;
		}

		if ($result && is_array($result))
		{
			if ($resultEnv && is_array($resultEnv))
			{
				return array_merge($result, $resultEnv);
			}
			else
			{
				return $result;
			}
		}
	}
}
?>