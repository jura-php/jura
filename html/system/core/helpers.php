<?php

function dump($value)
{
	echo "<pre>";
	var_dump($value);
	echo "</pre>";
}

function js_string($value)
{
	return str_replace("'", "\\" . "'", $value);
}

function str_uri($string)
{
	$string = str_replace('&', '-and-', strtolower($string));
    $string = str_replace(' ', '-', $string);
    $string = str_replace('--', '-', $string);
    $string = remove_accents($string);

	return trim($string);
}

function remove_accents($string)
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

function benchmark($message = "")
{
	static $previousTime = 0;
	static $index = 0;

	if ($previousTime == 0)
	{
		$previousTime = microtime(true);
		echo "[BENCHMARK] --------------------------------------<br>\n";
	}
	else
	{
		$microtime = microtime(true);
		echo "[" . ++$index . "] - " . number_format((($microtime - $previousTime) * 1000), 2)  . "ms ------------------------------------- " . $message . "<br>\n";
		$previousTime = $microtime;
	}
}

function elapsed_time()
{
	return microtime(true) - J_START;
}

function str_limit($string, $maxLength = 100)
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

//TODO: Detectar quando a string for utf8, se não utilizar o padrão..

function strtolowerutf8($text)
{
    return mb_convert_case($text, MB_CASE_LOWER, "UTF-8");
}

function strtoupperutf8($text)
{
    return mb_convert_case($text, MB_CASE_UPPER, "UTF-8");
}

function ucwordutf8($text)
{
    return mb_convert_case($text, MB_CASE_TITLE, "UTF-8");
}

function ucfirstutf8($text)
{
	return strtolowerutf8($text{0}) . substr($text, 1);
}

function sql_php_date($mysqlDate)
{
	if ($mysqlDate == "")
	{
		return 0;
	}

	$arr = explode("-", $mysqlDate);

	return mktime(0, 0, 0, $arr[1], $arr[2], $arr[0]);
}

function sql_php_datetime($mysqlDateTime)
{
	if ($mysqlDateTime == "")
	{
		return 0;
	}

	$arr1 = explode(" ", $mysqlDateTime);
	$arr2 = explode("-", $arr1[0]);
	$arr3 = explode(":", $arr1[1]);

	return mktime($arr3[0], $arr3[1], $arr3[2], $arr2[1], $arr2[2], $arr2[0]);
}

function php_sql_date($phpDate)
{
	return date("Y-m-d", $phpDate);
}

function php_sql_datetime($phpDateTime)
{
	return date("Y-m-d H:i:s", $phpDateTime);
}

function date_php($date)
{
	if ($date == "")
	{
		return 0;
	}

	$arr = explode("/", $date);

	return mktime(0, 0, 0, $arr[1], $arr[0], $arr[2]);
}

/**
 * Get an item from an array using "dot" notation.
 *
 * <code>
 *		// Get the $array['user']['name'] value from the array
 *		$name = array_get($array, 'user.name');
 *
 *		// Return a default from if the specified item doesn't exist
 *		$name = array_get($array, 'user.name', 'Taylor');
 * </code>
 *
 * @param  array   $array
 * @param  string  $key
 * @param  mixed   $default
 * @return mixed
 */
function array_get($array, $key, $default = null)
{
	if (is_null($key)) return $array;

	// To retrieve the array item using dot syntax, we'll iterate through
	// each segment in the key and look for that value. If it exists, we
	// will return it, otherwise we will set the depth of the array and
	// look for the next segment.
	foreach (explode('.', $key) as $segment)
	{
		if ( ! is_array($array) or ! array_key_exists($segment, $array))
		{
			return value($default);
		}

		$array = $array[$segment];
	}

	return $array;
}

/**
 * Set an array item to a given value using "dot" notation.
 *
 * If no key is given to the method, the entire array will be replaced.
 *
 * <code>
 *		// Set the $array['user']['name'] value on the array
 *		array_set($array, 'user.name', 'Taylor');
 *
 *		// Set the $array['user']['name']['first'] value on the array
 *		array_set($array, 'user.name.first', 'Michael');
 * </code>
 *
 * @param  array   $array
 * @param  string  $key
 * @param  mixed   $value
 * @return void
 */
function array_set(&$array, $key, $value)
{
	if (is_null($key)) return $array = $value;

	$keys = explode('.', $key);

	// This loop allows us to dig down into the array to a dynamic depth by
	// setting the array value for each level that we dig into. Once there
	// is one key left, we can fall out of the loop and set the value as
	// we should be at the proper depth.
	while (count($keys) > 1)
	{
		$key = array_shift($keys);

		// If the key doesn't exist at this depth, we will just create an
		// empty array to hold the next value, allowing us to create the
		// arrays to hold the final value.
		if ( ! isset($array[$key]) or ! is_array($array[$key]))
		{
			$array[$key] = array();
		}

		$array =& $array[$key];
	}

	$array[array_shift($keys)] = $value;
}

/**
 * Remove an array item from a given array using "dot" notation.
 *
 * <code>
 *		// Remove the $array['user']['name'] item from the array
 *		array_forget($array, 'user.name');
 *
 *		// Remove the $array['user']['name']['first'] item from the array
 *		array_forget($array, 'user.name.first');
 * </code>
 *
 * @param  array   $array
 * @param  string  $key
 * @return void
 */
