<?php
class TimeField extends Field
{
	public function __construct($name, $label = null)
	{
		parent::__construct($name, $label);

		$this->type = "time";

		$this->validationLength = 8;
		$this->defaultValue = "00:00:00";
	}

	public function format($value)
	{
		if (empty($value))
		{
			return $this->defaultValue;
		}

		return $value;
	}

}
?>