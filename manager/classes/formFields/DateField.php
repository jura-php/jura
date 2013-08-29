<?php
class DateField extends Field
{
	public function __construct($name, $label = null)
	{
		parent::__construct($name, $label);

		$this->type = "date";
		$this->defaultValue = date("Y-m-d");

		$this->validationLength = 10;
	}

	public function format($value)
	{
		if (!empty($value))
		{
			return php_date(sql_php_date($value));
		}

		return "";
	}

	public function unformat($value)
	{
		if (!empty($value))
		{
			return php_sql_date(date_php($value));
		}

		return "";
	}
}
?>