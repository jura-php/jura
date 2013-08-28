<?php
class ToggleField extends Field
{
	public function __construct($name = "active", $label = "Ativo")
	{
		parent::__construct($name, $label);

		$this->type = "toggle";
		$this->defaultValue = 0;
	}

	public function format($value)
	{
		return (string)$value;
	}

	public function unformat($value)
	{
		return (int)$value;
	}
}
?>