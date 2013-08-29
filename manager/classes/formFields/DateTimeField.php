<?php
class DateTimeField extends Field
{
	public function __construct($name, $label = null)
	{
		parent::__construct($name, $label);

		$this->type = "datetime";
		$this->defaultValue = date("Y-m-d H:i:s");

		$this->validationLength = 19;
	}

	public function format($value)
	{
		if (!empty($value))
		{
			return php_datetime(sql_php_datetime($value));
		}

		return "";
	}

	public function unformat($value)
	{
		if (!empty($value))
		{
			return php_sql_datetime(datetime_php($value));
		}

		return "";
	}
}
?>