<?php
class ExampleController
{
	function __construct()
	{

	}

	function hello()
	{
		return "Example response";
	}

	function see($what)
	{
		return "You're seen " . $what;
	}
}
?>