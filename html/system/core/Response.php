<?php
class Response
{
	public static function accessControlHeader($allow = "*")
	{
		header("Access-Control-Allow-Origin: " . $allow);
	}

	public static function jsonHeader()
	{
		header("Content-Type: application/json; charset=utf-8");
	}

	public static function redirect($route = "/", $useJSFallback = true)
	{
		$url = Str::finish(URL::root() . $route, "/");

		if (!(@header("Location: " . $url)))
		{
			if ($useJSFallback)
			{
				echo "<script>if (window.parent) { window.parent.location = '" . $url . "'; } else { document.location = '" . $url . "'; }</script>";
				exit();
			}
		}
	}
}
?>