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

	/**
	 * Checks if the session has this key.
	 * @param  mixed  $key
	 * @return boolean
	 */
	public static function has($key)
	{
		static::init();

		return isset($_SESSION[$key]);
	}

	/**
	 * Sets the value binded to this key.
	 * @param mixed $key
	 * @param mixed $value
	 */
	public static function set($key, $value)
	{
		static::init();

		$_SESSION[$key] = $value;
	}

	/**
	 * Get the value binded to this key. If not found, default is returned.
	 * @param  mixed $key
	 * @param  mixed $default
	 * @return mixed
	 */
	public static function get($key, $default = null)
	{
		static::init();

		if (isset($_SESSION[$key]))
		{
			return $_SESSION[$key];
		}

		return $default;
	}

	/**
	 * Clears the key.
	 * @param  mixed $key
	 */
	public static function clear($key)
	{
		static::init();

		unset($_SESSION[$key]);
	}

	/**
	 * Clears the entire session.
	 */
	public static function clearAll()
	{
		static::init();

		foreach ($_SESSION as $k => $v)
		{
			unset($_SESSION[$k]);
		}
	}

	/**
	 * Set a flash value to be read only on the next request.
	 * Further requests won't see this anymore.
	 * @param mixed $key
	 * @param mixed $value
	 */
	public static function setFlash($key, $value)
	{
		$key = "flash:new:" . $key;
		static::set($key, $value);
	}

	/**
	 * Get a flash value. If not found, default is returned.
	 * @param  mixed $key
	 * @param  mixed $default
	 * @return mixed
	 */
	public static function getFlash($key, $default = null)
	{
		$key = "flash:old:" . $key;
		return static::get($key, $default);
	}

	/**
	 * Marks a flash value to stay alive until the next request.
	 * @param  mixed $key
	 */
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

	/**
	 * Clears the flash value related to this key.
	 * @param  mixed $key
	 */
	private static function clearFlash($key)
	{
		$key = "flash:old:" . $key;
		static::clear($key);

		$key = "flash:new:" . $key;
		static::clear($key);
	}

	/**
	 * Sets a cookie on the client with the value encoded using our unique key.
	 * @see  app/config/application.php
	 * @param string  $key
	 * @param string  $value
	 * @param integer $expire
	 * @param string  $path
	 */
	public static function setCookie($key, $value, $expire = 31536000, $path = '/')
	{
		setcookie($key, Crypt::encode($value), time() + $expire, $path);
		$_COOKIE[$key] = $value;
	}

	/**
	 * Get a client cookie related to this key. If not found, default is returned.
	 * @param  string $key
	 * @param  string $default
	 * @return mixed
	 */
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