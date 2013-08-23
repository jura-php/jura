<?php
class TimeField extends Field
{
	public function __construct($name, $label = null)
	{
		parent::__construct($name, $label);

		$this->type = "time";

		$this->validationLength = 19;
	}

}
?>