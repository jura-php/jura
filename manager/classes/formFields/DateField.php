<?php
class DateField extends Field
{
	public function __construct($name, $label = null)
	{
		parent::__construct($name, $label);

		$this->type = "date";

		$this->validationLength = 10;
	}

	public function format($value, $flag)
	{
		return php_date(sql_php_date($value));
	}

	public function unformat($value)
	{
		return php_sql_date(date_php($value));
	}
}
?>