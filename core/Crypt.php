<?php
class Crypt {
	public static function encode($string)
	{
		$key = Config::item("application", "key");
		$key = mb_substr($key, 0, mcrypt_get_key_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_CBC));
	    $ivSize = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_CBC);
	    $ivCode = mcrypt_create_iv($ivSize, MCRYPT_DEV_URANDOM);
	    $encrypted = static::to64(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $key, $string, MCRYPT_MODE_CBC, $ivCode));

	    return $encrypted . "|" . static::to64($ivCode);
	}

	public static function decode($string)
	{
		$key = Config::item("application", "key");
		$key  = mb_substr($key, 0, mcrypt_get_key_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_CBC));
	    list($string, $ivCode) = explode("|", $string);
	    $string = static::from64($string);
	    $ivCode = static::from64($ivCode);
	    $string = mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $key, $string, MCRYPT_MODE_CBC, $ivCode);

	    return rtrim($string, "\0");
	}

	public static function sslencode($string)
	{
		$key = hash("sha256", Config::item("application", "key"));
		$iv = substr(hash("sha256", "123"), 0, 16);

		return static::to64(openssl_encrypt($string, "AES-256-CBC", $key, 0, $iv));
	}

	public static function ssldecode($string)
	{
		$key = hash("sha256", Config::item("application", "key"));
		$iv = substr(hash("sha256", "123"), 0, 16);

		return openssl_decrypt(static::from64($string), "AES-256-CBC", $key, 0, $iv);
	}

	private static function to64($string)
	{
		$string = base64_encode($string);
	    $string = preg_replace('/\//', '_', $string);
	    $string = preg_replace('/\+/', '.', $string);
	    $string = preg_replace('/\=/', '-', $string);

	    return trim($string, '-');
	}

	private static function from64($string)
	{
		$string = preg_replace('/\_/', '/', $string);
	    $string = preg_replace('/\./', '+', $string);
	    $string = preg_replace('/\-/', '=', $string);
	    $string = base64_decode($string);

	    return $string;
	}
}
?>