<?php

ini_set('default_charset','UTF-8');
chdir(J_PATH);

require J_SYSTEMPATH . "core" . DS . "helpers" . EXT;
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

require J_SYSTEMPATH . "core" . DS . "Str" . EXT;
require J_SYSTEMPATH . "core" . DS . "URI" . EXT;
require J_SYSTEMPATH . "core" . DS . "Config" . EXT;
require J_SYSTEMPATH . "core" . DS . "Router" . EXT;
require J_SYSTEMPATH . "core" . DS . "Route" . EXT;
require J_SYSTEMPATH . "core" . DS . "Crypt" . EXT;

Router::register("*", "(:all)", function ()
{
	return "404";
});

Request::$route = Router::route(Request::method(), URI::current());

$response = Request::$route->call();

echo "response: " . $response . "<br>";


//Fazer verificações de sanidade quando em development, verificar se as pastas existem, se tem 777 na pasta storage, etc...
//Fazer verificação de versão mínima do PHP (5.3), se local
//Fazer roteamento de views
//Fazer atalhos da Route::get, Reoute::post, etc...
//Fazer um install.sh que cria as pastas e arquivos de exemplo no app/ dando 777 no storage, etc...

echo "<br><br>" . round(elapsed_time() * 1000000) / 1000 . "ms";

?>