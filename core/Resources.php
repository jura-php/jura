<?php
class Resources
{
	public static function allJS()
	{
		header("Content-Type: application/x-javascript; charset=utf-8");

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
		header("Content-Type: text/css; charset=utf-8");

		$files = array();

		$path = J_SYSTEMPATH . "inc" . DS;
		$files[] = $path . "reset.css";
		$files[] = $path . "pure.grids.css";

		if (URI::isManager())
		{
			//TODO:

		}
		else
		{
			$path = J_APPPATH . "inc" . DS;

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

		$data = "";
		if (!($data = static::cacheData($files)))
		{
			$key = URI::current();
			$size = 0;

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

									$size += File::modified($file2) / 100000;
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

				$size += File::modified($file) / 100000;
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

			$key .= md5($size);

			Cache::save($key, $data); //TODO: Uncomment this
		}

		return $data;
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