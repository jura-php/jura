<?php
/**
 * Dups the var inside a pre tag
 * @param  object $value
 */
function dump($value)
{
	echo "<pre>";
	print_r($value);
	echo "</pre>";
}

/**
 * Escape the string to print right on a javascript content
 * @param  string $value
 */
function js_string($value)
{
	return str_replace("'", "\\" . "'", $value);
}

/**
 * Benchmarks the piece of code between 2 calls of this function.
 * 
 * <code>
 * benchmark();
 * for ($i = 0; $i < 100000; $i++)
 * {
 * 	$pow = $i * $i;
 * }
 * benchmark("Pow expression 100000 times");
 * </code>
 * 
 * @param  string $message
 */
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

/**
 * Time elapsed in milliseconds since the J framework has started
 * @return int
 */
function elapsed_time()
{
	return microtime(true) - J_START;
}

/**
 * Converts a SQL date to a PHP date
 * @param  string $mysqlDate
 * @return int
 */
function sql_php_date($mysqlDate)
{
	if (empty($mysqlDate))
	{
		return 0;
	}

	$arr = explode("-", $mysqlDate);

	return mktime(0, 0, 0, (int)$arr[1], (int)$arr[2], (int)$arr[0]);
}

/**
 * Converts a SQL datetime to a PHP date
 * @param  string $mysqlDateTime
 * @return int
 */
function sql_php_datetime($mysqlDateTime)
{
	if (empty($mysqlDateTime))
	{
		return 0;
	}

	$arr1 = explode(" ", $mysqlDateTime);
	$arr2 = explode("-", $arr1[0]);
	$arr3 = explode(":", $arr1[1]);

	return mktime((int)$arr3[0], (int)$arr3[1], (int)$arr3[2], (int)$arr2[1], (int)$arr2[2], (int)$arr2[0]);
}

/**
 * Converts a PHP date to a SQL date
 * @param  int $phpDate
 * @return string
 */
function php_sql_date($phpDate)
{
	return date("Y-m-d", $phpDate);
}

/**
 * Converts a PHP date to a SQL datetime
 * @param  int $phpDateTime
 * @return string
 */
function php_sql_datetime($phpDateTime)
{
	return date("Y-m-d H:i:s", $phpDateTime);
}

/**
 * Converts a human readable date to a PHP date
 * @param  string $date
 * @return int
 */
function date_php($date)
{
	if (empty($date))
	{
		return 0;
	}

	$arr = explode("/", $date);

	if (count($arr) != 3)
	{
		echo "date error: '" . $date . "'";
		return 0;
	}

	return mktime(0, 0, 0, (int)$arr[1], (int)$arr[0], (int)$arr[2]);
}

/**
 * Converts a human readable datetime to a PHP date
 * @param  string $dateTime
 * @return int
 */
function datetime_php($dateTime)
{
	if (empty($dateTime))
	{
		return 0;
	}

	$arr1 = explode(" ", $dateTime);

	if (count($arr1) != 2)
	{
		return 0;
	}

	$arr2 = explode("/", $arr1[0]);
	$arr3 = explode(":", $arr1[1]);

	if (count($arr2) != 3 || count($arr3) != 3)
	{
		return 0;
	}

	return mktime((int)$arr3[0], (int)$arr3[1], (int)$arr3[2], (int)$arr2[1], (int)$arr2[0], (int)$arr2[2]);
}

/**
 * Converts a PHP date to a human readable date
 * @param  int $phpDate
 * @return string
 */
function php_date($phpDate)
{
	return date("d/m/Y", $phpDate);
}

/**
 * Converts a PHP date to a human readable datetime
 * @param  int $phpDate
 * @return string
 */
function php_datetime($phpDate)
{
	return date("d/m/Y H:i:s", $phpDate);
}

/**
 * Get an item from an object using "dot" notation.
 *
 * <code>
 *		// Get the $object->user->name value from the array
 *		$name = object_get($object, 'user.name');
 *
 *		// Return a default from if the specified item doesn't exist
 *		$name = object_get($object, 'user.name', 'Taylor');
 * </code>
 *
 * @param  array   $array
 * @param  string  $key
 * @param  mixed   $default
 * @return mixed
 */
function object_get($object, $key, $default = null)
{
	if (is_null($key)) return $array;

	// To retrieve the array item using dot syntax, we'll iterate through
	// each segment in the key and look for that value. If it exists, we
	// will return it, otherwise we will set the depth of the array and
	// look for the next segment.
	foreach (explode('.', $key) as $segment)
	{
		if (!is_object($object) or !property_exists($object, $segment))
		{
			return value($default);
		}

		$object = $object->{$segment};
	}

	return $object;
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
		if (!is_array($array) or !array_key_exists($segment, $array))
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