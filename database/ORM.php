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

	private $deleteIDs;

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

	public function findFirst($id = null)
	{
		if (!is_null($id))
		{
			$this->setField("id", $id);
			$this->where("id", "=", $id);
		}

		$this->method = "SELECT";

		$lastLimit = $this->limit;
		$this->limit(1);

		$response = $this->run();

		$this->limit($lastLimit);

		if (count($response) > 0)
		{
			return $response[0];
		}

		return false;
	}

	public function find()
	{
		$this->method = "SELECT";

		return $this->run();
	}

	public function findRS()
	{
		$this->method = "SELECT_RS";

		return $this->run();
	}

	public function findArray()
	{
		$this->method = "SELECT_ARRAY";

		return $this->run();
	}

	private function run()
	{
		$db = DB::conn($this->connName);

		switch ($this->method)
		{
			case "SELECT":
			case "SELECT_RS":
			case "SELECT_ARRAY":
				$fields = $this->selectFields;

				if (is_array($fields))
				{
					foreach ($fields as $k => $v)
					{
						if (strpos($v, "#RAW#") !== false)
						{
							$fields[$k] = substr($v, 5);
						}
						else
						{
							$fields[$k] = $db->quoteID($v);
						}
					}

					$fields = join(", ", $fields);
				}

				$sql = "SELECT " . $fields . " FROM " . $db->quoteID(J_TP . $this->tableName) . " ";

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

				//echo $sql . "\n";

				static::$lastSQL = $sql;

				$rs = $db->queryORM($sql, $this);

				if ($this->method == "SELECT_RS")
				{
					return $rs;
				}
				else if ($this->method == "SELECT_ARRAY")
				{
					return $rs->all();
				}
				else
				{
					$data = array();

					while (!$rs->EOF)
					{
						$data[] = $rs->orm;
						$rs->moveNext();
					}

					return $data;
				}

				break;
			case "INSERT":
				$fieldsInfo = $db->fieldsInfo(J_TP . $this->tableName);
				$fields = array();
				$values = array();
				foreach ($fieldsInfo as $v)
				{
					$name = $v["name"];
					if (isset($this->dirtyFields[$name]))
					{
						$fields[] = $db->quoteID($name);
						$values[] = $db->escape($this->fields[$name]);
					}
				}

				$sql = "INSERT INTO " . $db->quoteID(J_TP . $this->tableName) . " (" . join(", ", $fields) . ") VALUES (" . join(", ", $values) . ");";

				static::$lastSQL = $sql;

				$rs = $db->query($sql);

				$this->setField("id", $rs->insertID);

				return $rs->success;

				break;
			case "UPDATE":
				$sql = "UPDATE " . $db->quoteID(J_TP . $this->tableName) . " SET ";

				$fieldsInfo = $db->fieldsInfo(J_TP . $this->tableName);
				$fields = array();
				foreach ($fieldsInfo as $v)
				{
					$name = $v["name"];
					if (isset($this->dirtyFields[$name]))
					{
						$fields[] = $db->quoteID($name) . " = " . $db->escape($this->fields[$name]);
					}
				}

				$sql .= join(", ", $fields) . " ";
				$sql .= "WHERE id = " . $db->escape($this->fields["id"]) . " LIMIT 1;";

				static::$lastSQL = $sql;

				$rs = $db->query($sql);

				return $rs->success;

				break;
			case "DELETE":
				$ids = $this->deleteIDs;

				$this->deleteIDs = null;

				if (is_null($ids))
				{
					$ids = array($this->field("id"));
				}

				if (count($ids) > 0)
				{
					foreach ($ids as $k => $id)
					{
						$ids[$k] = $db->escape($id);
					}

					$sql = "DELETE FROM " . $db->quoteID(J_TP . $this->tableName) . " WHERE id IN (" . implode(",", $ids) . ") LIMIT " . count($ids) . ";";

					static::$lastSQL = $sql;

					$rs = $db->query($sql);

					return $rs->success;
				}

				return false;

				break;
			case "DELETE_MANY":
				$sql = "DELETE FROM " . $db->quoteID(J_TP . $this->tableName);

				if (!is_null($this->wheres))
				{
					$sql .= "WHERE " . $this->buildWheres($this->wheres) . " ";
				}

				$sql .= ";";

				static::$lastSQL = $sql;

				$rs = $db->query($sql);

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
		$this->selectFields = array_unique($this->selectFields);

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

		$this->selectFields[] = "#RAW#" . $expr;
		$this->selectFields = array_unique($this->selectFields);

		return $this;
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

	public function whereLike($name, $value)
	{
		return $this->where($name, "LIKE", $value);
	}

	public function whereNotLike($name, $value)
	{
		return $this->where($name, "LIKE", $value);
	}

	public function whereNull($name)
	{
		return $this->whereRaw(DB::conn($this->connName)->quoteID($name) . " IS NULL");
	}

	public function whereNotNull($name)
	{
		return $this->whereRaw(DB::conn($this->connName)->quoteID($name) . " IS NOT NULL");
	}

	public function whereIn($name, $values)
	{
		$values = (array)$values;

		$db = DB::conn($this->connName);

		foreach ($values as $k => $v)
		{
			$values[$k] = $db->escape($v);
		}

		return $this->whereRaw($db->quoteID($name) . " IN (" . implode(",", $values) . ")");
	}

	public function whereNotIn($name, $values)
	{
		$values = (array)$values;

		$db = DB::conn($this->connName);

		foreach ($values as $k => $v)
		{
			$values[$k] = $db->escape($v);
		}

		return $this->whereRaw($db->quoteID($name) . " NOT IN (" . implode(",", $values) . ")");
	}

	public function whereRaw($raw, $values = null)
	{
		$this->initWheres();

		if (!is_null($values))
		{
			$values = (array)$values;

			if (strpos($raw, "?") !== false)
			{
				$segments = explode("?", $raw);

				if (count($values) >= count($segments))
				{
					$values = array_slice($values, 0, count($segments) - 1);
				}

				$db = DB::conn($this->connName);
				$newRaw = $segments[0];
				$i = 1;
				foreach ($values as $param)
				{
					$newRaw .= $db->escape($param);
					$newRaw .= $segments[$i++];
				}

				$raw = $newRaw;
			}
		}

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

		$orm = $this->findFirst();
		$result = 0;

		if ($orm)
		{
			$result = $orm->$alias;
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

	public function __get($key)
	{
		return $this->field($key);
	}

	public function __set($key, $value)
	{
		return $this->setField($key, $value);
	}

	public function __isset($key)
	{
		return isset($this->fields[$key]);
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

	public function asArray()
	{
		$data = array();
		foreach ($this->fields as $k => $v)
		{
			$data[$k] = $v;
		}

		return $data;
	}

	public function isNew()
	{
		return !isset($this->fields["id"]);
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

	public function save()
	{
		if (!$this->isNew())
		{
			return $this->update();
		}
		else
		{
			return $this->insert();
		}
	}

	public function delete($ids = null)
	{
		if (!is_null($ids) && !is_array($ids))
		{
			return $this->delete((array)$ids);
		}

		$responses = array();

		$this->method = "DELETE";

		$this->deleteIDs = $ids;

		return $this->run();
	}

	public function deleteMany()
	{
		$this->method = "DELETE_MANY";

		return $this->run();
	}
}
?>