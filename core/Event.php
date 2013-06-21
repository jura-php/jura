<?php

define('J_EVENT_RESPONSE_START', 'J_EVENT_RESPONSE_START');
define('J_EVENT_RESPONSE_END', 'J_EVENT_RESPONSE_END');
define('J_EVENT_SHUTDOWN', 'J_EVENT_SHUTDOWN');

//TODO: Create and apply more events..

class Event
{
	private static $events = array();

	public static function listen($name, $callback)
	{
		static::$events[$name][] = $callback;
	}

	public static function hasListener($name)
	{
		return isset(static::$events[$name]);
	}

	public static function fire($names, $params = array())
	{
		$params = (array)$params;
		$responses = array();

		foreach ((array)$names as $name)
		{
			if (static::hasListener($name))
			{
				foreach (static::$events[$name] as $callback)
				{
					$responses[] = call_user_func_array($callback, $params);
				}
			}
		}

		return $responses;
	}
}
?>