<?php
class Validation
{
	public static function email($value)
	{
		return filter_var($value, FILTER_VALIDATE_EMAIL);
	}

	public static function cpf($value)
	{
		if (empty($value))
		{
			return false;
		}

		$value = preg_replace('/[^0-9]/', '', $value);
		$value = str_pad($value, 11, '0', STR_PAD_LEFT);

		if (strlen($value) != 11)
		{
			return false;
		}

		if ($value == '00000000000' ||
			$value == '11111111111' ||
			$value == '22222222222' ||
			$value == '33333333333' ||
			$value == '44444444444' ||
			$value == '55555555555' ||
			$value == '66666666666' ||
			$value == '77777777777' ||
			$value == '88888888888' ||
			$value == '99999999999')
		{
			return false;
		}
		else
		{
			for ($t = 9; $t < 11; $t++)
			{
				for ($d = 0, $c = 0; $c < $t; $c++)
				{
					$d += $value{$c} * (($t + 1) - $c);
				}
				$d = ((10 * $d) % 11) % 10;
				if ($value{$c} != $d)
				{
					return false;
				}
			}

			return true;
		}
	}

	public static function creditCard($value)
	{
		$value = preg_replace('/[^0-9]/', '', $value);

		if (!preg_match('/^(?:4[0-9]{12}(?:[0-9]{3})?|5[1-5][0-9]{14}|6(?:011|5[0-9][0-9])[0-9]{12}|3[47][0-9]{13}|3(?:0[0-5]|[68][0-9])[0-9]{11}|(?:2131|1800|35\d{3})\d{11})$/', $value))
		{
			return false;
		}

		$len = strlen($value) - 1;

		if ($len <= 5)
		{
			return false;
		}

		$sum = 0;
		$double = false;
		for ($i = $len; $i >= 0; $i--)
		{
			$digit = (int)substr($value, $i, 1);
			if ($double)
			{
				$digit *= 2;
				if ($digit >= 10)
				{
					$digit = ($digit % 10) + 1;
				}

				$sum += $digit;
			}
			else
			{
				$sum += $digit;
			}

			$double = !$double;
		}
		if (($sum % 10) !== 0)
		{
			return false;
		}

		return true;
	}
}
?>