<?php
class Resources
{
	public static function allJS()
	{
		header("Content-Type: application/x-javascript; charset=utf-8");

		//TODO: Check every file size and date mod to generate a cache key. Use it to not cache a file that needs to be generated again..

		$files = array();

		$path = J_SYSTEMPATH . "inc" . DS;
		$files[] = $path . "jquery.js";

		if (URI::isManager())
		{
			//TODO:

		}
		else
		{
			$path = J_APPPATH . "inc" . DS;

			$listFiles = File::lsdir($path, ".js");
			foreach ($listFiles as $file)
			{
				$files[] = $path . $file;
			}
		}

		$data = "";
		if (!($data = static::cacheData($files)))
		{
			$key = URI::current();
			$size = 0;
			foreach ($files as $file)
			{
				$data .= File::get($file) . "\n";
				$size += File::modified($file) / 100000;
			}

			//Remove comments
			$data = preg_replace('!/\*(.*?)\*/!s', '', $data);
			//Kill leading space
			$data = preg_replace('!\n\s+!', "\n", $data);

			Cache::remove($key, true);

			$key .= md5($size);

			Cache::save($key, $data);
		}

		return $data;
	}

	public static function allCSS()
	{
		return "css?"; //TODO: ...
	}

	public static function cacheData($files)
	{
		$key = URI::current();
		$size = 0;

		foreach ($files as $file)
		{
			$size += File::modified($file) / 100000;
		}

		$key .= md5($size);

		return Cache::get($key);
	}

}
?>