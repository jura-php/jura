<?php

//TODO: Where com OR ou AND..
//TODO: Joins
//TODO: Retorno da query com um recordset associando um objeto a cada move[Next][First]()...

class ORM
{
	private $tableName;
	private $connName;

	private $method;

	private $selectFields;
	private $wheres;
	private $orderBys;
	private $groupBys;
	private $limit;
	private $offset;

	/*private $fields;
	private $dirtyFields;*/

	public static function make($tableName, $connName = null)
	{
		return new ORM($tableName, $connName);
	}

	public function __construct($tableName, $connName = null)
	{
		$this->tableName = $tableName;
		$this->connName = $connName;

		$this->reset();
	}

	public function reset()
	{
		/*$this->fields = null;
		$this->dirtyFields = null;*/

		$this->selectFields = "*";
		$this->wheres = null;
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

	public function findMany()
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

				$sql = $this->method . " " . $fields . " FROM " . DB::conn($this->connName)->quoteID($this->tableName) . " ";

				if (count($this->wheres) > 0)
				{
					$sql .= "WHERE " . join(" AND ", $this->wheres);
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

				echo $sql;
			break;
		}

		//TODO: Build select query and return a RS

		//DB::conn($this->connName)
	}

	public function select($fields)
	{
		$fields = (array)$fields;

		if ($this->selectFields == "*")
		{
			$this->selectFields = array();
		}

		$this->selectFields = array_merge($this->selectFields, $fields);
	}

	public function where($name, $method, $value)
	{
		return $this->wheres[] = DB::conn($this->connName)->quoteID($name) . $method . $value;
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
		return $this->wheres[] = $raw;
	}


	public function orderByAsc($name)
	{
		$this->orderBys[] = DB::conn($this->connName)->quoteID($name) . " ASC";
	}

	public function orderByDesc($name)
	{
		$this->orderBys[] = DB::conn($this->connName)->quoteID($name) . " DESC";
	}

	public function orderByExpr($expr)
	{
		$this->orderBys[] = $expr;
	}


	public function groupBy($name)
	{
		$this->groupBys[] = DB::conn($this->connName)->quoteID($name);
	}

	public function groupByExpr($expr)
	{
		$this->groupBys[] = $expr;
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

	//CRUD...

	public function __get($key)
	{
		return $this->field($key);
	}

	public function __set($key, $value)
	{
		return $this->setField($key, $value);
	}



	/*public function setField($name, $value)
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
	}*/
}
?>