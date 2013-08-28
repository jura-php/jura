<?php

use FilesystemIterator as fIterator;

class File
{
	private static $mimeTypes = null;

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
			$extensionLen = strlen($extension);

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

	public static function mime($extension, $default = 'application/octet-stream')
	{
		if (is_null(static::$mimeTypes))
		{
			static::$mimeTypes = array(
				'hqx'   => 'application/mac-binhex40',
				'cpt'   => 'application/mac-compactpro',
				'csv'   => array('text/x-comma-separated-values', 'text/comma-separated-values', 'application/octet-stream'),
				'bin'   => 'application/macbinary',
				'dms'   => 'application/octet-stream',
				'lha'   => 'application/octet-stream',
				'lzh'   => 'application/octet-stream',
				'exe'   => array('application/octet-stream', 'application/x-msdownload'),
				'class' => 'application/octet-stream',
				'psd'   => 'application/x-photoshop',
				'so'    => 'application/octet-stream',
				'sea'   => 'application/octet-stream',
				'dll'   => 'application/octet-stream',
				'oda'   => 'application/oda',
				'pdf'   => array('application/pdf', 'application/x-download'),
				'ai'    => 'application/postscript',
				'eps'   => 'application/postscript',
				'ps'    => 'application/postscript',
				'smi'   => 'application/smil',
				'smil'  => 'application/smil',
				'mif'   => 'application/vnd.mif',
				'xls'   => array('application/excel', 'application/vnd.ms-excel', 'application/msexcel'),
				'ppt'   => array('application/powerpoint', 'application/vnd.ms-powerpoint'),
				'wbxml' => 'application/wbxml',
				'wmlc'  => 'application/wmlc',
				'dcr'   => 'application/x-director',
				'dir'   => 'application/x-director',
				'dxr'   => 'application/x-director',
				'dvi'   => 'application/x-dvi',
				'gtar'  => 'application/x-gtar',
				'gz'    => 'application/x-gzip',
				'php'   => array('application/x-httpd-php', 'text/x-php'),
				'php4'  => 'application/x-httpd-php',
				'php3'  => 'application/x-httpd-php',
				'phtml' => 'application/x-httpd-php',
				'phps'  => 'application/x-httpd-php-source',
				'js'    => 'application/x-javascript',
				'swf'   => 'application/x-shockwave-flash',
				'sit'   => 'application/x-stuffit',
				'tar'   => 'application/x-tar',
				'tgz'   => array('application/x-tar', 'application/x-gzip-compressed'),
				'xhtml' => 'application/xhtml+xml',
				'xht'   => 'application/xhtml+xml',
				'zip'   => array('application/x-zip', 'application/zip', 'application/x-zip-compressed'),
				'mid'   => 'audio/midi',
				'midi'  => 'audio/midi',
				'mpga'  => 'audio/mpeg',
				'mp2'   => 'audio/mpeg',
				'mp3'   => array('audio/mpeg', 'audio/mpg', 'audio/mpeg3', 'audio/mp3'),
				'aif'   => 'audio/x-aiff',
				'aiff'  => 'audio/x-aiff',
				'aifc'  => 'audio/x-aiff',
				'ram'   => 'audio/x-pn-realaudio',
				'rm'    => 'audio/x-pn-realaudio',
				'rpm'   => 'audio/x-pn-realaudio-plugin',
				'ra'    => 'audio/x-realaudio',
				'rv'    => 'video/vnd.rn-realvideo',
				'wav'   => 'audio/x-wav',
				'bmp'   => 'image/bmp',
				'gif'   => 'image/gif',
				'jpeg'  => array('image/jpeg', 'image/pjpeg'),
				'jpg'   => array('image/jpeg', 'image/pjpeg'),
				'jpe'   => array('image/jpeg', 'image/pjpeg'),
				'png'   => 'image/png',
				'tiff'  => 'image/tiff',
				'tif'   => 'image/tiff',
				'css'   => 'text/css',
				'html'  => 'text/html',
				'htm'   => 'text/html',
				'shtml' => 'text/html',
				'txt'   => 'text/plain',
				'text'  => 'text/plain',
				'log'   => array('text/plain', 'text/x-log'),
				'rtx'   => 'text/richtext',
				'rtf'   => 'text/rtf',
				'xml'   => 'text/xml',
				'xsl'   => 'text/xml',
				'mpeg'  => 'video/mpeg',
				'mpg'   => 'video/mpeg',
				'mpe'   => 'video/mpeg',
				'qt'    => 'video/quicktime',
				'mov'   => 'video/quicktime',
				'avi'   => 'video/x-msvideo',
				'movie' => 'video/x-sgi-movie',
				'doc'   => 'application/msword',
				'docx'  => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
				'xlsx'  => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
				'word'  => array('application/msword', 'application/octet-stream'),
				'xl'    => 'application/excel',
				'eml'   => 'message/rfc822',
				'json'  => array('application/json', 'text/json')
			);
		}

		if (!array_key_exists($extension, static::$mimeTypes))
		{
			return $default;
		}

		return (is_array(static::$mimeTypes[$extension])) ? static::$mimeTypes[$extension][0] : static::$mimeTypes[$extension];
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

	public static function lsdir($path, $mask = null, $options = fIterator::SKIP_DOTS)
	{
		$files = array();
		$items = new fIterator($path, $options);

		foreach ($items as $item)
		{
			$itemFileName = $item->getBasename();

			if (empty($mask) || Str::contains($itemFileName, $mask))
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