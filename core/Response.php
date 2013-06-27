<?php
class Response
{
	public static function accessControlHeader($allow = "*")
	{
		header("Access-Control-Allow-Origin: " . $allow);
	}

	public static function json($data)
	{
		header("Content-Type: application/json; charset=utf-8");

		return json_encode($data);
	}

	public static function download($path, $name = null, $headers = array())
	{
		if (is_null($name))
		{
			$name = basename($path);
		}

		$ext = File::extension($name);
		if ($ext == "")
		{
			$ext = File::extension($path);
		}

		$headers = array_merge(array(
			'Content-Description' => 'File Transfer',
			'Content-Type' => File::mime(File::extension($path)),
			'Content-Transfer-Encoding' => 'binary',
			'Expires' => 0,
			'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
			'Pragma' => 'public',
			'Content-Length' => File::size($path),
			'Content-Disposition' => 'attachment; filename="' . str_replace('"', '\\"', $name) . '"'
		), $headers);

		foreach ($headers as $k => $v)
		{
			header($k . ": " . $v);
		}

		readfile($path);
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