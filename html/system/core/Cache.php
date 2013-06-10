<?php
class Cache
{
	private static $path;

	public static function init()
	{
		static::$path = J_APPPATH . "storage" . DS . "cache" . DS;

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

	public static function get($uri)
	{
		$path = static::$path . Str::toURIParam($uri) . ".cache";

		if (!File::exists($path))
		{
			return false;
		}

		$data = File::get($path);
		$data = unserialize($data);

		if (time() > $data["expirationTime"])
		{
			static::remove($path);

			return false;
		}

		return $data["data"];
	}

	//2 days of lifetime by default
	public static function save($uri, $data, $lifeTime = 172800)
	{
		$path = static::$path . Str::toURIParam($uri) . ".cache";

		static::remove($path);

		$content = array(
			"expirationTime" => time() + $lifeTime,
			"data" => $data
		);

		return File::put($path, serialize($content));
	}

	public static function remove($uri, $asMask = false)
	{
		$uri = Str::toURIParam($uri);

		if (!$asMask)
		{
			$path = static::$path . $uri . ".cache";

			return File::delete($path);
		}
		else
		{
			$deleted = false;
			$files = File::lsdir(static::$path, ".cache");

			foreach ($files as $file)
			{
				if (Str::contains($file, $uri))
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