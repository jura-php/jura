<?php

//OK: Where com OR ou AND..
//OK: Retorno da query com um recordset associando um objeto a cada move[Next][First]()...
//OK: Joins

class ORM implements ArrayAccess
{
	private static $lastSQL = "";

	public $tableName;
	private $connName;
	public $className;

	private $method;

	private $selectFields;
	private $joins;
	private $wheres;
	private $currentWhere;
	private $orderBys;
	private $groupBys;
	private $limit;
	private $offset;

	private $fields;
	private $dirtyFields;

	private $deleteIDs;

	private $tableAliases;

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
		$this->className = "ORM";

		$this->reset();
	}

	public function reset()
	{
		$this->fields = null;
		$this->dirtyFields = null;

		$this->selectFields = "*";
		$this->joins = null;
		$this->wheres = null;
		$this->currentWhere = null;
		$this->orderBys = null;
		$this->groupBys = null;
		$this->limit = 0;
		$this->offset = 0;
		$this->tableAliases = null;

		return $this;
	}

	public function emptyCopy()
	{
		return new $this->className($this->tableName, $this->connName);
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

	private function run()
	{
		$db = DB::conn($this->connName);

		switch ($this->method)
		{
			case "SELECT":
			case "SELECT_RS":
				$fields = $this->selectFields;

				if (is_array($fields))
				{
					foreach ($fields as $k => $v)
					{
						if (strpos($v, "#RAW#") !== false)
						{
							$fields[$k] = substr($v, 5);
						}
						else if (strpos($v, "#AS#") !== false)
						{
							$pieces = explode("#AS#", $v);
							$fields[$k] = $this->quoteField($pieces[0]) . " as " . $db->quoteID($pieces[1]);
						}
						else
						{
							$fields[$k] = $this->quoteField($v);
						}
					}

					$fields = join(", ", $fields);
				}

				$sql = "SELECT " . $fields . " FROM " . $db->quoteID(J_TP . $this->tableName) . " ";

				if (!is_null($this->joins))
				{
					natsort($this->joins);

					foreach ($this->joins as $k => $join)
					{
						$pieces = explode("#", $join);
						array_splice($pieces, 0, 1);

						$this->joins[$k] = implode("", $pieces);
					}

					$sql .= implode(" ", $this->joins) . " ";
				}

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
				else
				{
					$data = array();

					while (!$rs->EOF)
					{
						$data[] = $rs->orm;
						$rs->moveNext();
					}

					return new ORMResult($data);
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

				if ($rs->success)
				{
					$this->dirtyFields = array();
				}

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

				if ($rs->success)
				{
					$this->dirtyFields = array();
				}

				return $rs->success;

				break;
			case "DELETE":
				$ids = $this->deleteIDs;

				$this->deleteIDs = null;

				if (is_null($ids))
				{
					$ids = array($this->field("id"));
				}

				$idsLength = count($ids);
				if ($idsLength > 0)
				{
					foreach ($ids as $k => $id)
					{
						$ids[$k] = $db->escape($id);
					}

					$sql = "DELETE FROM " . $db->quoteID(J_TP . $this->tableName) . " WHERE id IN (" . implode(",", $ids) . ") LIMIT " . $idsLength . ";";

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

	public function quoteField($name)
	{
		$bypassPrefix = false;

		if ((!is_null($this->joins) && count($this->joins) > 0) || !is_null($this->tableAliases))
		{
			if (strpos($name, ".") === false)
			{
				$name = $this->tableName . "." . $name;
			}
			else if (!is_null($this->tableAliases))
			{
				$pieces = explode(".", $name);

				if (array_search($pieces[0], $this->tableAliases) !== false)
				{
					$bypassPrefix = true;
				}
			}
		}

		if (!$bypassPrefix && (strpos($name, ".") !== false && J_TP != "" && strpos($name, J_TP) !== 0))
		{
			$name = J_TP . $name;
		}

		return DB::conn($this->connName)->quoteID($name);
	}

	public function select($fields, $alias = null)
	{
		$fields = (array)$fields;

		if ($this->selectFields == "*")
		{
			$this->selectFields = array();
		}

		if (count($fields) == 1 && !is_null($alias))
		{
			$fields = array($fields[0] . "#AS#" . $alias);
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

	private function addJoin($operator, $tableName, $constraints, $tableAlias = null, $order = -1)
	{
		$db = DB::conn($this->connName);

		$operator = trim($operator . " JOIN");
		$tableName = $db->quoteID(J_TP . $tableName);

		if (!is_null($tableAlias))
		{
			if (is_null($this->tableAliases))
			{
				$this->tableAliases = array();
			}

			$this->tableAliases[] = $tableAlias;
		}

		if (!is_null($tableAlias))
		{
			$tableName .= " " . $db->quoteID($tableAlias);
		}

		if (is_array($constraints))
		{
			$constraintsString = "";
			$i = 0;
			foreach ($constraints as $v)
			{
				if ($i > 0)
				{
					$constraintsString .= " ";
				}

				if ($i % 2 == 0)
				{
					/*if (strpos($v, ".") !== false)
					{
						$v = J_TP . $v;
					}*/

					$constraintsString .= $this->quoteField($v);
				}
				else
				{
					$constraintsString .= $v;
				}

				$i++;
			}

			$constraints = $constraintsString;
		}

		if (is_null($this->joins))
		{
			$this->joins = array();
		}

		if ($order == -1)
		{
			$order = count($this->joins);
		}

		$this->joins[] = $order . "#" . $operator . " " . $tableName . " ON (" . $constraints . ")";

		return $this;
	}

	public function join($tableName, $constraints, $tableAlias = null, $order = -1)
	{
		return $this->addJoin("", $tableName, $constraints, $tableAlias, $order);
	}

	public function innerJoin($tableName, $constraints, $tableAlias = null, $order = -1)
	{
		return $this->addJoin("INNER", $tableName, $constraints, $tableAlias, $order);
	}

	public function leftJoin($tableName, $constraints, $tableAlias = null, $order = -1)
	{
		return $this->addJoin("LEFT OUTER", $tableName, $constraints, $tableAlias, $order);
	}

	public function rightJoin($tableName, $constraints, $tableAlias = null, $order = -1)
	{
		return $this->addJoin("RIGHT OUTER", $tableName, $constraints, $tableAlias, $order);
	}

	/*public function fullOutherJoin($tableName, $constraints, $tableAlias = null, $order = -1)
	{
		return $this->addJoin("FULL", $tableName, $constraints, $tableAlias, $order);
	}*/

	public function where($name, $method, $value = null)
	{
		if (is_null($value))
		{
			$this->whereEqual($name, $method);
		}
		else
		{
			$this->initWheres();

			$this->currentWhere["wheres"][] = $this->quoteField($name) . " " . $method . " " . DB::conn($this->connName)->escape($value);
		}

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
		return $this->whereRaw($this->quoteField($name) . " IS NULL");
	}

	public function whereNotNull($name)
	{
		return $this->whereRaw($this->quoteField($name) . " IS NOT NULL");
	}

	public function whereIn($name, $values)
	{
		$values = (array)$values;

		$db = DB::conn($this->connName);

		foreach ($values as $k => $v)
		{
			$values[$k] = $db->escape($v);
		}

		return $this->whereRaw($this->quoteField($name) . " IN (" . implode(",", $values) . ")");
	}

	public function whereNotIn($name, $values)
	{
		$values = (array)$values;

		$db = DB::conn($this->connName);

		foreach ($values as $k => $v)
		{
			$values[$k] = $db->escape($v);
		}

		return $this->whereRaw($this->quoteField($name) . " NOT IN (" . implode(",", $values) . ")");
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
				$segmentsLength = count($segments);

				if (count($values) >= $segmentsLength)
				{
					$values = array_slice($values, 0, $segmentsLength - 1);
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
		$this->orderBys[] = $this->quoteField($name) . " ASC ";
		return $this;
	}

	public function orderByDesc($name)
	{
		$this->orderBys[] = $this->quoteField($name) . " DESC ";
		return $this;
	}

	public function orderByExpr($expr)
	{
		$this->orderBys[] = $expr . " ";
		return $this;
	}

	public function groupBy($name)
	{
		$this->groupBys[] = $this->quoteField($name);
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

	public function count($name = 'id')
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
			$name = $this->quoteField($name);
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

	public function asArray()
	{
		if (func_num_args() === 0)
		{
			return $this->fields;
		}
		return array_intersect_key($this->fields, array_flip(func_get_args()));
	}

	public function isDirty($name)
	{
		return isset($this->dirtyFields[$name]) && $this->dirtyFields[$name] == true;
	}

	public function isNew()
	{
		return !isset($this->fields["id"]);
	}

	public function connName()
	{
		return $this->connName;
	}

	public function fieldNames()
	{
		if (is_null($this->fields))
		{
			return array();
		}

		$names = array();

		foreach ($this->fields as $k => $v)
		{
			$names[] = $k;
		}

		return $names;
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

	public function setFields($fields)
	{
		foreach ($fields as $key => $value)
		{
			$this->setField($key, $value);
		}

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

	public function offsetExists($key)
	{
		return isset($this->fields[$key]);
		return isset($this->fields[$offset]);
	}

	public function offsetGet($key)
	{
		return $this->field($key);
	}

	public function offsetSet($key, $value)
	{
		return $this->setField($key, $value);
	}

	public function offsetUnset($key)
	{
		unset($this->fields[$key]);
	}
}

class ORMResult implements Countable, IteratorAggregate, ArrayAccess, Serializable
{
	protected $data;

	public function __construct($data)
	{
		$this->data = $data;
	}

	public function asArray($fields = true)
	{
		if (!$fields)
		{
			return $this->data;
		}

		$data = array();

		foreach ($this->data as $orm)
		{
			$data[] = $orm->asArray();
		}

		return $data;
	}

	public function count()
	{
		return count($this->data);
	}

	public function getIterator()
	{
		return new ArrayIterator($this->data);
	}

	public function offsetExists($offset)
	{
		return isset($this->data[$offset]);
	}

	public function offsetGet($offset)
	{
		return $this->data[$offset];
	}

	public function offsetSet($offset, $value)
	{
		$this->data[$offset] = $value;
	}

	public function offsetUnset($offset)
	{
		unset($this->data[$offset]);
	}

	public function serialize()
	{
		return serialize($this->data);
	}

	public function unserialize($serialized)
	{
		$this->data = $serialized;
	}

	public function __call($method, $params = array())
	{
		foreach ($this->data as $orm)
		{
			call_user_func_array(array($orm, $method), $params);
		}
		return $this;
	}
}
?>