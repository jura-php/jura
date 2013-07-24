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
				$this->validationPattern = ""; //?
				$this->validationLength = 5;

				break;
			case "minutes":
				$this->validationPattern = ""; //?
				$this->validationLength = 5;

				break;
			case "float":
				$this->validationPattern = ""; //?
				$this->validationLength = 20;

				break;
			case "int":
				$this->validationPattern = ""; //?
				$this->validationLength = 20;

				break;
			case "currency":
				$this->validationPattern = ""; //?
				$this->validationLength = 20;
				//TODO: Mask

				break;
		}
	}
}
?>