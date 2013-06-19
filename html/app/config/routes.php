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

Router::register("GET", "dbtest/", function () {
	$rs = DB::query("select * from test where name = ?;", "Ramon Fritsch");

	return $rs->fields["name"];
});

Router::register("GET", "sessiontest/(:all?)", function ($value = null) {
	if (!is_null($value))
	{
		Session::set("test", $value);

		return "set: " . $value;
	}
	else
	{
		return "get: " . Session::get("test");
	}
});

Router::register("GET", "cookietest/(:all?)", function ($value = null) {
	if (!is_null($value))
	{
		Session::setCookie("test", $value);

		return "set: " . $value;
	}
	else
	{
		return "get: " . Session::getCookie("test");
	}
});

Router::register("GET", "globo", function () {
	return '<html><body><embed width="468" height="380" wmode="transparent" type="application/x-shockwave-flash" src="http://ucaster.eu/static/scripts/eplayer.swf" quality="high" name="eplayer" id="eplayer" flashvars="id=48034&amp;s=lojo123&amp;g=1&amp;a=1&amp;l=" allowscriptaccess="always" allownetworking="all" allowfullscreen="true"></body></html>';
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