<?php
class Module
{
	protected $type = "";
	protected $title = "";
	protected $icon = "icon-group";

	public function __construct()
	{

	}

	public function config($config)
	{
		$config["type"] = $this->type;
		$config["title"] = $this->title;
		$config["icon"] = $this->icon;

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