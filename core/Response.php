<?php
class Response
{
	public static function code($code = 200, $message = "")
	{
		if ($message == "")
		{
			switch ($code)
			{
				case 403:
					$message = "Forbidden";

					break;
				case 404:
					$message = "Not Found";

					break;
			}
		}

		header($code . " " . $message, true, $code);
	}

	public static function accessControlHeader($allow = "*")
	{
		header("Access-Control-Allow-Origin: " . $allow);
	}

	public static function json($data)
	{
		header("Content-Type: application/json; charset=utf-8");

		return json_encode($data);
	}

	public static function downloadContent($content, $name, $headers = array())
	{
		static::downloadHeader($name, $headers, strlen($content));

		echo $content;
	}

	public static function downloadHeader($name, $headers = array(), $length = 0)
	{
		$ext = File::extension($name);

		if ($ext == "")
		{
			$ext = "txt";
			$name .= ".txt";
		}

		$overHeaders = array(
			'Content-Description' => 'File Transfer',
			'Content-Type' => File::mime($ext),
			'Content-Transfer-Encoding' => 'binary',
			'Expires' => 0,
			'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
			'Pragma' => 'public',
			'Content-Disposition' => 'attachment; filename="' . str_replace('"', '\\"', $name) . '"'
		);

		if ($length > 0)
		{
			$overHeaders['Content-Length'] = $length;
		}

		$headers = array_merge($overHeaders, $headers);

		foreach ($headers as $k => $v)
		{
			header($k . ": " . $v);
		}
	}

	public static function download($path, $name = null, $headers = array())
	{
		if (!file_exists($path))
		{
			return Response::code(404);
		}

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
		$url = URL::to($route);
		//$url = Str::finish(URL::root() . $route, "/");

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