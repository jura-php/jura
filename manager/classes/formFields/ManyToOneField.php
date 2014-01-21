<?php
class ManyToOneField extends ItemsField
{
	private $relationTableName;
	private $relationKeyField;
	private $relationNameField;

	private $filterFields;

	private $customSelect;

	public $items;

	public function __construct($name, $label = null, $tableName, $keyField = "id", $nameField = "name")
	{
		parent::__construct($name, $label);

		$this->relationTableName = $tableName;
		$this->relationKeyField = $keyField;
		$this->relationNameField = $nameField;

		$this->customSelect = null;
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

	public function customSelect($callback)
	{
		$this->customSelect = $callback;
	}

	public function init()
	{
		$wasEmpty = count($this->toAdd) == 0;

		parent::init();

		$flag = $this->module->flag;



		if ($wasEmpty && ($flag == "C" || $flag == "R" || $flag == "U"))
		{

			if ($this->relationTableName == "cidades_bairros")
			{
				count($this->toAdd);

				die();
			}

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

		if ($this->customSelect)
		{
			call_user_func($this->customSelect, $this->module->orm, $this, $this->relationTableName, $this->relationKeyField, $this->relationNameField);
		}
	}

	public function filter($search)
	{
		$this->module->orm->whereLike($this->relationTableName . "." . $this->relationNameField, "%" . $search . "%");

		if (!is_null($this->filterFields))
		{
			foreach ($this->filterFields as $field)
			{
				$name = $field[0];
				$comparation = $field[1];

				if (strpos($name, ".") === false)
				{
					$name = $this->relationTableName . "." . $name;
				}

				switch ($comparation)
				{
					case "=":
					default:
						$this->module->orm->where($name, $search);

						break;
					case "%":
						$this->module->orm->whereLike($name, "%" . $search . "%");

						break;
				}
			}
		}
	}
}
?>