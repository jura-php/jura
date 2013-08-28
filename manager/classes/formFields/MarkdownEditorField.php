<?php
class MarkdownEditorField extends Field
{
	public $listLimit = 100;

	public function __construct($name, $label = null)
	{
		parent::__construct($name, $label);

		$this->type = "markdown";
		$this->validationLength = 65535;
	}

	public function format($value)
	{
		$flag = $this->module->flag;
		
		if ($flag == "L")
		{
			return Markdown::defaultTransform(Str::limit($value, $this->listLimit));
		}

		if ($flag == "R")
		{
			return Markdown::defaultTransform($value);
		}

		return $value;
	}
}
?>