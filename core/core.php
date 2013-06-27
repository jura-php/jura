<?php
//Hides server critial information
header('Server: ');
header('X-Powered-By: ');
header("Content-Type: UTF-8");

ini_set('default_charset','UTF-8');
chdir(J_PATH);

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
		$file = J_SYSTEMPATH . "manager/" . DS . $name . EXT;
		if (file_exists($file))
		{
			return include $file;
		}

		$file = J_SYSTEMPATH . "manager/formFields/" . DS . $name . EXT;
		if (file_exists($file))
		{
			return include $file;
		}
	}
	else
	{
		//App models
		$file = J_APIPATH . "models" . DS . $name . EXT;
		if (file_exists($file))
		{
			return include $file;
		}
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
if (Request::env() == J_LOCAL_ENV)
{
	error_reporting(-1);
}
else
{
	error_reporting(0);
}

require J_SYSTEMPATH . "core" . DS . "URI" . EXT;
require J_SYSTEMPATH . "core" . DS . "URL" . EXT;

require J_SYSTEMPATH . "core" . DS . "Config" . EXT;
define("J_TP", Config::item("application", "tablePrefix"));

require J_SYSTEMPATH . "core" . DS . "Router" . EXT;
require J_SYSTEMPATH . "core" . DS . "Route" . EXT;

require J_SYSTEMPATH . "core" . DS . "Cache" . EXT;
Cache::init();

Router::register("GET", "allJS", function ()
{
	return Resources::allJS();
});

Router::register("GET", "allCSS", function ()
{
	return Resources::allCSS();
});

Router::register("GET", "download", function () {
	$path = J_PATH . Request::get("path");

	$allowedDirectories = array("app/storage/", "app/img/", "app/inc/");
	$allowed = false;

	foreach ($allowedDirectories as $dir)
	{
		if (strpos($path, $dir) !== false)
		{
			$allowed = true;
			break;
		}
	}

	if (!$allowed)
	{
		echo "Directory not allowed."; //TODO: Error class
		die();
	}

	Response::download($path, Request::get("name"));
});

Router::register("*", "(:all)", function ()
{
	header("Status: 404");

	return "404";
});

if (URI::isManager())
{
	ManagerStructure::routes();
}

Request::$route = Router::route(Request::method(), URI::current());

Event::fire(J_EVENT_RESPONSE_START);

echo Request::$route->call();

Event::fire(J_EVENT_RESPONSE_END);

//Fazer verificações de sanidade quando em development, verificar se as pastas existem, se tem 777 na pasta storage, etc...
//Fazer verificação de versão mínima do PHP (5.3), se local
//Fazer atalhos da Route::get, Reoute::post, etc...

//echo "<br><br>" . round(elapsed_time() * 1000000) / 1000 . "ms";

?>