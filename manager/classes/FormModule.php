<?php
class FormModule extends Module
{
	public $flag;
	public $orm;
	public $tableName;
	public $uniqueID;

	public $flags;
	public $pageSize;
	public $order;
	public $orderBy;
	public $name;

	public $fields;
	public $buttons;

	public function __construct()
	{
		$this->type = "form";
		$this->flags = "LOFCRUD";

		$this->tableName = "";
		$this->fields = array();
		$this->pageSize = 20;
		$this->orm = null;
		$this->order = "ASC";
		$this->orderBy = "";
		$this->buttons = array();
		$this->uniqueID = false;

		$name = get_class($this);
		$this->name = Str::lower(substr($name, 0, strlen($name) - 4));
	}

	public function config($config)
	{
		$config = parent::config($config);

		$config["uri"] = str_replace("_", "", $this->name);
		$config["flags"] = $this->flags;
		$config["order"] = $this->order;
		$config["orderBy"] = $this->orderBy;
		$config["uniqueID"] = $this->uniqueID;

		$buttons = array();
		foreach ($this->buttons as $button)
		{
			$info = array(
				"type" => $button["type"],
				"flags" => $button["flags"],
				"label" => $button["label"],
				"icon" => $button["icon"]
			);

			if ($button["type"] == "export" || $button["type"] == "request")
			{
				$info["url"] = $button["url"];
			}

			if ($button["type"] == "redirect")
			{
				$info["url"] = $button["callback"];
			}

			if ($button["type"] == "redirectWithParam")
			{
				$button["type"] = "redirect";
				$info["url"] = $button["callback"];
				$info["param"] = $button["additional_data"];
			}

			$buttons[] = $info;
		}
		$config["buttons"] = $buttons;

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

		$that = $this;

		Router::register("GET", array("manager/api/" . $this->name), function () use ($that) {
			if (($token = User::validateToken()) !== true)
			{
				return $token;
			}

			$that->flag = "L";

			if (!$that->hasFlag($that->flag))
			{
				return Response::json(array(
					"error" => true,
					"error_description" => "Operação não permitida."
				));
			}

			$page = (int)Request::get("page", 1);
			$search = Request::get("search", "");
			$order = Request::get("order", $that->order);
			$orderBy = Request::get("orderBy", $that->orderBy);

			$that->orm = $that->listCountORM();

			foreach ($that->fields as $field)
			{
				if ($field->hasFlag("L"))
				{
					$field->init();
					$field->select();
				}
			}

			if ($search != "")
			{
				$that->orm->whereGroup("OR", function ($orm) use ($search, $that) {
					foreach ($that->fields as $field)
					{
						if ($field->hasFlag("F"))
						{
							$field->filter($search);
						}
					}
				});
			}

			$count = $that->orm->count('id');

			$pageCount = max(1, ceil($count / $that->pageSize));
			$page = max(1, min($pageCount, $page));
			$nextPage = ($page < $pageCount) ? $page + 1 : false;
			$previousPage = ($page > 1) ? $page - 1 : false;

			$results = array();
			$fields = array();
			$that->orm = $that->listORM();

			foreach ($that->fields as $field)
			{
				if ($field->hasFlag("L"))
				{
					$fields[] = $field;

					$field->select();
				}
			}

			if ($search != "")
			{
				$that->orm->whereGroup("OR", function ($orm) use ($search, $that) {
					foreach ($that->fields as $field)
					{
						if ($field->hasFlag("F"))
						{
							$field->filter($search);
						}
					}
				});
			}

			$entries = $that->orm
						->offset(($page - 1) * $that->pageSize)
						->limit($that->pageSize);


			if ($orderBy != "")
			{
				if ($order == "ASC")
				{
					$entries->orderByAsc($orderBy);
				}
				else
				{
					$entries->orderByDesc($orderBy);
				}
			}

			foreach ($entries->find() as $entry)
			{
				$that->orm = $entry;

				$values = array();
				$values["id"] = (int)$entry->id;

				foreach ($fields as $field)
				{
					$values[$field->name] = $field->value();
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
				"order" => $order,
				"orderBy" => $orderBy,
				"data" => $results
			));
		});

		Router::register("PATCH", "manager/api/" . $this->name . "/(:num)", function ($id) use ($that) {
			if (($token = User::validateToken()) !== true)
			{
				return $token;
			}

			$that->flag = "U";

			if (!$that->hasFlag($that->flag))
			{
				return Response::json(array(
					"error" => true,
					"error_description" => "Operação não permitida."
				));
			}

			$that->orm = ORM::make($that->tableName)
										->findFirst($id);

			foreach ($that->fields as $field)
			{
				if ($field->hasFlag("L") && $field->hasFlag("U") && Request::hasPost($field->name))
				{
					$field->init();

					$value = $field->unformat(Request::post($field->name, $field->defaultValue));
					if ($return = $field->save($value))
					{
						return $return;
					}
				}
			}

			if ($return = $that->save())
			{
				return $return;
			}

			$that->orm->update($id);

			foreach ($that->fields as $field)
			{
				if ($field->hasFlag("L") && $field->hasFlag("U"))
				{
					if ($return = $field->afterSave())
					{
						return $return;
					}
				}
			}

			if ($return = $that->afterSave())
			{
				return $return;
			}
		});

		Router::register("GET", "manager/api/" . $this->name . "/new", function () use ($that) {
			if (($token = User::validateToken()) !== true)
			{
				return $token;
			}

			$that->flag = "C";

			if (!$that->hasFlag($that->flag))
			{
				return Response::json(array(
					"error" => true,
					"error_description" => "Operação não permitida."
				));
			}

			$values = array();

			foreach ($that->fields as $field)
			{
				if ($field->hasFlag("C"))
				{
					$field->init();

					$values[$field->name] = $field->format($field->defaultValue);
				}
			}

			return Response::json($values);
		});

		Router::register("POST", "manager/api/" . $this->name, function () use ($that) {
			if (($token = User::validateToken()) !== true)
			{
				return $token;
			}

			$that->flag = "C";

			if (!$that->hasFlag($that->flag))
			{
				return Response::json(array(
					"error" => true,
					"error_description" => "Operação não permitida."
				));
			}

			$that->orm = ORM::make($that->tableName);

			foreach ($that->fields as $field)
			{
				if ($field->hasFlag("C"))
				{
					$field->init();

					$value = $field->unformat(Request::post($field->name, $field->defaultValue, true));
					if ($return = $field->save($value))
					{
						return $return;
					}
				}
			}

			if ($return = $that->save())
			{
				return $return;
			}

			$that->orm->insert();

			foreach ($that->fields as $field)
			{
				if ($field->hasFlag("C"))
				{
					if ($return = $field->afterSave())
					{
						return $return;
					}
				}
			}

			if ($return = $that->afterSave())
			{
				return $return;
			}
		});

		Router::register("GET", "manager/api/" . $this->name . "/(:num)", function ($id) use ($that) {
			if (($token = User::validateToken()) !== true)
			{
				return $token;
			}

			$that->flag = "R";

			if (!$that->hasFlag($that->flag))
			{
				return Response::json(array(
					"error" => true,
					"error_description" => "Operação não permitida."
				));
			}

			$fields = array();
			$that->orm = $that->orm = ORM::make($that->tableName)
						->select("id");
			$hasUpdateFlag = false;

			foreach ($that->fields as $field)
			{
				if ($field->hasFlag("R") || $field->hasFlag("U"))
				{
					$field->init();

					$fields[] = $field;

					if ($field->includeOnSQL())
					{
						$that->orm->select($field->name);
					}
				}
			}

			$that->orm = $that->orm->findFirst($id);

			$moduleFlag = $that->flag;

			if ($that->orm)
			{
				$values = array();
				$values["id"] = (int)$that->orm->id;

				foreach ($fields as $field)
				{
					$that->flag = "R";

					if ($field->hasFlag("U"))
					{
						$that->flag = "U";
					}

					$values[$field->name] = $field->value();

					$that->flag = $moduleFlag;
				}

				return Response::json($values);
			}

			return Response::code(404);
		});

		Router::register("PUT", "manager/api/" . $this->name . "/(:num)", function ($id) use ($that) {
			if (($token = User::validateToken()) !== true)
			{
				return $token;
			}

			$that->flag = "U";

			if (!$that->hasFlag($that->flag))
			{
				return Response::json(array(
					"error" => true,
					"error_description" => "Operação não permitida."
				));
			}

			$that->orm = ORM::make($that->tableName)
										->findFirst($id);

			foreach ($that->fields as $field)
			{
				if ($field->hasFlag("U") && Request::hasPost($field->name))
				{
					$field->init();

					$value = $field->unformat(Request::post($field->name, $field->defaultValue));
					if ($return = $field->save($value))
					{
						return $return;
					}
				}
			}

			if ($return = $that->save())
			{
				return $return;
			}

			$that->orm->update($id);

			foreach ($that->fields as $field)
			{
				if ($field->hasFlag("U"))
				{
					if ($return = $field->afterSave())
					{
						return $return;
					}
				}
			}

			if ($return = $that->afterSave())
			{
				return $return;
			}
		});

		Router::register("DELETE", "manager/api/" . $this->name . "/(:any)", function ($ids) use ($that) {
			if (($token = User::validateToken()) !== true)
			{
				return $token;
			}

			$that->flag = "D";

			if (!$that->hasFlag($that->flag))
			{
				return Response::json(array(
					"error" => true,
					"error_description" => "Operação não permitida."
				));
			}

			$ids = explode('-', $ids);

			$orm = ORM::make($that->tableName);

			foreach ($ids as $id)
			{
				$that->orm = $orm->reset()->findFirst($id);

				if ($that->orm)
				{
					foreach ($that->fields as $field)
					{
						if ($return = $field->save(""))
						{
							return $return;
						}
					}

					if ($return = $that->save())
					{
						return $return;
					}

					$that->orm->delete();

					foreach ($that->fields as $field)
					{
						if ($return = $field->afterSave())
						{
							return $return;
						}
					}

					if ($return = $that->afterSave())
					{
						return $return;
					}
				}
			}
		});
	}

