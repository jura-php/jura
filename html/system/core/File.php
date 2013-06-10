<?php

use FilesystemIterator as fIterator;

class File
{
	public static function exists($path)
	{
		return file_exists($path);
	}

	public static function extension($path)
	{
		if (file_exists($path))
		{
			return pathinfo($path, PATHINFO_EXTENSION);
		}

		$pieces = explode(".", $path);
		if (sizeof($pieces) > 1)
		{
			return strtolower(end($pieces));
		}
		else
		{
			return false;
		}
	}

	public static function removeExtension($path)
	{
		$extension = static::extension($path);

		if ($extension)
		{
			$len = strlen($path);
			$extensionLen = strlen($extensionLen);

			if ($len > $extensionLen)
			{
				$path = substr($path, 0, $len - ($extensionLen + 1));
			}
		}

		return $path;
	}

	public static function fileName($path)
	{
		return basename($path);
	}

	public static function type($path)
	{
		return filetype($path);
	}

	public static function size($path)
	{
		static::permission($path);

		return filesize($path);
	}

	public static function modified($path)
	{
		return filemtime($path);
	}

	public static function permission($path)
	{
		//TODO: Enable/disable this on configuration file

		if (file_exists($path))
		{
			return @chmod($path, 0777);
		}

		return false;
	}

	public static function get($path, $default = null)
	{
		if (file_exists($path))
		{
			return file_get_contents($path);
		}

		return value($default);
	}

	public static function put($path, $data)
	{
		static::permission($path);

		return file_put_contents($path, $data, LOCK_EX);
	}

	public static function append($path, $data)
	{
		static::permission($path);

		return file_put_contents($path, $data, LOCK_EX | FILE_APPEND);
	}

	public static function delete($path)
	{
		if (file_exists($path))
		{
			static::permission($path);

			return @unlink($path);
		}
	}

	public static function move($path, $target)
	{
		static::permission($path);

		return rename($path, $target);
	}

	public static function copy($path, $target)
	{
		static::permission($path);

		return copy($path, $target);
	}

	public static function dirName($path)
	{
		return static::formatDir(pathinfo($path, PATHINFO_DIRNAME));
	}

	public static function formatDir($path)
	{
		return Str::finish($path, DS);
	}

	public static function lsdir($path, $mask = "", $options = fIterator::SKIP_DOTS)
	{
		$files = array();
		$items = new fIterator($path, $options);

		foreach ($items as $item)
		{
			$itemFileName = $item->getBasename();

			if (($mask == "") || (Str::contains($itemFileName, $mask)))
			{
				$files[] = $itemFileName;
			}
		}

		natsort($files);

		return $files;
	}

	public static function mkdir($path, $chmod = 0777)
	{
		return (!is_dir($path)) ? mkdir($path, $chmod, true) : true;
	}

	public static function mvdir($source, $destination, $options = fIterator::SKIP_DOTS)
	{
		static::permission($source);

		return static::cpdir($source, $destination, true, $options);
	}

	public static function cpdir($source, $destination, $delete = false, $options = fIterator::SKIP_DOTS)
	{
		if (!is_dir($source))
		{
			return false;
		}

		if (!is_dir($destination))
		{
			mkdir($destination, 0777, true);
		}

		$items = new fIterator($source, $options);

		foreach ($items as $item)
		{
			$location = $destination . DS . $item->getBasename();

			if ($item->isDir())
			{
				$path = $item->getRealPath();

				if (!static::cpdir($path, $location, $delete, $options))
				{
					return false;
				}

				if ($delete)
				{
					@rmdir($item->getRealPath());
				}
			}
			else
			{
				if(!copy($item->getRealPath(), $location))
				{
					return false;
				}

				if ($delete)
				{
					@unlink($item->getRealPath());
				}
			}
		}

		unset($items);
		if ($delete) @rmdir($source);

		return true;
	}

	public static function rmdir($directory, $preserve = false)
	{
		if (!is_dir($directory))
		{
			return;
		}

		static::permission($directory);

		$items = new fIterator($directory);

		foreach ($items as $item)
		{
			if ($item->isDir())
			{
				static::rmdir($item->getRealPath());
			}
			else
			{
				@unlink($item->getRealPath());
			}
		}

		unset($items);
		if (!$preserve)
		{
			@rmdir($directory);
		}
	}

	public static function cleandir($directory)
	{
		return static::rmdir($directory, true);
	}
}
?>