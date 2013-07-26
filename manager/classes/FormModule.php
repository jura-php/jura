<?php
class FormModule extends Module
{
	protected $tableName;
	protected $flags;

	private $fields;
	private $name;

	public function __construct()
	{
		$this->type = "form";
		$this->flags = "LOFCRUD";

		$this->tableName = "";
		$this->fields = array();

		$name = get_class($this);
		$this->name = Str::lower(substr($name, 0, strlen($name) - 4));
	}

	public function config($config)
	{
		$config = parent::config($config);

		$config["uri"] = str_replace("_", "", $this->name);
		$config["flags"] = $this->flags;

		$this->loadFields();

		$fields = array();

		foreach ($this->fields as $field)
		{
			$fields[] = $field->config();
		}

		$config["fields"] = $fields;

		return $config;
	}

	public function routes()
	{
		$this->loadFields();

		//TODO: Check module flags... eg.: dont allow update if it hasn't the U flag

		Router::register("GET", "manager/api/" . $this->name, function () {
			//TODO: Fazer paginação

			if (($token = User::validateToken()) !== true)
			{
				return $token;
			}

			$results = array();
			$fields = array();
			$orm = ORM::make($this->tableName)->select("id");

			foreach ($this->fields as $field)
			{
				if ($field->hasFlag("L"))
				{
					$fields[] = $field;

					if ($field->includeOnSQL())
					{
						$orm = $orm->select($field->name);
					}
				}
			}

			$rs = $orm->find();
			while (!$rs->EOF)
			{
				$values = array();

				$orm = $rs->orm;

				foreach ($fields as $field)
				{
					$values[$field->name] = $field->value($orm, "L");
				}

				if (!isset($values["id"]))
				{
					$values["id"] = (int)$rs->fields["id"];
				}

				$results[] = $values;

				$rs->moveNext();
			}

			return Response::json($results);
		});

		Router::register("GET", "manager/api/" . $this->name . "/new", function () {
			if (($token = User::validateToken()) !== true)
			{
				return $token;
			}

			$values = array();

			foreach ($this->fields as $field)
			{
				if ($field->hasFlag("C"))
				{
					$values[$field->name] = $field->format($field->defaultValue, "C");
				}
			}

			return Response::json($values);
		});

		Router::register("POST", "manager/api/" . $this->name, function () {
			if (($token = User::validateToken()) !== true)
			{
				return $token;
			}

			$orm = ORM::make($this->tableName);

			foreach ($this->fields as $field)
			{
				if ($field->hasFlag("C"))
				{
					$value = Request::post($field->name, $field->defaultValue, true);
					$value = $field->unformat($value);

					$field->save($orm, $value, "C");
				}
			}

			$orm->insert();

			foreach ($this->fields as $field)
			{
				if ($field->hasFlag("C"))
				{
					$field->afterSave($orm, "C");	
				}
			}
		});

		Router::register("GET", "manager/api/" . $this->name . "/(:num)", function ($id) {
			if (($token = User::validateToken()) !== true)
			{
				return $token;
			}

			$fields = array();
			$orm = ORM::make($this->tableName)->select("id");
			$hasUpdateFlag = false;

			foreach ($this->fields as $field)
			{
				if ($field->hasFlag("R") || $field->hasFlag("U"))
				{
					$fields[] = $field;

					if ($field->includeOnSQL())
					{
						$orm->select($field->name);	
					}
				}
			}

			$rs = $orm->findFirst($id);

			$values = array();
			$values["id"] = (int)$rs->fields["id"];

			$orm = $rs->orm;
			
			foreach ($fields as $field)
			{
				$flag = "R";

				if ($field->hasFlag("U"))
				{
					$flag = "U";
				}

				$values[$field->name] = $field->value($orm, $flag);
			}

			//TODO: Check if id exists..

			return Response::json($values);
		});

		Router::register("PUT", "manager/api/" . $this->name . "/(:num)", function ($id) {
			if (($token = User::validateToken()) !== true)
			{
				return $token;
			}

			$orm = ORM::make($this->tableName);

			foreach ($this->fields as $field)
			{
				if ($field->hasFlag("U") && Request::hasPost($field->name))
				{
					$value = Request::post($field->name, $field->defaultValue);
					$value = $field->unformat($value);

					$field->save($orm, $value, "U");
				}
			}

			$orm->update($id);

			foreach ($this->fields as $field)
			{
				if ($field->hasFlag("U"))
				{
					$field->afterSave($orm, "U");
				}
			}
		});

		Router::register("DELETE", "manager/api/" . $this->name . "/(:any)", function ($ids) {
			if (($token = User::validateToken()) !== true)
			{
				return $token;
			}

			ORM::make($this->tableName)->delete(explode('-', $ids));
		});
	}

	private function loadFields()
	{
		if (count($this->fields) == 0)
		{
			$this->fields();
		}
	}

	protected function addField($field, $flags = "LOFCRU")
	{
		$filteredFlags = "";

		for ($i = 0; $i < strlen($flags); $i++)
		{
			if (Str::contains($this->flags, $flags{$i}))
			{
				$filteredFlags .= $flags{$i};
			}
		}

		$field->flags = $filteredFlags;

		$this->fields[] = $field;
		//TODO:
	}
}
?>