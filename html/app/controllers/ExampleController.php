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

	function cryptTest($param = "example")
	{
		echo "Encoded: " . Crypt::encode($param) . "<br>";
		echo "decoded: " . Crypt::decode(Crypt::encode($param)) . "<br>";
	}
}
?>