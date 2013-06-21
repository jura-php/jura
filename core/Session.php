<?php
class Session
{
	private static $started = false;

	public static function checkStart()
	{
		if (!static::$started)
		{
			session_start();
			static::$started = true;
		}
	}

	public static function set($key, $value)
	{
		static::checkStart();

		$_SESSION[$key] = $value;
	}

	public static function get($key, $default = null)
	{
		static::checkStart();

		if (isset($_SESSION[$key]))
		{
			return $_SESSION[$key];
		}

		return $default;
	}

	public static function clear($key)
	{
		static::checkStart();

		unset($_SESSION[$key]);
	}

	public static function setCookie($key, $value, $expire = 31536000, $path = '/')
	{
		$encriptKey = Config::item("application", "key");

		echo $encriptKey . "-";

		setcookie($key, $value, time() + $expire, $path);
		$_COOKIE[$key] = $value;
	}

	public static function getCookie($key, $default = null)
	{
		if (isset($_COOKIE[$key]))
		{
			return $_COOKIE[$key];
		}

		return $default;
	}
}
?>