<?php
class Field
{
	public $name;
	public $label;
	public $flags;
	public $type;

	public $defaultValue;

	public $required;
	public $validationPattern;
	public $validationTitle;
	public $validationLength;

	public function __construct($name, $label = null)
	{
		$this->name = $name;
		$this->label = (is_null($label) ? $name : $label);
		$this->flags = "";
		$this->type = "text";
		$this->defaultValue = "";

		$this->required = true;
		$this->validationPattern = ".*";
		$this->validationTitle = "Preencha o #LABEL# corretamente"; //TODO: Usar linguagem
		$this->validationLength = 255;
	}

	public function hasFlag($flag)
	{
		return (Str::contains($this->flags, $flag));
	}

	public function config()
	{
		$validationTitle = str_replace("#LABEL#", $this->label, $this->validationTitle);

		return array(
			"type" => $this->type,
			"name" => $this->name,
			"label" => $this->label,
			"flags" => $this->flags,
			"validation" => array(
				"required" => $this->required,
				"pattern" => $this->validationPattern,
				"title" => $this->validationTitle,
				"length" => $this->validationLength
			)
		);
	}

	public function format($value)
	{
		return $value;
	}

	public function unformat($value)
	{
		return $value;
	}

	public function validation($type)
	{
		
	}
}
?>