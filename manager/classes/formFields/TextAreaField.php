<?php
class TextAreaField extends Field
{
	public $listLimit = 100;

	public function __construct($name, $label = null)
	{
		parent::__construct($name, $label);

		$this->type = "textarea";
	}

	public function validation($type)
	{
		$this->validationLength = 65535;
	}

	public function format($value)
	{
		if ($this->module->flag == "L")
		{
			return Str::limit($value, $this->listLimit);
		}

		return $value;
	}
}
?>