function array_forget(&$array, $key)
{
	$keys = explode('.', $key);

	// This loop functions very similarly to the loop in the "set" method.
	// We will iterate over the keys, setting the array value to the new
	// depth at each iteration. Once there is only one key left, we will
	// be at the proper depth in the array.
	while (count($keys) > 1)
	{
		$key = array_shift($keys);

		// Since this method is supposed to remove a value from the array,
		// if a value higher up in the chain doesn't exist, there is no
		// need to keep digging into the array, since it is impossible
		// for the final value to even exist.
		if ( ! isset($array[$key]) or ! is_array($array[$key]))
		{
			return;
		}

		$array =& $array[$key];
	}

	unset($array[array_shift($keys)]);
}

/**
 * Return the first element in an array which passes a given truth test.
 *
 * <code>
 *		// Return the first array element
 *		$value = array_first($array);
 *
 *		// Return the first array element that equals "Taylor"
 *		$value = array_first($array, function($k, $v) {return $v == 'Taylor';});
 *
 *		// Return a default value if no matching element is found
 *		$value = array_first($array, function($k, $v) {return $v == 'Taylor'}, 'Default');
 * </code>
 *
 * @param  array    $array
 * @param  Closure  $callback
 * @param  mixed    $default
 * @return mixed
 */
function array_first($array, $callback = null, $default = null)
{
	if ($callback)
	{
		foreach ($array as $key => $value)
		{
			if (call_user_func($callback, $key, $value)) return $value;
		}
	}
	else
	{
		if (count($array) > 0)
		{
			$keys = array_keys($array);

			return value($array[$keys[0]]);
		}
	}

	return value($default);
}

/**
 * Return the last element in an array which passes a given truth test.
 *
 * <code>
 *		// Return the last array element
 *		$value = array_first($array);
 *
 *		// Return the last array element that equals "Taylor"
 *		$value = array_first($array, function($k, $v) {return $v == 'Taylor';});
 *
 *		// Return a default value if no matching element is found
 *		$value = array_first($array, function($k, $v) {return $v == 'Taylor'}, 'Default');
 * </code>
 *
 * @param  array    $array
 * @param  Closure  $callback
 * @param  mixed    $default
 * @return mixed
 */
function array_last($array, $callback = null, $default = null)
{
	return array_first(array_reverse($array), $callback, $default);
}

/**
 * Recursively remove slashes from array keys and values.
 *
 * @param  array  $array
 * @return array
 */
function array_strip_slashes($array)
{
	$result = array();

	foreach($array as $key => $value)
	{
		$key = stripslashes($key);

		// If the value is an array, we will just recurse back into the
		// function to keep stripping the slashes out of the array,
		// otherwise we will set the stripped value.
		if (is_array($value))
		{
			$result[$key] = array_strip_slashes($value);
		}
		else
		{
			$result[$key] = stripslashes($value);
		}
	}

	return $result;
}

/**
 * Divide an array into two arrays. One with keys and the other with values.
 *
 * @param  array  $array
 * @return array
 */
function array_divide($array)
{
	return array(array_keys($array), array_values($array));
}

/**
 * Pluck an array of values from an array.
 *
 * @param  array   $array
 * @param  string  $key
 * @return array
 */
function array_pluck($array, $key)
{
	return array_map(function($v) use ($key)
	{
		return is_object($v) ? $v->$key : $v[$key];

	}, $array);
}

/**
 * Get a subset of the items from the given array.
 *
 * @param  array  $array
 * @param  array  $keys
 * @return array
 */
function array_only($array, $keys)
{
	return array_intersect_key( $array, array_flip((array) $keys) );
}

/**
 * Get all of the given array except for a specified array of items.
 *
 * @param  array  $array
 * @param  array  $keys
 * @return array
 */
function array_except($array, $keys)
{
	return array_diff_key( $array, array_flip((array) $keys) );
}

/**
 * Determine if "Magic Quotes" are enabled on the server.
 *
 * @return bool
 */
function magic_quotes()
{
	return function_exists('get_magic_quotes_gpc') and get_magic_quotes_gpc();
}

/**
 * Determine if a given string begins with a given value.
 *
 * @param  string  $haystack
 * @param  string  $needle
 * @return bool
 */
function starts_with($haystack, $needle)
{
	return strpos($haystack, $needle) === 0;
}

/**
 * Determine if a given string ends with a given value.
 *
 * @param  string  $haystack
 * @param  string  $needle
 * @return bool
 */
function ends_with($haystack, $needle)
{
	return $needle == substr($haystack, strlen($haystack) - strlen($needle));
}

/**
 * Determine if a given string contains a given sub-string.
 *
 * @param  string        $haystack
 * @param  string|array  $needle
 * @return bool
 */
function str_contains($haystack, $needle)
{
	foreach ((array) $needle as $n)
	{
		if (strpos($haystack, $n) !== false) return true;
	}

	return false;
}

/**
 * Cap a string with a single instance of the given string.
 *
 * @param  string  $value
 * @param  string  $cap
 * @return string
 */
function str_finish($value, $cap)
{
	return rtrim($value, $cap).$cap;
}

function str_remove_invisible($str, $urlEncoded = TRUE)
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

/**
 * Calculate the human-readable file size (with proper units).
 *
 * @param  int     $size
 * @return string
 */
function human_file_size($size)
{
	$units = array('Bytes', 'KiB', 'MiB', 'GiB', 'TiB', 'PiB', 'EiB');
	return @round($size / pow(1024, ($i = floor(log($size, 1024)))), 2).' '.$units[$i];
}

/**
 * Return the value of the given item.
 *
 * If the given item is a Closure the result of the Closure will be returned.
 *
 * @param  mixed  $value
 * @return mixed
 */
function value($value)
{
	return (is_callable($value) and ! is_string($value)) ? call_user_func($value) : $value;
}

?>