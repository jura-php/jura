<?php
class Session
{
	private static $initialized = false;

	private static function init()
	{
		if (!static::$initialized)
		{
			session_start();

			static::$initialized = true;

			//Clear all old flash data
			foreach ($_SESSION as $k => $v)
			{
				if (Str::contains($k, ":old:"))
				{
					static::clear($k);
				}
				/*$parts = explode(":old:", $k);
				if (is_array($parts) && count($parts) == 2)
				{
					$parts[1]
				}*/
			}

			//Set all new flash data as old, to be cleared on the next request
			foreach ($_SESSION as $k => $v)
			{
				$parts = explode(":new:", $k);
				if (is_array($parts) && count($parts) == 2)
				{
					$newKey = "flash:old:" . $parts[1];
					static::set($newKey, $v);
					static::clear($k);
				}
			}
		}
	}

	public static function has($key)
	{
		static::init();

		return isset($_SESSION[$key]);
	}

	public static function set($key, $value)
	{
		static::init();

		$_SESSION[$key] = $value;
	}

	public static function get($key, $default = null)
	{
		static::init();

		if (isset($_SESSION[$key]))
		{
			return $_SESSION[$key];
		}

		return $default;
	}

	public static function clear($key)
	{
		static::init();

		unset($_SESSION[$key]);
	}

	public static function clearAll()
	{
		static::init();

		foreach ($_SESSION as $k => $v)
		{
			unset($_SESSION[$k]);
		}
	}

	public static function setFlash($key, $value)
	{
		$key = "flash:new:" . $key;
		static::set($key, $value);
	}

	public static function getFlash($key, $default = null)
	{
		$key = "flash:old:" . $key;
		return static::get($key, $default);
	}

	public static function keepFlash($key)
	{
		$key = "flash:old:" . $key;
		$value = static::get($key);

		if (!is_null($value))
		{
			$key = "flash:new:" . $key;
			static::set($key, $value);
		}
	}

	private static function clearFlash($key)
	{
		$key = "flash:old:" . $key;
		static::clear($key);

		$key = "flash:new:" . $key;
		static::clear($key);
	}

	public static function setCookie($key, $value, $expire = 31536000, $path = '/')
	{
		setcookie($key, Crypt::encode($value), time() + $expire, $path);
		$_COOKIE[$key] = $value;
	}

	public static function getCookie($key, $default = null)
	{
		if (isset($_COOKIE[$key]))
		{
			return Crypt::decode($_COOKIE[$key]);
		}

		return $default;
	}
}
?>