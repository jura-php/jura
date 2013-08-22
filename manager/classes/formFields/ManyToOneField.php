<?php
class ManyToOneField extends ItemsField
{
	private $relationTableName;
	private $relationKeyField;
	private $relationNameField;

	public $items;
	public $resourceURL;

	private $tmpValue;

	public function __construct($name, $label = null, $tableName, $keyField = "id", $nameField = "name")
	{
		parent::__construct($name, $label);

		$this->relationTableName = $tableName;
		$this->relationKeyField = $keyField;
		$this->relationNameField = $nameField;
	}

	public function addItemsFromArray($arr)
	{
		echo "ERROR..."; //TODO: Put on error class
	}

	public function init($flag)
	{
		if ($flag == "C" || $flag == "R" || $flag == "U")
		{
			$this->addItemsFromTable($this->relationTableName, $this->relationKeyField, $this->relationNameField);
		}
	}

	public function config()
	{
		$arr = parent::config();

		return array_merge([
			
		], $arr);
	}

	public function format($value, $flag)
	{
		if ($flag == "L")
		{
			return $value;
		}

		return parent::format($value, $flag);
	}

	public function listORM($orm)
	{
		if ($this->includeOnSQL())
		{
			return $orm
					->select($this->relationTableName . "." . $this->relationNameField, $this->name)
					->innerJoin($this->relationTableName, array($this->relationTableName . "." . $this->relationKeyField, "=", $orm->tableName . "." . $this->name));
		}

		return $orm;
	}

	public function filterORM($orm, $search)
	{
		return $orm->whereLike($this->relationTableName . "." . $this->relationNameField, "%" . $search . "%");
	}
}
?>