<?php

//OK: Where com OR ou AND..
//OK: Retorno da query com um recordset associando um objeto a cada move[Next][First]()...

//TODO: Joins

class ORM
{
	private static $lastSQL = "";

	public $tableName;
	private $connName;

	private $method;

	private $selectFields;
	private $wheres;
	private $currentWhere;
	private $orderBys;
	private $groupBys;
	private $limit;
	private $offset;

	private $fields;
	private $dirtyFields;

	public static function make($tableName, $connName = null)
	{
		return new ORM($tableName, $connName);
	}

	public static function lastSQL()
	{
		return static::$lastSQL;
	}

	public function __construct($tableName, $connName = null)
	{
		$this->tableName = $tableName;
		$this->connName = $connName;

		$this->reset();
	}

	public function reset()
	{
		$this->fields = null;
		$this->dirtyFields = null;

		$this->selectFields = "*";
		$this->wheres = null;
		$this->currentWhere = null;
		$this->orderBys = null;
		$this->groupBys = null;
		$this->limit = 0;
		$this->offset = 0;

		return $this;
	}

	//SELECT
	public function findFirst($id = null)
	{
		if (!is_null($id))
		{
			$this->setField("id", $id);
		}
		$this->method = "SELECT";

		$this->limit(1);

		return $this->run();
	}

	public function find()
	{
		$this->method = "SELECT";

		$this->limit(0);

		return $this->run();
	}

	private function run()
	{
		switch ($this->method)
		{
			case "SELECT":
				$fields = $this->selectFields;

				if (is_array($fields))
				{
					$fields = join(", ", $fields);
				}

				$sql = $this->method . " " . $fields . " FROM " . DB::conn($this->connName)->quoteID(J_TP . $this->tableName) . " ";

				if (!is_null($this->wheres))
				{
					$sql .= "WHERE " . $this->buildWheres($this->wheres) . " ";
				}

				if (count($this->groupBys) > 0)
				{
					$sql .= "GROUP BY " . join(", ", $this->groupBys);
				}

				if (count($this->orderBys) > 0)
				{
					$sql .= "ORDER BY " . join(", ", $this->orderBys);
				}

				if ($this->limit > 0 || $this->offset > 0)
				{
					$sql .= "LIMIT ";

					if ($this->offset > 0)
					{
						$sql .= $this->offset . ",";
					}

					$sql .= $this->limit;
				}

				static::$lastSQL = $sql;

				return DB::conn($this->connName)->queryORM($sql, $this);

				break;
			case "INSERT":
				$fieldsInfo = DB::conn($this->connName)->fieldsInfo(J_TP . $this->tableName);
				$fields = array();
				$values = array();
				foreach ($fieldsInfo as $v)
				{
					$name = $v["name"];
					if (isset($this->dirtyFields[$name]))
					{
						$fields[] = DB::conn($this->connName)->quoteID($name);
						$values[] = DB::conn($this->connName)->escape($this->fields[$name]);
					}
				}

				$sql = "INSERT INTO " . J_TP . $this->tableName . " (" . join(", ", $fields) . ") VALUES (" . join(", ", $values) . ");";

				static::$lastSQL = $sql;

				$rs = DB::conn($this->connName)->query($sql);

				$this->setField("id", $rs->insertID);

				return $rs->success;

				break;
			case "UPDATE":
				$sql = "UPDATE " . J_TP . $this->tableName . " SET ";

				$fieldsInfo = DB::conn($this->connName)->fieldsInfo(J_TP . $this->tableName);
				$fields = array();
				foreach ($fieldsInfo as $v)
				{
					$name = $v["name"];
					if (isset($this->dirtyFields[$name]))
					{
						$fields[] = DB::conn($this->connName)->quoteID($name) . " = " . DB::conn($this->connName)->escape($this->fields[$name]);
					}
				}

				$sql .= join(", ", $fields) . " ";
				$sql .= "WHERE id = " . DB::conn($this->connName)->escape($this->fields["id"]) . " LIMIT 1;";

				static::$lastSQL = $sql;

				$rs = DB::conn($this->connName)->query($sql);

				return $rs->success;

				break;
			case "DELETE":
				$sql = "DELETE FROM " . J_TP . $this->tableName . " WHERE id = " . DB::conn($this->connName)->escape($this->fields["id"]) . " LIMIT 1;";

				static::$lastSQL = $sql;

				$rs = DB::conn($this->connName)->query($sql);

				return $rs->success;

				break;
		}
	}

	private function buildWheres(&$wheres)
	{
		$list = $wheres["wheres"];
		$outs = array();

		foreach ($list as $v)
		{
			if (is_array($v))
			{
				$outs[] = "(" . $this->buildWheres($v) . ")";
			}
			else
			{
				$outs[] = $v;
			}
		}

		return join(" " . $wheres["concat"] . " ", $outs);
	}

