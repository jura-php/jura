<?php
class ManyToOneField extends ItemsField
{
	private $relationTableName;
	private $relationKeyField;
	private $relationNameField;

	private $filterFields;

	public $items;

	public function __construct($name, $label = null, $tableName, $keyField = "id", $nameField = "name")
	{
		parent::__construct($name, $label);

		$this->relationTableName = $tableName;
		$this->relationKeyField = $keyField;
		$this->relationNameField = $nameField;
	}

	public function addItemsFromArray($arr)
	{
		trigger_error("addItemsFromArray not suported for this field");
	}

	//Comparison possibilities: =, %
	public function alsoFilter($fieldName, $comparison = "=")
	{
		if (is_null($this->filterFields))
		{
			$this->filterFields = array();
		}

		$this->filterFields[] = array($fieldName, $comparison);
	}

	public function init()
	{
		$flag = $this->module->flag;

		if ($flag == "C" || $flag == "R" || $flag == "U")
		{
			$this->addItemsFromTable($this->relationTableName, $this->relationKeyField, $this->relationNameField);
		}
	}

	public function format($value)
	{
		if ($this->module->flag == "L")
		{
			return $value;
		}

		return parent::format($value);
	}

	public function select()
	{
		$this->module->orm
				->select($this->relationTableName . "." . $this->relationNameField, $this->name)
				->leftJoin($this->relationTableName, array($this->relationTableName . "." . $this->relationKeyField, "=", $this->module->orm->tableName . "." . $this->name));
	}

	public function filter($search)
	{
		$this->module->orm->whereLike($this->relationTableName . "." . $this->relationNameField, "%" . $search . "%");

		if (!is_null($this->filterFields))
		{
			foreach ($this->filterFields as $field)
			{
				$comparation = $field[1];

				switch ($comparation)
				{
					case "=":
					default:
						$this->module->orm->where($this->relationTableName . "." . $field[0], $search);

						break;
					case "%":
						$this->module->orm->whereLike($this->relationTableName . "." . $field[0], "%" . $search . "%");

						break;
				}
			}
		}
	}
}
?>