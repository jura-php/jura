<?php

Router::register("GET", "/", "index"); //view

Router::register("GET", "test", function () {
	header("Content-Type: UTF-8; text/plain");
	return "Testing cached content...";
}, true);

Router::register("GET", "example", "example@hello");

Router::register("GET", "cryptTest/(:all?)", "example@cryptTest");

Router::register("GET", "see/(:all)", "example@see"); //controller

Router::register("GET", "home", "home"); //view

Router::register("GET", "here/(:all?)", function ($param = "bacon") {
	return "here is the param: " . $param;
});


/*Event::listen(J_EVENT_RESPONSE_START, function () {
	echo "event: Start example..<br>";
});

Event::listen(J_EVENT_RESPONSE_END, function () {
	echo "<br>event: End example..<br>";
});

Event::listen(J_EVENT_SHUTDOWN, function () {
	echo "<br>event: Shutdown example..<br>";
});*/

?>