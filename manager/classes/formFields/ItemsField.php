<?php
class ItemsField extends Field
{
	public function __construct($name, $label = null)
	{
		parent::__construct($name, $label);

		$this->type = 'items';
		$this->multiple = false;
		$this->items = [];
		$this->resource_url = 'resource_items/' . $name;

		Router::register('GET', 'manager/api/' . $this->resource_url, function () {
			return Response::json($this->items);
		});
	}

	public function addItemsFromArray($arr)
	{
		$this->items = $arr;
	}

	public function config()
	{
		$arr = parent::config();

		return array_merge([
			'multiple' => $this->multiple,
			'resource_url' => $this->resource_url
		], $arr);
	}

	public function multiple($table_relationship)
	{
		$this->multiple = true;
	}
}
?>