<?php
class MysqlDB
{
	public $res;

	private $fieldsInfoCache;
	private $lastQuery;

	public function __construct($params)
	{
		$that = $this;
		Event::listen(J_EVENT_SHUTDOWN, function () use ($that) {
			$that->close();
		});

		if (isset($params["persistent"]) && $params["persistent"] == true)
		{
			$this->res = @mysql_pconnect($params["host"], $params["user"], $params["pass"]);
		}
		else
		{
			$this->res = @mysql_connect($params["host"], $params["user"], $params["pass"]);
		}

		if (!$this->res)
		{
			trigger_error("Can't connect to server <b>'" . $params["host"] . "'</b>");
		}

		if (!@mysql_select_db($params["database"], $this->res))
		{
			trigger_error("Can't select database <b>'" . $params["database"] . "'</b>");
		}

		mysql_set_charset('utf8', $this->res);
		mysql_query("SET NAMES 'utf8'; SET character_set_connection=utf8; SET character_set_client=utf8; SET character_set_results=utf8; SET character_set_database=utf8; SET character_set_server=utf8", $this->res);
	}

	public function query($query, $params = null)
	{
		$params = (array)$params;

		if (is_array($params) && count($params) > 0 && strpos($query, "?") !== false)
		{
			$segments = explode("?", $query);
			$segmentsLength = count($segments);

			if (count($params) >= $segmentsLength)
			{
				$params = array_slice($params, 0, $segmentsLength - 1);
			}

			$newQuery = $segments[0];
			$i = 1;
			foreach ($params as $param)
			{
				$newQuery .= $this->escape($param);
				$newQuery .= $segments[$i++];
			}

			$query = $newQuery;
		}

		$this->lastQuery = $query;

		return new MysqlRecordSet($query, $this);
	}

	public function queryORM($query, &$orm)
	{
		return new MysqlRecordSet($query, $this, $orm);
	}

	public function lastQuery()
	{
		return $this->lastQuery;
	}

	public function escape($value)
	{
		if (is_string($value))
		{
			return "'" . mysql_real_escape_string($value, $this->res) . "'";
		}
		else if (is_numeric($value) && round($value) != $value)
		{
			return number_format($value, 10, ".", "");
		}
		else if (is_numeric($value))
		{
			return (int)$value;
		}
		else if (is_null($value) || empty($value))
		{
			return "NULL";
		}
		else
		{
			return 0;
		}
	}

	public function quoteID($identifier)
	{
		$parts = explode(".", $identifier);

		foreach ($parts as $k => $v)
		{
			if ($v == "*")
			{
				continue;
			}

			$parts[$k] = "`" . trim($v, "`") . "`";
		}

		return implode(".", $parts);
	}

	public function fieldsInfo($tableName)
	{
		if (!isset($this->fieldsInfoCache[$tableName]))
		{
			$fieldsInfo = array();
			$sql = "SHOW COLUMNS FROM " . $tableName . ";";
			$res = mysql_query($sql);
			while ($d = mysql_fetch_assoc($res))
			{
				$info = array();

				$info["name"] = $d["Field"];
				$info["nullAccept"] = ($d["Null"] == "YES");
				$info["keyType"] = $d["Key"];
				$info["default"] = $d["Default"];
				$info["autoIncrement"] = (strpos($d["Extra"], "auto_increment") !== false);
				$info["type"] = $d["Type"];

				$fieldsInfo[] = $info;
			}
			mysql_free_result($res);

			$this->fieldsInfoCache[$tableName] = $fieldsInfo;
		}

		return $this->fieldsInfoCache[$tableName];
	}

	public function close()
	{
		@mysql_close($this->res);
		$this->res = null;
	}
}
?>