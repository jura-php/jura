<?php

define("DS", DIRECTORY_SEPARATOR);
define("EXT", ".php");
define("CRLF", PHP_EOL);

define('J_START', microtime(true));
define("J_PATH", realpath(__DIR__) . DS);
define("J_APPPATH", realpath(__DIR__)  . DS . "app" . DS);
define("J_SYSTEMPATH", realpath(__DIR__)  . DS . "system" . DS);
define("J_LOCAL_ENV", "local");

require J_SYSTEMPATH . "core" . DS . "core" . EXT;
?>