<?php
class TextAreaField extends TextField
{
	public function __construct($name, $label = null)
	{
		parent::__construct($name, $label);

		$this->type = "textarea";
		$this->validation("textarea");
	}
}
?>