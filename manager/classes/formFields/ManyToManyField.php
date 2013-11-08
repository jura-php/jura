<?php
class ManyToManyField extends ItemsField
{
	private $linkTableName;
	private $linkFromKeyField;
	private $linkToKeyField;

	private $relationTableName;
	private $relationKeyField;
	private $relationNameField;

	private $filterFields;

	public function __construct($name, $label = null, $linkTableName, $linkFromKeyField, $linkToKeyField, $relationTableName, $relationKeyField = "id", $relationNameField = "name")
	{
		parent::__construct($name, $label);

		$this->linkTableName = $linkTableName;
		$this->linkFromKeyField = $linkFromKeyField;
		$this->linkToKeyField = $linkToKeyField;

		$this->relationTableName = $relationTableName;
		$this->relationKeyField = $relationKeyField;
		$this->relationNameField = $relationNameField;

		$this->addItemsFromTable($this->relationTableName, $this->relationKeyField, $this->relationNameField);
		$this->multiple($this->linkTableName, $this->linkFromKeyField, $this->linkToKeyField);
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
				->selectRaw("GROUP_CONCAT(`" . J_TP . $this->relationTableName . "`.`" . $this->relationNameField . "` order by `" . J_TP . $this->relationTableName . "`.`" . $this->relationNameField . "` asc separator ', ') as `" . $this->name . "`")
				->leftJoin($this->linkTableName, array($this->linkTableName . "." . $this->linkFromKeyField, "=", $this->module->orm->tableName . ".id"))
				->leftJoin($this->relationTableName, array($this->relationTableName . "." . $this->relationKeyField, "=", $this->linkTableName . "." . $this->linkToKeyField))
				->groupBy("id");
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

	public function value()
	{
		$id = $this->module->orm->field("id");
		$ormRel = ORM::make($this->linkTableName);
		$values = array();

		$entries = $ormRel
						->where($this->linkFromKeyField, "=", $id)
						->leftJoin($this->relationTableName, array($this->relationTableName . "." . $this->relationKeyField, "=", $this->linkTableName . "." . $this->linkToKeyField))
						->orderByAsc($this->relationTableName . "." . $this->relationNameField)
						->find();

		foreach ($entries as $entry)
		{
			$values[] = (int)$entry->field($this->linkToKeyField);
		}

		return $values;
	}
}
?>