	public function select($fields)
	{
		$fields = (array)$fields;

		if ($this->selectFields == "*")
		{
			$this->selectFields = array();
		}

		$this->selectFields = array_merge($this->selectFields, $fields);

		return $this;
	}

	public function selectRaw($field, $alias = null)
	{
		$expr = $field;

		if (!is_null($alias))
		{
			$expr .= " as " . DB::conn($this->connName)->quoteID($alias);
		}

		if ($this->selectFields == "*")
		{
			$this->selectFields = array();
		}

		$this->selectFields[] = $expr;
	}

	public function where($name, $method, $value)
	{
		$this->initWheres();

		$db = DB::conn($this->connName);

		$this->currentWhere["wheres"][] = $db->quoteID($name) . " " . $method . " " . $db->escape($value);

		return $this;
	}

	public function whereEqual($name, $value)
	{
		return $this->where($name, "=", $value);
	}

	public function whereNot($name, $value)
	{
		return $this->where($name, "!=", $value);
	}

	public function whereRaw($raw)
	{
		$this->initWheres();

		$this->currentWhere["wheres"][] = $raw;

		return $this;
	}

	private function initWheres()
	{
		if (is_null($this->wheres))
		{
			$this->wheres = array(
				"concat" => "AND",
				"wheres" => array()
			);

			$this->currentWhere = &$this->wheres;
		}
	}

	public function whereGroup($concat, $callback)
	{
		$concat = Str::upper($concat);
		if ($concat != "AND" && $concat != "OR")
		{
			return false;
		}

		$this->initWheres();

		$where = array(
			"concat" => $concat,
			"wheres" => array()
		);

		$oldWhere = &$this->currentWhere;
		$this->currentWhere = &$where;

		call_user_func_array($callback, array($this));

		$oldWhere["wheres"][] = $this->currentWhere;
		$this->currentWhere = &$oldWhere;

		return $this;
	}


	public function orderByAsc($name)
	{
		$this->orderBys[] = DB::conn($this->connName)->quoteID($name) . " ASC";
		return $this;
	}

	public function orderByDesc($name)
	{
		$this->orderBys[] = DB::conn($this->connName)->quoteID($name) . " DESC";
		return $this;
	}

	public function orderByExpr($expr)
	{
		$this->orderBys[] = $expr;
		return $this;
	}


	public function groupBy($name)
	{
		$this->groupBys[] = DB::conn($this->connName)->quoteID($name);
		return $this;
	}

	public function groupByExpr($expr)
	{
		$this->groupBys[] = $expr;
		return $this;
	}


	public function limit($count)
	{
		$this->limit = $count;
		return $this;
	}

	public function offset($count)
	{
		$this->offset = $count;
		return $this;
	}

	public function count($name)
	{
		return $this->execDBFunc("count", $name);
	}

	public function max($name)
	{
		return $this->execDBFunc("max", $name);
	}

	public function min($name)
	{
		return $this->execDBFunc("min", $name);
	}

	public function avg($name)
	{
		return $this->execDBFunc("avg", $name);
	}

	public function sum($name)
	{
		return $this->execDBFunc("sum", $name);
	}

	private function execDBFunc($func, $name)
	{
		$alias = Str::lower($func);
		$func = Str::upper($func);

		if ($name != "*")
		{
			$name = DB::conn($this->connName)->quoteID($name);
		}

		$this->selectRaw($func . "(" . $name . ")", $alias);

		$rs = $this->findFirst();
		$result = 0;

		if (!$rs->EOF)
		{
			$result = $rs->fields[$alias];
		}

		array_pop($this->selectFields);

		if (count($this->selectFields) == 0)
		{
			$this->selectFields = "*";
		}

		if ((int)$result == (float)$result)
		{
			return (int)$result;
		}
		else
		{
			return (float)$result;
		}
	}

	//CRUD...

	public function __get($key)
	{
		return $this->field($key);
	}

	public function __set($key, $value)
	{
		return $this->setField($key, $value);
	}

	public function setField($name, $value)
	{
		if (is_null($this->fields))
		{
			$this->fields = array();
		}

		if (is_null($this->dirtyFields))
		{
			$this->dirtyFields = array();
		}

		$this->fields[$name] = $value;

		$this->dirtyFields[$name] = true;

		return $this;
	}

	public function field($name)
	{
		if (is_null($this->fields))
		{
			$this->fields = array();
		}

		if (!isset($this->fields[$name]))
		{
			return null;
		}

		return $this->fields[$name];
	}


	public function insert()
	{
		$this->method = "INSERT";

		return $this->run();
	}

	public function update($id = null)
	{
		if (!is_null($id))
		{
			$this->setField("id", $id);
		}

		$this->method = "UPDATE";

		return $this->run();
	}

	public function delete($id = null)
	{
		if (!is_null($id))
		{
			$this->setField("id", $id);
		}

		$this->method = "DELETE";

		return $this->run();
	}
}
?>