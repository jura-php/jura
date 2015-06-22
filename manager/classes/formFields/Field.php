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
	public $fieldGroup;

	public $module;

	public $includeOnSQL = true;

	public function __construct($name, $label = null)
	{
		$this->name = $name;
		$this->label = (is_null($label) ? $name : $label);
		$this->flags = "";
		$this->type = "text";
		$this->defaultValue = "";
		$this->fieldGroup = 0;

		$this->required = true;
		$this->validationPattern = ".*";
		$this->validationTitle = "Preencha o campo '#LABEL#' corretamente"; //TODO: Usar linguagem
		$this->validationLength = 255;
	}

	public function init()
	{

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
			"group" => $this->fieldGroup,
			"validation" => array(
				"required" => $this->required,
				"pattern" => $this->validationPattern,
				"title" => $validationTitle,
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

	public function includeOnSQL()
	{
		return $this->includeOnSQL;
	}

	public function save($value)
	{
		if ($this->includeOnSQL())
		{
			if ($this->validationLength > 0)
			{
				$value = substr($value, 0, $this->validationLength);
			}

			$this->module->orm->setField($this->name, $value);
		}
	}

	public function value()
	{
		$value = $this->module->orm->field($this->name);

		return $this->format($value);
	}

	public function afterSave()
	{

	}

	public function filter($search)
	{
		$this->module->orm->whereLike($this->name, "%" . $search . "%");
	}

	public function select()
	{
		if ($this->includeOnSQL())
		{
			$this->module->orm->select($this->name);
		}
	}

	public function extraData()
	{
		return null;
	}
}
?>