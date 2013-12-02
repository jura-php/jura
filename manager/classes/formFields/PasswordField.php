<?php
class PasswordField extends Field
{
	public function __construct($name, $label = null)
	{
		parent::__construct($name, $label);

		$this->type = "password";
		$this->required = false;
	}

	public function format($value)
	{
		return "";
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