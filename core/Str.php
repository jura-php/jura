<?php
class Str
{
	public static function is($pattern, $string)
	{
		if ($pattern !== '/')
		{
			$pattern = str_replace('*', '(.*)', $pattern) . '\z';
		}
		else
		{
			$pattern = '^/$';
		}

		return preg_match('#' . $pattern . '#', $string);
	}

	public static function removeAccents($string)
	{
		static $replaceDict = array(
	    'Š'=>'S', 'š'=>'s', 'Ð'=>'Dj','Ž'=>'Z', 'ž'=>'z', 'À'=>'A', 'Á'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Ä'=>'A',
	    'Å'=>'A', 'Æ'=>'A', 'Ç'=>'C', 'È'=>'E', 'É'=>'E', 'Ê'=>'E', 'Ë'=>'E', 'Ì'=>'I', 'Í'=>'I', 'Î'=>'I',
	    'Ï'=>'I', 'Ñ'=>'N', 'Ò'=>'O', 'Ó'=>'O', 'Ô'=>'O', 'Õ'=>'O', 'Ö'=>'O', 'Ø'=>'O', 'Ù'=>'U', 'Ú'=>'U',
	    'Û'=>'U', 'Ü'=>'U', 'Ý'=>'Y', 'Þ'=>'B', 'ß'=>'Ss','à'=>'a', 'á'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'a',
	    'å'=>'a', 'æ'=>'a', 'ç'=>'c', 'è'=>'e', 'é'=>'e', 'ê'=>'e', 'ë'=>'e', 'ì'=>'i', 'í'=>'i', 'î'=>'i',
	    'ï'=>'i', 'ð'=>'o', 'ñ'=>'n', 'ò'=>'o', 'ó'=>'o', 'ô'=>'o', 'õ'=>'o', 'ö'=>'o', 'ø'=>'o', 'ù'=>'u',
	    'ú'=>'u', 'û'=>'u', 'ý'=>'y', 'ý'=>'y', 'þ'=>'b', 'ÿ'=>'y', 'ƒ'=>'f');

		return strtr($string, $replaceDict);
	}

	public static function toURIParam($string)
	{
		$string = str_replace('&', '-and-', strtolower($string));
	    $string = str_replace(' ', '-', $string);
	    $string = str_replace('--', '-', $string);
	    $string = str_replace('/', '-', $string);

	    return trim(static::removeAccents($string));
	}

	public static function limit($string, $maxLength = 100)
	{
		$size = strlen($string);

		if ($size < $maxLength)
		{
			return $string;
		}
		else
		{
			return substr($string, 0, $maxLength - 3) . "...";
		}
	}

	public static function isUTF8($string)
	{
	    // From http://w3.org/International/questions/qa-forms-utf-8.html
	    return preg_match('%^(?:
			          [\x09\x0A\x0D\x20-\x7E]            # ASCII
			        | [\xC2-\xDF][\x80-\xBF]             # non-overlong 2-byte
			        |  \xE0[\xA0-\xBF][\x80-\xBF]        # excluding overlongs
			        | [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}  # straight 3-byte
			        |  \xED[\x80-\x9F][\x80-\xBF]        # excluding surrogates
			        |  \xF0[\x90-\xBF][\x80-\xBF]{2}     # planes 1-3
			        | [\xF1-\xF3][\x80-\xBF]{3}          # planes 4-15
			        |  \xF4[\x80-\x8F][\x80-\xBF]{2}     # plane 16
			    )*$%xs', $string);
	}

	public static function lower($string)
	{
		if (static::isUTF8($string))
		{
			return mb_convert_case($string, MB_CASE_LOWER, "UTF-8");
		}
		else
		{
			return strtolower($string);
		}
	}

	public static function upper($string)
	{
		if (static::isUTF8($string))
		{
			return mb_convert_case($string, MB_CASE_UPPER, "UTF-8");
		}
		else
		{
			return strtoupper($string);
		}
	}

	public static function ucwords($string)
	{
		if (static::isUTF8($string))
		{
			return mb_convert_case($string, MB_CASE_TITLE, "UTF-8");
		}
		else
		{
			return ucwords($string);
		}
	}

	public static function ucfirst($string)
	{
		return static::lower($text{0}) . substr($text, 1);
	}

	public static function startsWith($haystack, $needle)
	{
		return strpos($haystack, $needle) == 0;
	}

	public static function endsWith($haystack, $needle)
	{
		return $needle == substr($haystack, strlen($haystack) - strlen($needle));
	}

	public static function contains($haystack, $needle)
	{
		foreach ((array)$needle as $n)
		{
			if (strpos($haystack, $n) !== false) return true;
		}

		return false;
	}

	public static function finish($string, $cap)
	{
		return rtrim($string, $cap) . $cap;
	}

	public static function removeInvisible($string, $urnEncoded = true)
	{
		$nonDisplayables = array();

		// every control character except newline (dec 10)
		// carriage return (dec 13), and horizontal tab (dec 09)

		if ($urlEncoded)
		{
			$nonDisplayables[] = '/%0[0-8bcef]/';	// url encoded 00-08, 11, 12, 14, 15
			$nonDisplayables[] = '/%1[0-9a-f]/';	// url encoded 16-31
		}

		$nonDisplayables[] = '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/S';	// 00-08, 11, 12, 14-31, 127

		do
		{
			$str = preg_replace($nonDisplayables, '', $str, -1, $count);
		}
		while ($count);

		return $str;
	}
}
?>