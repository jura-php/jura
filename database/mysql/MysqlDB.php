<?php
class MysqlDB
{
	public $res;

	public function __construct($params)
	{
		Event::listen(J_EVENT_SHUTDOWN, function () {
			$this->close();
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
			echo "Can't connect to server <b>'" . $params["host"] . "'</b>"; //TODO: Error class...
			exit();
		}

		if (!@mysql_select_db($params["database"], $this->res))
		{
			echo "Can't select database <b>'" . $params["database"] . "'</b>"; //TODO: Error class...
			exit();
		}

		mysql_set_charset('utf8', $this->res);
		mysql_query("SET NAMES 'utf8'", $this->res);
        mysql_query('SET character_set_connection=utf8', $this->res);
        mysql_query('SET character_set_client=utf8', $this->res);
        mysql_query('SET character_set_results=utf8', $this->res);
	}

	public function query($query, $params = null)
	{
		$params = (array)$params;

		if (is_array($params) && strpos($query, "?") !== false)
		{
			$segments = explode("?", $query);

			if (count($params) >= count($segments))
			{
				$params = array_slice($params, 0, count($segments) - 1);
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

		return new MysqlRecordSet($query, $this);
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
		else if (is_null($value))
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
		return trim("`" . $identifier . "`", "`");
	}

	public function close()
	{
		@mysql_close($this->res);
		$this->res = null;
	}
}
?>