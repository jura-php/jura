<?php
class Crypt {
	private static $hashTable;

	private static function hashTable()
	{
		if (!self::$hashTable)
		{
			self::$hashTable = array("f", "4", "G", "a", "D", "8", "P", "K", "Z", "u", "Y", "x", "c", "M", "y", "w", "r", "7", "5", "0", "S", "g", "F", "Q", "o", "R", "E", "h", "m", "t", "C", "s", "z", "9", "e", "V");	
		}

		return self::$hashTable;
	}

	public static function encode($string)
	{
		$string = (string)$string;

		$arr = self::hashTable();
		$t = sizeof($arr) - 1;
		$r = "";
		$l = strlen($string);

		for ($i = 0; $i < $l; $i++)
		{
			$c1 = 0;
			$c2 = ord($string{$i});

			while ($c2 > $t) {
				$c2 -= $t;
				$c1++;
			}

			if (($i % 2) == 0)
			{
				$r .= $arr[$c1] . $arr[$c2];
			}
			else
			{
				$r .= $arr[$t - $c1] . $arr[$t - $c2];
			}
		}

		return $r;
	}

	public static function decode($string)
	{
		$arr = self::hashTable();
		$t = sizeof($arr) - 1;
		$k = array_flip($arr);
		$n = 0;
		$r = "";
		$l = strlen($string);

		for ($i = 0; $i < $l; $i++)
		{
			$c1 = $string{$i++};
			$c2 = $string{$i};

			if (($n % 2) == 0)
			{
				$r .= chr(($k[$c1] * $t) + $k[$c2]);
			}
			else 
			{
				$r .= chr((($t - $k[$c1]) * $t) + ($t - $k[$c2]));
			}

			$n++;
		}

		return $r;
	}
}
?>