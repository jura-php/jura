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
		if ($this->module->flag == "R")
		{
			return ((int)$value == 1) ? "Sim" : "Não";
		}

		return (string)$value;
	}

	public function unformat($value)
	{
		return (int)$value;
	}
}
?>