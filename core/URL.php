<?php
class URL
{
	public static function full()
	{
		return Request::rootURL() . URI::fullURI();
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

	public static function to($uri)
	{
		return rtrim(static::root(), "/") . "/" . trim($uri, "/") . "/";
	}


}
?>