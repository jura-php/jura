<?php
class LabelField extends TextField
{
	public $tableName;

	function __construct($name, $label)
	{
		parent::__construct($name, $label);

		$this->validation("textarea");
	}

	public function includeOnSQL()
	{
		return false;
	}

	// public function value()
	// {
	// 	return "";
	// }

	public function save($value)
	{

	}
}