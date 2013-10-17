<?php
class Config
{
	private static $items = array();

	public static function load($group, $save = true)
	{
		$paths = array();

		$paths[] = J_PATH . "config" . DS . $group . EXT;
		$paths[] = J_PATH . "config" . DS . strtolower(Request::env()) . DS . $group . EXT;

		if (URI::isManager())
		{
			$paths[] = J_MANAGERPATH . DS . "config" . DS . $group . EXT;
			$paths[] = J_MANAGERPATH . DS . "config" . DS . strtolower(Request::env()) . DS . $group . EXT;
		}
		else
		{
			$paths[] = J_APPPATH . "config" . DS . $group . EXT;
			$paths[] = J_APPPATH . "config" . DS . strtolower(Request::env()) . DS . $group . EXT;
		}

		$items = array();

		foreach ($paths as $path)
		{
			if (file_exists($path))
			{
				$result = require $path;

				if (is_array($result))
				{
					$items = array_merge($items, $result);
				}
			}
		}

		if (count($items) > 0)
		{
			if ($save)
			{
				static::$items[$group] = $items;
			}

			return $items;
		}
	}

	public static function loadOnce($group)
	{
		if (!isset(static::$items[$group]))
		{
			static::load($group);
		}
	}

	public static function group($group)
	{
		static::loadOnce($group);

		if (isset(static::$items[$group]))
		{
			return static::$items[$group];
		}

		return null;
	}

	public static function item($group, $name = false, $default = null)
	{
		if(!$name) {
			$name = $group;
			$group = "application";
		}

		static::loadOnce($group);

		$value = array_get(static::$items[$group], $name, null);
		if (!is_null($value))
		{
			return $value;
		}

		return $default;
	}
}
?>