	public function hasFlag($flag)
	{
		return (Str::contains($this->flags, $flag));
	}

	private function loadFields()
	{
		if (Str::contains($this->flags, "U") && !Str::contains($this->flags, "R"))
		{
			$this->flags .= "R";
		}

		if (count($this->fields) == 0)
		{
			$this->fields();
		}
	}

	protected function addField($field, $flags = "LOFCRU")
	{
		if (Str::contains($flags, "U") && !Str::contains($flags, "R"))
		{
			$flags .= "R";
		}

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

	/*
	Examples:

	$this->button("print", "LR");

	$this->button("export", "LR", null, null, function () {
		return Response::downloadContent("content", "export.csv");
	});

	$this->button("redirect", "LR", "Usuários", null, "/registers");

	$this->button("request", "LR", "Request", null, function () {
		return Response::json(array(
			"message" => $this->flag . " - " . ($this->orm ? $this->orm->id : "(sem id)")
		));
	});
	*/
	protected function button($type, $flags, $label = null, $icon = null, $callback = null, $additional_data = null)
	{
		if ($type == "print")
		{
			if (is_null($label))
			{
				$label = "Imprimir";
			}

			if (is_null($icon))
			{
				$icon = "icon-print";
			}
		}
		else if ($type == "export")
		{
			if (is_null($label))
			{
				$label = "Exportar";
			}

			if (is_null($icon))
			{
				$icon = "icon-share";
			}
		}
		else if ($type == "redirect" || $type == "request")
		{
			if (is_null($icon))
			{
				$icon = "icon-arrow-right";
			}
		}

		$info = array(
			"type" => $type,
			"flags" => $flags,
			"label" => $label,
			"icon" => $icon,
			"callback" => $callback,
			"additional_data" => $additional_data
		);

		if ($type == "export" || $type == "request")
		{
			$uri = "manager/api/button" . uniqueID();
			$info["url"] = URL::root(false) . $uri;

			$that = $this;
			Router::register("GET", $uri, function () use ($callback, $that) {
				if (($token = User::validateToken()) !== true)
				{
					return $token;
				}

				$id = (int)Request::get("id", 0);
				$that->flag = Str::upper(Request::get("flag", "L"));

				if ($that->flag == "RU")
				{
					$that->flag == "R";
				}

				if ($id > 0)
				{
					$that->orm = ORM::make($that->tableName)->where("id", $id)->findFirst();
				}

				return call_user_func($callback);
			});
		}

		$this->buttons[] = $info;
	}

	public function listORM()
	{
		return $orm = ORM::make($this->tableName)
						->select("id");
	}

	public function listCountORM()
	{
		return ORM::make($this->tableName);
	}

	public function save()
	{

	}

	public function afterSave()
	{

	}
}
?>