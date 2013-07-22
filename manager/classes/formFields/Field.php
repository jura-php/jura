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

	public function __construct($name, $label = null)
	{
		$this->name = $name;
		$this->label = (is_null($label) ? $name : $label);
		$this->flags = "";
		$this->type = "text";
		$this->defaultValue = "";

		$this->required = true;
		$this->validationPattern = ".*";
		$this->validationTitle = "Preencha o campo"; //TODO: Usar linguagem
	}

	public function hasFlag($flag)
	{
		return (Str::contains($this->flags, $flag));
	}

	public function config()
	{
		$config = array();

		$config["type"] = $this->type;
		$config["name"] = $this->name;
		$config["label"] = $this->label;
		$config["flags"] = $this->flags;

		$validation = array();
		$validation["required"] = $this->required;
		$validation["pattern"] = $this->validationPattern;
		$validation["title"] = $this->validationTitle;

		$config["validation"] = $validation;

		return $config;
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