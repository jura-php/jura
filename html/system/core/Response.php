<?php
class Response
{
	public static function accessControl($allow = "*")
	{
		header("Access-Control-Allow-Origin: " . $allow);
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