<?php
class Field
{
	public $name;
	public $label;
	public $flags;
	public $type;

	public $defaultValue;

	public function __construct($name, $label = null)
	{
		$this->name = $name;
		$this->label = (is_null($label) ? $name : $label);
		$this->flags = "";
		$this->type = "text";
		$this->defaultValue = "";
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
}
?>