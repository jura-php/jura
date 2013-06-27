<?php
class Cache
{
	private static $path;
	private static $expirationTime = -1;

	public static function init()
	{
		static::$path = J_APIPATH . "storage" . DS . "cache" . DS;

		if (Request::isLocal())
		{
			if (!File::exists(static::$path))
			{
				echo "Directory <b>" . static::$path . "</b> doesn't exists."; //TODO: Put this on the Error class.
				die();
			}
			else if (!is_writable(static::$path))
			{
				echo "Directory <b>" . static::$path . "</b> is not writable."; //TODO: Put this on the Error class.
				die();
			}
		}
	}

	public static function get($key)
	{
		$path = static::$path . Str::toURIParam($key) . ".cache";

		if (!File::exists($path))
		{
			return false;
		}

		$data = File::get($path);

		if (time() > substr($data, 0, 10))
		{
			static::remove($path);

			return false;
		}

		return substr($data, 10);
	}

	//2 days of expirationTime by default
	public static function save($key, $data, $expirationTime = 172800)
	{
		$path = static::$path . Str::toURIParam($key) . ".cache";

		static::remove($path);

		return File::put($path, (time() + $expirationTime) . $data);
	}

	public static function remove($key, $asMask = false)
	{
		$key = Str::toURIParam($key);

		if (!$asMask)
		{
			$path = static::$path . $key . ".cache";

			return File::delete($path);
		}
		else
		{
			$deleted = false;
			$files = File::lsdir(static::$path, ".cache");

			foreach ($files as $file)
			{
				if (Str::contains($file, $key))
				{
					File::delete(static::$path . $file);
					$delete = true;
				}
			}

			return $deleted;
		}
	}
}
?>