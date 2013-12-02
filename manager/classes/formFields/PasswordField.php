<?php
class PasswordField extends Field
{
	public function __construct($name, $label = null)
	{
		parent::__construct($name, $label);

		$this->type = "password";
	}

	public function format($value)
	{
		if ($this->module->flag == "L")
		{
			return ".";
		}

		return $value;
	}

	public function unformat($value)
	{
		if (!empty($value))
		{
			return md5($value);
		}

		return "";
	}
}
?>