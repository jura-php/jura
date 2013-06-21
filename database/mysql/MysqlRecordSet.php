<?php
class MysqlRecordSet
{
	public $conn;
	public $res;

	public $BOF;
	public $EOF;
	public $index;
	public $count;

	public $success;

	public $fields;

	public $inserID;

	public function __construct($query, &$conn)
	{
		$this->conn = $conn;
		$this->res = null;

		$this->success = false;
		$this->fields = null;
		$this->EOF = true;
		$this->BOF = true;
		$this->index = 0;
		$this->count = 0;

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
				$this->insertID = mysql_insert_id($this->res);
			}
		}
		else
		{
			echo "SQL error: <br> <b>'" . mysql_error($this->conn->res) . "'</b>"; //TODO: Error class
			die();
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

	public function __get($key)
	{
		if ($key == "fields" && $this->BOF && $this->EOF)
		{
			echo "SQL error: <br>Trying to access a field of an empty RecordSet"; //TODO: Error class

			return null;
		}

		return $this->$key;
	}

	public function close()
	{
		mysql_free_result($this->res);
	}

	private function fetch()
	{
		mysql_data_seek($this->res, $this->index);

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