<?php
class Resources
{
	public static function allJS()
	{
		header("Content-Type: application/x-javascript; charset=utf-8");

		$files = array();
		$config = Config::item("application", "js");
		$path = J_APPPATH . "inc" . DS;

		if ($config == "*")
		{
			$listFiles = File::lsdir($path, ".js");
			foreach ($listFiles as $file)
			{
				$files[] = $path . $file;
			}
		}
		else
		{
			foreach ($config as $file)
			{
				$files[] = $path . $file;
			}
		}

		$data = "";
		$key = static::cacheKey($files);
		if (!($data = Cache::get($key)))
		{
			foreach ($files as $file)
			{
				$data .= File::get($file) . "\n";
			}

			//Remove comments
			$data = preg_replace('!/\*(.*?)\*/!s', '', $data);
			//Kill leading space
			$data = preg_replace('!\n\s+!', "\n", $data);

			Cache::remove($key, true);
			Cache::save($key, $data);
		}

		return $data;
	}

	public static function allCSS()
	{
		header("Content-Type: text/css; charset=utf-8");

		$files = array();
		$config = Config::item("application", "css");
		$path = J_APPPATH . "inc" . DS;

		if ($config == "*")
		{
			$listFiles = File::lsdir($path, ".css");
			foreach ($listFiles as $file)
			{
				$files[] = $path . $file;
			}

			$listFiles = File::lsdir($path, ".less");
			foreach ($listFiles as $file)
			{
				$files[] = $path . $file;
			}
		}
		else
		{
			foreach ($config as $file)
			{
				$files[] = $path . $file;
			}
		}

		$data = "";
		$key = static::cacheKey($files);
		if (!($data = Cache::get($key)))
		{
			//Check included .less files
			$lessIncluded = array();
			$lessContents = array();
			foreach ($files as $file)
			{
				if (File::extension($file) == "less")
				{
					$filename = File::fileName($file);

					$lessIncluded[$file] = false;

					foreach ($files as $file2)
					{
						if (File::extension($file2) == "less")
						{
							if ($file2 != $file)
							{
								if (!array_key_exists($file2, $lessContents))
								{
									$fileContent = File::get($file2);
									$lessContents[$file2] = $fileContent;
								}
								else
								{
									$fileContent = $lessContents[$file2];
								}

								if (Str::contains($fileContent, $filename))
								{
									$lessIncluded[$file] = true;
									break;
								}
							}
						}
					}
				}
			}

			$lessContents = null;

			$less = new lessc;
			$less->setFormatter("compressed");

			foreach ($files as $file)
			{
				if (File::extension($file) == "less")
				{
					if (!$lessIncluded[$file])
					{
						$data .= $less->compileFile($file) . "\n";
					}
				}
				else
				{
					$data .= File::get($file) . "\n";
				}
			}

			//Remove comments
			$data = preg_replace('!/\*(.*?)\*/!s', '', $data);
			// Kill double spaces
			$data = ltrim(preg_replace('!\n+!', "\n", $data ));
			// Kill leading space
			$data = preg_replace('!\n\s+!', "\n", $data);

			$replacements = array(
				'!\s+!'                             => ' ',
				'!(\[)\s*|\s*(\])|(\()\s*|\s*(\))!' => '${1}${2}${3}${4}',  // Trim internal bracket WS
				'!\s*(;|,|\/|\!)\s*!'               => '$1',     // Trim WS around delimiters and special characters
			);
			$data = preg_replace(array_keys($replacements), array_values($replacements), $data);

			$data = str_replace("  ", " ", $data);

			Cache::remove($key, true);
			Cache::save($key, $data);
		}

		return $data;
	}

	private static function cacheKey($files)
	{
		$size = 0;
		$names = "";

		foreach ($files as $file)
		{
			$names .= basename($file) . "-";
			$size += File::modified($file) / 100000;
		}

		return URI::current() . md5($names . $size);
	}

}
?>