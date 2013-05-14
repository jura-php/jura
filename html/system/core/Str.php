<?php
class Str
{
	public static function is($pattern, $string)
	{
		if ($pattern !== '/')
		{
			$pattern = str_replace('*', '(.*)', $pattern).'\z';
		}
		else
		{
			$pattern = '^/$';
		}

		return preg_match('#'.$pattern.'#', $value);
	}
}
?>