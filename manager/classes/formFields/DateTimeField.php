<?php
class DateTimeField extends Field
{
	public function __construct($name, $label = null)
	{
		parent::__construct($name, $label);

		$this->type = "datetime";

		$this->validationLength = 19;
	}

	public function format($value, $flag)
	{
		return php_datetime(sql_php_datetime($value));
	}

	public function unformat($value)
	{
		return php_sql_datetime(datetime_php($value));
	}
}
?>