<?php

Router::register("GET", "test", function () {
	return "called test!";
});

Router::register("GET", "example", "example@hello");
Router::register("GET", "cryptTest/(:all?)", "example@cryptTest");
Router::register("GET", "see/(:all)", "example@see");

Router::register("GET", "here/(:all?)", function ($param = "bacon") {
	return "here is the param: " . $param;
});

?>