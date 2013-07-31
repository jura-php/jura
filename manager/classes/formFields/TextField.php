<?php
class TextField extends Field
{
	public function __construct($name, $label = null)
	{
		parent::__construct($name, $label);

		$this->type = "text";
	}

	public function validation($type)
	{

		switch ($type)
		{
			case "email":
				$this->validationPattern = "^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$";

				break;
			case "textarea":
				$this->validationLength = 65535;

				break;
		}
	}
}
?>