<?php
class FormModule extends Module
{
	public $flag;
	public $orm;
	public $tableName;

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
		$this->orm = null;

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

			$this->flag = "L";

			$page = (int)Request::get("page", 1);
			$search = Request::get("search", "");
			$order = Request::get("order", "");
			$orderBy = Request::get("orderBy", "");

			$this->orm = $this->listCountORM();

			foreach ($this->fields as $field)
			{
				if ($field->hasFlag("L"))
				{
					$field->init("L");
					$field->select();
				}
			}

			if ($search != "")
			{
				$this->orm->whereGroup("OR", function ($orm) use ($search) {
					foreach ($this->fields as $field)
					{
						if ($field->hasFlag("F"))
						{
							$field->filter($search);
						}
					}
				});
			}

			$count = $this->orm->count('id');
			$pageCount = max(1, ceil($count / $this->pageSize));
			$page = max(1, min($pageCount, $page));
			$nextPage = ($page < $pageCount) ? $page + 1 : false;
			$previousPage = ($page > 1) ? $page - 1 : false;

			$results = array();
			$fields = array();
			$this->orm = $this->listORM();

			foreach ($this->fields as $field)
			{
				if ($field->hasFlag("L"))
				{
					$fields[] = $field;

					$field->select();
				}
			}

			if ($search != "")
			{
				$this->orm->whereGroup("OR", function ($orm) use ($search) {
					foreach ($this->fields as $field)
					{
						if ($field->hasFlag("F"))
						{
							$field->filter($search);
						}
					}
				});
			}

			$entries = $this->orm
						->offset(($page - 1) * $this->pageSize)
						->limit($this->pageSize);


			if($orderBy != ""){
				if($order == 'ASC') {
					$entries->orderByAsc($orderBy);
				} else {
					$entries->orderByDesc($orderBy);
				}
			}

			foreach ($entries->find() as $entry)
			{
				$this->orm = $entry;

				$values = array();
				$values["id"] = (int)$entry->id;

				foreach ($fields as $field)
				{
					$values[$field->name] = $field->value("L");
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

			$this->flag = "C";

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

			$this->flag = "C";

			$this->orm = ORM::make($this->tableName);

			foreach ($this->fields as $field)
			{
				if ($field->hasFlag("C"))
				{
					$field->init("C");

					$value = $field->unformat(Request::post($field->name, $field->defaultValue, true));
					$field->save($value, "C");
				}
			}

			$this->save("C");

			$this->orm->insert();

			foreach ($this->fields as $field)
			{
				if ($field->hasFlag("C"))
				{
					$field->afterSave("C");
				}
			}

			$this->afterSave("C");
		});

		Router::register("GET", "manager/api/" . $this->name . "/(:num)", function ($id) {
			if (($token = User::validateToken()) !== true)
			{
				return $token;
			}

			$this->flag = "R";

			$fields = array();
			$this->orm = $this->orm = ORM::make($this->tableName)
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
						$this->orm->select($field->name);
					}
				}
			}

			$this->orm = $this->orm->findFirst($id);

			if ($this->orm)
			{
				$values = array();
				$values["id"] = (int)$this->orm->id;

				foreach ($fields as $field)
				{
					$flag = "R";

					if ($field->hasFlag("U"))
					{
						$flag = "U";
					}

					$values[$field->name] = $field->value($flag);
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

			$this->flag = "U";

			$this->orm = ORM::make($this->tableName)
										->findFirst($id);

			foreach ($this->fields as $field)
			{
				if ($field->hasFlag("U") && Request::hasPost($field->name))
				{
					$field->init("U");

					$value = $field->unformat(Request::post($field->name, $field->defaultValue));
					$field->save($value, "U");
				}
			}

			$this->save("U");

			$this->orm->update($id);

			foreach ($this->fields as $field)
			{
				if ($field->hasFlag("U"))
				{
					$field->afterSave("U");
				}
			}

			$this->afterSave("U");
		});

		Router::register("DELETE", "manager/api/" . $this->name . "/(:any)", function ($ids) {
			if (($token = User::validateToken()) !== true)
			{
				return $token;
			}

			$this->flag = "D";

			$ids = explode('-', $ids);

			$orm = ORM::make($this->tableName);

			foreach ($ids as $id)
			{
				$this->orm = $orm->findFirst($id);

				foreach ($this->fields as $field)
				{
					$field->save("", "D");
				}

				$this->save("D");

				$this->orm->delete();

				foreach ($this->fields as $field)
				{
					$field->afterSave("D");
				}

				$this->afterSave("D");
			}
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

		$field->module = $this;
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

	protected function save($flag)
	{

	}

	protected function afterSave($flag)
	{

	}
}
?>