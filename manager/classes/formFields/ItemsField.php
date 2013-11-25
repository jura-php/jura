<?php
class ItemsField extends Field
{
	private $multiple;
	private $multipleTable;
	private $multipleFieldFrom;
	private $multipleFieldTo;

	private $items;

	private $resourceURL;
	private $tmpValue;

	private $initialized;
	private $toAdd;

	public function __construct($name, $label = null)
	{
		parent::__construct($name, $label);

		$this->type = 'items';
		$this->multiple = false;
		$this->items = array();
		$this->resourceURL = 'fields/' . $this->type . uniqueID();
		$this->validationLength = -1;
		$this->initialized = false;
		$this->toAdd = array();

		// $that = $this;
		// Router::register('GET', 'manager/api/' . $this->resourceURL, function () use ($that) {
		// 	if (($token = User::validateToken()) !== true)
		// 	{
		// 		return $token;
		// 	}

		// 	$that->module->flag = "C";

		// 	$that->init();
		// 	return Response::json($that->items);
		// });
	}

	public function init()
	{
		$this->initialized = true;
		$this->runAdds();
	}

	private function runAdds()
	{
		if (!$this->initialized)
		{
			return;
		}

		foreach ($this->toAdd as $add)
		{
			$add = explode("#####", $add);

			switch ($add[0])
			{
				case "fromSQL":
					$this->addItemsFromSQL($add[1], $add[2], $add[3], ($add[4] == "") ? null : $add[4]);

					break;
			}
		}

		$this->toAdd = array();
	}

	public function addItemsFromArray($arr)
	{
		foreach ($arr as $k => $v)
		{
			$this->items[$k] = $v;
		}
	}

	public function addItemsFromTable($tableName, $fieldValue = "id", $fieldLabel = "name", $orderBy = "[label]", $connName = null, $now = false)
	{
		if ($orderBy == "[label]")
		{
			$orderBy = $fieldLabel . " ASC";
		}
		
		$sql = "SELECT " . $fieldValue . " AS id, " . $fieldLabel . " AS name FROM " . J_TP . $tableName . " ORDER BY " . $orderBy;
		$this->addItemsFromSQL($sql, "id", "name", $connName, $now);
	}

	public function addItemsFromSQL($sql, $fieldValue = "id", $fieldLabel = "name", $connName = null, $now = false)
	{
		if (!$this->initialized && !$now)
		{
			$this->toAdd[] = "fromSQL#####" . $sql . "#####" . $fieldValue . "#####" . $fieldLabel . "#####" . $connName;
			return;
		}

		$rs = DB::conn($connName)->query($sql);

		while (!$rs->EOF)
		{
			$this->items[$rs->fields[$fieldValue]] = $rs->fields[$fieldLabel];

			$rs->moveNext();
		}

		$rs->close();
	}

	public function config()
	{
		if ($this->multiple)
		{
			$this->type = "multipleItems";
		}

		return parent::config();
	}

	public function multiple($tableName, $fieldFrom, $fieldTo)
	{
		$this->multiple = true;
		$this->multipleTable = $tableName;
		$this->multipleFieldFrom = $fieldFrom;
		$this->multipleFieldTo = $fieldTo;
	}

	public function format($value)
	{
		$flag = $this->module->flag;
		
		if ($flag == "L" || $flag == "R")
		{
			return array_get($this->items, $value);
		}
		else if ($flag == "C" || $flag == "U")
		{
			if (!is_array($value))
			{
				if (is_numeric($value))
				{
					$value = (int)$value;
				}

				if ($this->multiple)
				{
					$value = (array)$value;
				}
			}

			return $value;
		}
	}

	public function unformat($value)
	{
		if (!$this->multiple)
		{
			if (is_array($value))
			{
				$value = $value[0];
			}
		}

		return $value;
	}

	public function includeOnSQL()
	{
		return !$this->multiple;
	}

	public function save($value)
	{
		if (!$this->multiple)
		{
			$this->module->orm->setField($this->name, $value);
		}
		else
		{
			$this->tmpValue = $value;
		}
	}

	public function afterSave()
	{
		if ($this->multiple)
		{
			$value = $this->tmpValue;
			$id = $this->module->orm->field("id");
			$ormRel = ORM::make($this->multipleTable);

			$entries = $ormRel
							->where($this->multipleFieldFrom, "=", $id)
							->find();

			foreach ($entries as $entry)
			{
				$to = $entry->field($this->multipleFieldTo);
				$found = false;

				foreach ($value as $k => $v)
				{
					if ((int)$to == (int)$v)
					{
						$found = true;
						unset($value[$k]);

						break;
					}
				}

				if (!$found)
				{
					$entry->delete();
				}
			}

			foreach ($value as $v)
			{
				$ormRel->reset();
				$ormRel->setField($this->multipleFieldFrom, $id);
				$ormRel->setField($this->multipleFieldTo, $v);
				$ormRel->insert();
			}
		}
	}

	public function value()
	{
		if (!$this->multiple)
		{
			$value = $this->module->orm->field($this->name);

			return $this->format($value);
		}
		else
		{
			$id = $this->module->orm->field("id");
			$ormRel = ORM::make($this->multipleTable);
			$values = array();

			$entries = $ormRel
							->where($this->multipleFieldFrom, "=", $id)
							->find();

			foreach ($entries as $entry)
			{
				$values[] = (int)$entry->field($this->multipleFieldTo);
			}

			return $values;
		}
	}

	public function extraData()
	{
		return $this->items;
	}
}
?>