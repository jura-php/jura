<?php
class StaticField extends TextField
{
	function __construct($name, $label)
	{
		parent::__construct($name, $label);

		$this->validation("textarea");
	}

	public function includeOnSQL()
	{
		return false;
	}

	public function value($flag)
	{
		return "";
	}

	public function save($value, $flag)
	{

	}
}