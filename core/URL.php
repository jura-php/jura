<?php
class URL
{
	public static function full()
	{
		return Request::rootURL() . Request::fullURI();
	}

	public static function root($addManager = true)
	{
		$root = Request::rootURL();

		if ($addManager && URI::isManager())
		{
			$root .= "manager/";
		}

		return $root;
	}

	public static function to($uri = "/", $addManager = true)
	{
		$uri = trim($uri, "/");
		return rtrim(static::root($addManager), "/") . "/" . (($uri != "") ? $uri . "/" : "");
	}

	public static function download($path)
	{
		$path = trim($path, "/");
		return rtrim(static::root(false), "/") . "/download/" . $path . "/";
	}

	public static function thumb($path, $width = 0, $height = 0, $method = "fit", $background = null)
	{
		$path = trim($path, "/");
		return rtrim(static::root(false), "/") . "/thumb/?path=" . urlencode($path) . "&width=" . $width . "&height=" . $height . "&method=" . $method . (!is_null($background) ? "&background=" . $background : "");
	}


}
?>