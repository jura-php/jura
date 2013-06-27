<?php
class ManagerFormModule extends ManagerModule
{
	protected static $actionsMap = array(
		"L" => "list",
		"O" => "order",
		"F" => "filter",
		"C" => "create",
		"R" => "read",
		"U" => "update",
		"D" => "delete"
	);

	protected $tableName;
	protected $flags;

	private $fields;

	public function __construct()
	{
		$this->type = "form";
		$this->flags = "LOFCRUD";

		$this->tableName = "";
		$this->fields = array();
	}

	public function config($config)
	{
		$config = parent::config($config);

		$config["uri"] = str_replace("_", "", $this->tableName);

		$actions = array();

		foreach (static::$actionsMap as $k => $v)
		{
			if (Str::contains($this->flags, $k))
			{
				$actions[] = $v;
			}
		}

		$config["actions"] = $actions;

		//TODO: Fields

		return $config;
	}

	public function routes()
	{
		Router::register("GET", "manager/api/" . $this->tableName, function () {

		});

		//TODO: Create CRUD routes....
	}

	protected function addField($field, $flags = "LOFCRUD")
	{
		//TODO:
	}
}
?>