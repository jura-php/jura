<?php
class URL
{
	/**
	 * Returns the full URL for the current request
	 * @return string
	 */
	public static function full()
	{
		return trim(Request::rootURL(), "/") . Request::pathInfo();
	}

	/**
	 * Returns the root URL of the project. If addManager = false, don't append 'manager/' if we are calling this function from a manager route.
	 * @param  boolean $addManager
	 * @return string
	 */
	public static function root($addManager = true)
	{
		$root = Request::rootURL();

		if ($addManager && URI::isManager())
		{
			$root .= "manager/";
		}

		return $root;
	}

	/**
	 * Returns a URL prepended by root()
	 * @param  string  $uri
	 * @param  boolean $addManager
	 * @return string
	 */
	public static function to($uri = "/", $addManager = true)
	{
		if (strpos($uri, static::root($addManager)) === 0)
		{
			$len = strlen(static::root($addManager));
			$uri = substr($uri, $len, strlen($uri) - $len);
		}

		$uri = trim($uri, "/");
		$append = "/";

		if (strpos($uri, ".") !== false || strpos($uri, "?") !== false)
		{
			$append = "";
		}

		return rtrim(static::root($addManager), "/") . "/" . (($uri != "") ? $uri . $append : "");
	}

	/**
	 * Return a force download URL to this path
	 * @param  string $path
	 * @return string
	 */
	public static function download($path)
	{
		$path = trim($path, "/");
		return rtrim(static::root(false), "/") . "/download/?path=" . $path;
	}

	/**
	 * Return a resized image URL
	 * @param  string  $path
	 * @param  integer $width
	 * @param  integer $height
	 * @param  string  $method
	 * @param  mixed  $background
	 * @return string
	 */
	public static function thumb($path, $width = 0, $height = 0, $method = "fit", $background = null)
	{
		$path = trim($path, "/");
		return rtrim(static::root(false), "/") . "/thumb/?path=" . urlencode($path) . "&width=" . $width . "&height=" . $height . "&method=" . $method . (!is_null($background) ? "&background=" . $background : "");
	}


}
?>