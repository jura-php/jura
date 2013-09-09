<?php
class ItemsField extends Field
{
	private $multiple;
	private $multipleTable;
	private $multipleFieldFrom;
	private $multipleFieldTo;

	public $items;

	private $resourceURL;
	private $tmpValue;

	public function __construct($name, $label = null)
	{
		parent::__construct($name, $label);

		$this->type = 'items';
		$this->multiple = false;
		$this->items = array();
		$this->resourceURL = 'fields/' . $this->type . uniqueID();
		$this->validationLength = -1;

		$that = $this;
		Router::register('GET', 'manager/api/' . $this->resourceURL, function () use ($that) {
			if (($token = User::validateToken()) !== true)
			{
				return $token;
			}

			$that->module->flag = "C";

			$that->init();
			return Response::json($that->items());
		});
	}

	public function addItemsFromArray($arr)
	{
		$this->items = array_unique(array_merge($this->items, $arr));
	}

	public function addItemsFromTable($tableName, $fieldValue = "id", $fieldLabel = "name", $orderBy = "[label]")
	{
		if ($orderBy == "[label]")
		{
			$orderBy = $fieldLabel . " ASC";
		}
		
		$sql = "SELECT " . $fieldValue . " AS id, " . $fieldLabel . " AS name FROM " . J_TP . $tableName . " ORDER BY " . $orderBy;
		$this->addItemsFromSQL($sql);
	}

	public function addItemsFromSQL($sql, $fieldValue = "id", $fieldLabel = "name", $connName = null)
	{
		$rs = DB::conn($connName)->query($sql);

		while (!$rs->EOF)
		{
			$this->items[$rs->fields[$fieldValue]] = $rs->fields[$fieldLabel];

			$rs->moveNext();
		}

		$rs->close();
	}

	public function items()
	{
		$items = array();
		foreach ($this->items as $k => $v)
		{
			$items[] = array(
				"v" => $k,
				"l" => $v
			);
		}

		return $items;
	}

	public function config()
	{
		if ($this->multiple)
		{
			$this->type = "multipleItems";
		}
		
		$arr = parent::config();

		return array_merge(array(
			'resource_url' => $this->resourceURL
		), $arr);
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
			return $this->items[$value];
		}
		else if ($flag == "C" || $flag == "U")
		{
			if (!is_array($value))
			{
				if ($value == (int)$value)
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
}
?>