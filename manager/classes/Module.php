<?php
class Module
{
	protected $type = "";
	protected $title = "";
	protected $icon = "icon-group";
	protected $default = false;

	public function __construct()
	{

	}

	public function config($config)
	{
		$config["type"] = $this->type;
		$config["title"] = $this->title;
		$config["icon"] = $this->icon;
		$config["default"] = $this->default;

		if (!isset($config["menu"]))
		{
			$config["menu"] = "side";
		}

		return $config;
	}

	public function routes()
	{

	}
}
?>