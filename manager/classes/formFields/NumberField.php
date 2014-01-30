<?php
class NumberField extends Field
{
	public function __construct($name, $label = null)
	{
		parent::__construct($name, $label);

		$this->validationType = "int";
		$this->type = "number";
	}

	public function validation($type)
	{

		switch ($type)
		{
			case "hours":
				$this->validationPattern = "\d+";
				$this->validationLength = 5;

				break;
			case "minutes":
				$this->validationPattern = "\d+";
				$this->validationLength = 5;

				break;
			case "float":
				$this->validationPattern = "-?\d+(,\d{0,10})?";
				$this->validationLength = 20;

				break;
			case "currency":
				$this->validationPattern = "^\\$?(([1-9](\\d*|\\d{0,2}(.\\d{3})*))|0)(\\,\\d{1,2})?$"; //?
				$this->validationLength = 30;
				//TODO: Mask

				break;
			case "int":
				$this->validationPattern = "\d+";
				$this->validationLength = 20;

				break;
		}

		$this->validationType = $type;
	}

	public function format($value)
	{
		switch ($this->validationType)
		{
			case "currency":
				return number_format((float)$value, 2, ",", ".");

				break;
			case "float":
				return str_replace(".", ",", $value);

				break;
			default:
				return $value;
		}

	}

	public function unformat($value)
	{
		switch ($this->validationType)
		{
			case "currency":
				return (float)str_replace(",", ".", str_replace(".", "", $value));

				break;
			case "float":
				return str_replace(",", ".", $value);

				break;

			default:
				return $value;
		}

	}
}
?>