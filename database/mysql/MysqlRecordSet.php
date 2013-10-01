<?php
class MysqlRecordSet
{
	public $conn;
	public $res;
	private $orm;
	private $rowOrm;

	public $BOF;
	public $EOF;
	public $index;
	public $count;

	public $success;

	public $fields;

	public $insertID;

	public function __construct($query, &$conn, &$orm = null)
	{
		$this->conn = $conn;
		$this->res = null;
		$this->orm = $orm;
		$this->rowOrm = null;

		$this->success = false;
		$this->fields = null;
		$this->EOF = true;
		$this->BOF = true;
		$this->index = 0;
		$this->count = 0;
		$this->insertID = 0;

		$type = strtolower(substr(trim(str_replace("\n", " ", $query)), 0, 7));

		$typeIsSelect = $type == "select ";
		$typeIsShowTables = $type == "show ta";
		$typeIsInsert = $type == "insert ";

		$query = Str::finish($query, ";");

		if ($this->res = @mysql_query($query, $this->conn->res))
		{
			$this->success = true;

			if ($typeIsSelect || $typeIsShowTables)
			{
				$this->count = mysql_num_rows($this->res);

				$this->moveFirst();
			}
			else if ($typeIsInsert)
			{
				$this->insertID = mysql_insert_id($this->conn->res);
			}
		}
		else
		{
			trigger_error("SQL error: " . $query . "\n<br><b>'" . mysql_error($this->conn->res) . "'</b>");
		}
	}

	public function moveFirst()
	{
		$this->BOF = true;

		$this->index = -1;
		$this->moveNext();
	}

	public function moveNext()
	{
		if ($this->index >= ($this->count - 1))
		{
			$this->EOF = true;

			return;
		}

		$this->index++;

		$this->fetch();
	}

	public function movePrevious()
	{
		if ($this->index < 1)
		{
			$this->BOF = true;

			return;
		}

		$this->index--;

		$this->fetch();
	}

	public function all()
	{
		$all = array();

		$this->moveFirst();

		while (!$this->EOF)
		{
			$all[] = $this->fields;

			$this->moveNext();
		}

		return $all;
	}

	public function __get($key)
	{
		if ($key == "fields" && $this->BOF && $this->EOF)
		{
			trigger_error("SQL error: <br>Trying to access a field of an empty RecordSet");

			return null;
		}

		if ($key == "orm")
		{
			if (is_null($this->rowOrm))
			{
				$this->rowOrm = $this->orm->emptyCopy();

				foreach ($this->fields as $k => $v)
				{
					$this->rowOrm->$k = $v;
				}
			}

			return $this->rowOrm;
		}

		if (isset($this->fields[$key]))
		{
			return $this->fields[$key];
		}

		return @$this->$key;
	}

	public function close()
	{
		mysql_free_result($this->res);
	}

	private function fetch()
	{
		mysql_data_seek($this->res, $this->index);

		unset($this->fields);
		$this->fields = null;

		unset($this->rowOrm);
		$this->rowOrm = null;

		if (!($this->fields = mysql_fetch_assoc($this->res)))
		{
			$this->EOF = true;
		}
		else
		{
			$this->EOF = false;
		}
	}
}
?>