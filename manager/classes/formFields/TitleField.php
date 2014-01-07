<?php
class TitleField extends TextField
{
	function __construct($label)
	{
		parent::__construct("title" . uniqueID(), $label);

		$this->type = "title";
		$this->validation("textarea");
	}

	public function includeOnSQL()
	{
		return false;
	}

	public function value()
	{
		return "";
	}

	public function save($value)
	{

	}

	public function filter($search)
	{

	}
}