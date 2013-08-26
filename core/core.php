<?php
//Hides server critial information
header('Server: ');
header('X-Powered-By: ');
header("Content-Type: text/html;UTF-8");

ini_set('default_charset','UTF-8');
chdir(J_PATH);

$globalUniqueID = 1;
function uniqueID()
{
	global $globalUniqueID;

	return $globalUniqueID++;
}

function j_autoload($name)
{
	//System core
	$file = J_SYSTEMPATH . "core" . DS . $name . EXT;
	if (file_exists($file))
	{
		return include $file;
	}

	//System database
	$file = J_SYSTEMPATH . "database" . DS . $name . EXT;
	if (file_exists($file))
	{
		return include $file;
	}

	//System library
	$file = J_SYSTEMPATH . "library" . DS . $name . EXT;
	if (file_exists($file))
	{
		return include $file;
	}

	if (URI::isManager())
	{
		$file = J_SYSTEMPATH . "manager/classes/" . DS . $name . EXT;
		if (file_exists($file))
		{
			return include $file;
		}

		$file = J_SYSTEMPATH . "manager/classes/formFields/" . DS . $name . EXT;
		if (file_exists($file))
		{
			return include $file;
		}

		$file = J_MANAGERPATH . "formFields/" . DS . $name . EXT;
		if (file_exists($file))
		{
			return include $file;
		}
	}

	//App models
	$file = J_APPPATH . "models" . DS . $name . EXT;
	if (file_exists($file))
	{
		return include $file;
	}
}
spl_autoload_register("j_autoload");

require J_SYSTEMPATH . "core" . DS . "helpers" . EXT;
require J_SYSTEMPATH . "core" . DS . "Str" . EXT;
require J_SYSTEMPATH . "core" . DS . "Event" . EXT;

function j_shutdown()
{
	Event::fire(J_EVENT_SHUTDOWN);
}
register_shutdown_function("j_shutdown");

require J_SYSTEMPATH . "core" . DS . "Request" . EXT;
Request::init();

//TODO: Place it on a Error class.. Create error handlers..
if (Request::isLocal() || Request::isPreview())
{
	error_reporting(E_ALL);
	ini_set('display_errors','1');
}
else
{
	error_reporting(0);
	ini_set("error_log", J_APPPATH . "storage" . DS . "errors.log");
}

require J_SYSTEMPATH . "core" . DS . "URI" . EXT;
require J_SYSTEMPATH . "core" . DS . "URL" . EXT;

require J_SYSTEMPATH . "core" . DS . "Config" . EXT;

require J_SYSTEMPATH . "database" . DS . "DB" . EXT;
DB::init();

require J_SYSTEMPATH . "core" . DS . "Router" . EXT;
require J_SYSTEMPATH . "core" . DS . "Route" . EXT;

require J_SYSTEMPATH . "core" . DS . "Cache" . EXT;
Cache::init();

Router::register("GET", "download/(:all)", function () {
	$pieces = explode("/", Request::pathInfo());
	array_shift($pieces); //(empty space)
	array_shift($pieces); //download

	$path = implode(DS, $pieces);

	$allowedPaths = Config::item("application", "downloadPaths");
	$allowed = false;

	foreach ($allowedPaths as $dir)
	{
		if (Str::startsWith($path, File::formatDir($dir)))
		{
			$allowed = true;
			break;
		}
	}

	if (!$allowed)
	{
		Response::code(403);

		return;
	}

	Response::download(J_PATH . DS . $path, Request::get("name"));
});

Router::register("GET", "thumb/", function () {
	$pieces = explode("/", trim(Request::get("path"), "/"));

	$path = implode(DS, $pieces);

	$allowedPaths = array("storage", "public/img", "app/assets/img");
	$allowed = false;

	foreach ($allowedPaths as $dir)
	{
		if (Str::startsWith($path, File::formatDir($dir)))
		{
			$allowed = true;
			break;
		}
	}

	if (!$allowed || count($pieces) == 0)
	{
		return Response::code(403);
	}

	$path = implode(DS, $pieces);

	if (!File::exists(J_PATH . DS . $path) || is_dir(J_PATH . DS . $path))
	{
		return Response::code(404);
	}

	$im = new Image(J_PATH . DS . $path);
	$im->resize((int)Request::get("width"), (int)Request::get("height"), Request::get("method", "fit"), Request::get("background", 0xFFFFFF));
	$im->header();
});

Router::register("*", "(:all)", function () {
	Response::code(404);

	if (Request::isLocal())
	{
		echo "URI: " . URI::full() . "<br>\n";
		echo "Path Info: " . Request::pathInfo() . "\n";
	}

	return;
});

if (URI::isManager())
{
	Structure::routes();
}

Request::$route = Router::route(Request::method(), URI::current());

Event::fire(J_EVENT_RESPONSE_START);

echo Request::$route->call();

Event::fire(J_EVENT_RESPONSE_END);

//echo "<br><br>" . round(elapsed_time() * 1000000) / 1000 . "ms";

?>