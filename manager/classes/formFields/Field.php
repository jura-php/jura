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
		$this->validationTitle = "Preencha o campo '#LABEL#' corretamente"; //TODO: Usar linguagem
		$this->validationLength = 255;
	}

	public function init($flag)
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
			"validation" => array(
				"required" => $this->required,
				"pattern" => $this->validationPattern,
				"title" => $validationTitle,
				"length" => $this->validationLength
			)
		);
	}

	public function format($value, $flag)
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
		return true;
	}

	public function save($orm, $value, $flag)
	{
		if ($this->includeOnSQL())
		{
			if ($this->validationLength > 0)
			{
				$value = substr($value, 0, $this->validationLength);
			}

			$orm->setField($this->name, $value);
		}
	}

	public function value($orm, $flag)
	{
		$value = $orm->field($this->name);

		return $this->format($value, $flag);
	}

	public function afterSave($orm, $flag)
	{

	}

	public function filterORM($orm, $search)
	{
		return $orm->whereLike($this->name, "%" . $search . "%");
	}

	public function listORM($orm)
	{
		if ($this->includeOnSQL())
		{
			return $orm->select($this->name);
		}

		return $orm;
	}
}
?>