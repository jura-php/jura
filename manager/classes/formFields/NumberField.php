<?php
class NumberField extends Field
{
	public function __construct($name, $label = null)
	{
		parent::__construct($name, $label);

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
				$this->validationPattern = "\d+(,\d{2})?";
				$this->validationLength = 20;
				//TODO: Format, Unformat

				break;
			case "int":
				$this->validationPattern = "\d+";
				$this->validationLength = 20;

				break;
			case "currency":
				$this->validationPattern = "^\\$?(([1-9](\\d*|\\d{0,2}(.\\d{3})*))|0)(\\,\\d{1,2})?$"; //?
				$this->validationLength = 30;
				//TODO: Mask
				//TODO: Format, Unformat

				break;
		}
	}
}
?>