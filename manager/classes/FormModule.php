<?php
class FormModule extends Module
{
	protected $tableName;
	protected $flags;
	protected $pageSize;

	private $fields;
	private $name;

	public function __construct()
	{
		$this->type = "form";
		$this->flags = "LOFCRUD";

		$this->tableName = "";
		$this->fields = array();
		$this->pageSize = 20;

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

		Router::register("GET", array("manager/api/" . $this->name), function () {
			if (($token = User::validateToken()) !== true)
			{
				return $token;
			}

			$page = (int)Request::get("page", 1);
			$search = Request::get("search", "");

			$orm = $this->listCountORM();

			foreach ($this->fields as $field)
			{
				if ($field->hasFlag("L"))
				{
					$field->listORM($orm);
				}
			}

			if ($search != "")
			{
				$orm->whereGroup("OR", function ($orm) use ($search) {
					foreach ($this->fields as $field)
					{
						if ($field->hasFlag("F"))
						{
							$field->filterORM($orm, $search);
						}
					}
				});
			}

			$count = $orm->count('id');
			$pageCount = max(1, ceil($count / $this->pageSize));
			$page = max(1, min($pageCount, $page));
			$nextPage = ($page < $pageCount) ? $page + 1 : false;
			$previousPage = ($page > 1) ? $page - 1 : false;

			$results = array();
			$fields = array();
			$orm = $this->listORM();

			foreach ($this->fields as $field)
			{
				if ($field->hasFlag("L"))
				{
					$fields[] = $field;

					$field->listORM($orm);
				}
			}

			if ($search != "")
			{
				$orm->whereGroup("OR", function ($orm) use ($search) {
					foreach ($this->fields as $field)
					{
						if ($field->hasFlag("F"))
						{
							$field->filterORM($orm, $search);
						}
					}
				});
			}

			$entries = $orm
						->offset(($page - 1) * $this->pageSize)
						->limit($this->pageSize)
						->find();
			foreach ($entries as $entry)
			{
				$values = array();

				$values["id"] = (int)$entry->id;

				foreach ($fields as $field)
				{
					$values[$field->name] = $field->value($entry, "L");
				}

				$results[] = $values;
			}

			//echo ORM::lastSQL();
			//die();

			return Response::json(array(
				"search" => "",
				"pagination" => array(
					"count" => $pageCount,
					"current" => $page,
					"next" => $nextPage,
					"previous" => $previousPage,
				),
				"count" => $count,
				"data" => $results
			));
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
					$field->init("C");

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
					$field->init("C");

					$value = Request::post($field->name, $field->defaultValue, true);
					$value = $field->unformat($value);

					$field->save($orm, $value, "C");
				}
			}

			$this->save($orm, "C");

			$orm->insert();

			foreach ($this->fields as $field)
			{
				if ($field->hasFlag("C"))
				{
					$field->afterSave($orm, "C");
				}
			}

			$this->afterSave($orm, "C");
		});

		Router::register("GET", "manager/api/" . $this->name . "/(:num)", function ($id) {
			if (($token = User::validateToken()) !== true)
			{
				return $token;
			}

			$fields = array();
			$orm = ORM::make($this->tableName)
						->select("id");
			$hasUpdateFlag = false;

			foreach ($this->fields as $field)
			{
				if ($field->hasFlag("R") || $field->hasFlag("U"))
				{
					$field->init($field->hasFlag("U") ? "U" : "R");

					$fields[] = $field;

					if ($field->includeOnSQL())
					{
						$orm->select($field->name);
					}
				}
			}

			$orm = $orm->findFirst($id);

			if ($orm)
			{
				$values = array();
				$values["id"] = (int)$orm->id;
				
				foreach ($fields as $field)
				{
					$flag = "R";

					if ($field->hasFlag("U"))
					{
						$flag = "U";
					}

					$values[$field->name] = $field->value($orm, $flag);
				}

				return Response::json($values);
			}

			return Response::code(404);
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
					$field->init("U");

					$value = Request::post($field->name, $field->defaultValue);
					$value = $field->unformat($value);

					$field->save($orm, $value, "U");
				}
			}

			$this->save($orm, "U");

			$orm->update($id);

			foreach ($this->fields as $field)
			{
				if ($field->hasFlag("U"))
				{
					$field->afterSave($orm, "U");
				}
			}

			$this->afterSave($orm, "U");
		});

		Router::register("DELETE", "manager/api/" . $this->name . "/(:any)", function ($ids) {
			if (($token = User::validateToken()) !== true)
			{
				return $token;
			}

			//TODO: Check all saving behaviors for delete, we have to delete each id separated, selecting the orm, etc..

			$this->save($orm, "D");

			ORM::make($this->tableName)->delete(explode('-', $ids));

			foreach ($this->fields as $field)
			{
				$field->afterSave($orm, "D");
			}

			$this->afterSave($orm, "D");
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
	}

	protected function listORM()
	{
		return $orm = ORM::make($this->tableName)
						->select("id");
	}

	protected function listCountORM()
	{
		return ORM::make($this->tableName);
	}

	protected function save($orm, $flag)
	{

	}

	protected function afterSave($orm, $flag)
	{

	}
}
?>