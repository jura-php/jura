<?php
class MarkdownEditorField extends Field
{
	public $listLimit = 100;

	public function __construct($name, $label = null)
	{
		parent::__construct($name, $label);

		$this->type = "markdown";
	}

	public function format($value, $flag)
	{
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