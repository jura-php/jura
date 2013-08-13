<?php
class Validation
{
	public static function email($value)
	{
		return filter_var($value, FILTER_VALIDATE_EMAIL);
	}
}
?>