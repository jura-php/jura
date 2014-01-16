<?php

$systemRoot = rtrim(realpath(dirname(__FILE__)), "/") . DIRECTORY_SEPARATOR;

chdir($systemRoot);

include $systemRoot . "/core/helpers.php";

if (!isset($_SERVER["argv"]))
{
	header("", true, 500);
	echo "ERROR: Script must be executed from commandline.\n";
	die();
}

function make_dir($node, $path = null, $root = null)
{
	if (is_null($root))
	{
		$root = ".." . DIRECTORY_SEPARATOR;
	}

	$root = trim($root, "/") . DIRECTORY_SEPARATOR;
	$keep = false;
	$ignore = false;

	if ($node == "#keep#")
	{
		$keep = true;
		$node = null;
	}
	else if ($node == "#ignore#")
	{
		$ignore = true;
		$node = null;
	}

	if (!is_null($node) && !is_array($node))
	{
		$path = $node;
	}

	if (!is_null($path))
	{
		$root = $root . $path . DIRECTORY_SEPARATOR;
		if (!file_exists($root))
		{
			echo "> dir " . $root . "\n";
			mkdir($root);
		}
	}

	if ($keep)
	{
		$keep = $root . ".gitkeep";

		if (!file_exists($keep))
		{
			echo "> file " . $keep . "\n";
			file_put_contents($keep, "");
		}
	}
	else if ($ignore)
	{
		$ignore = $root . ".gitignore";

		if (!file_exists($ignore))
		{
			echo "> file " . $ignore . "\n";
			file_put_contents($ignore, "*");
		}
	}

	if (is_array($node))
	{
		foreach ($node as $k => $v)
		{
			make_dir($v, $k, $root);
		}
	}
}

function make_file($paths, $content = "", $callback = null, $append = false)
{
	$paths = (array)$paths;

	foreach ($paths as $path)
	{
		if (!file_exists($path))
		{
			echo "> file " . $path . "\n";
			file_put_contents($path, value($content));

			if (is_callable($callback))
			{
				$callback = call_user_func($callback);
			}
		}
		else if ($append)
		{
			$content = value($content);
			$file = file_get_contents($path);
			if (strpos($file, $content) === false)
			{
				echo "> file " . $path . "\n";
				file_put_contents($path, "\n" . $content, FILE_APPEND);
			}
		}
	}
}

$folders = array(
	"app" => array(
		"assets" => "#keep#",
		"config",
		"controllers" => "#keep#",
		"models" => "#keep#",
		"storage" => array(
			"cache" => "#ignore#",
			"tmp" => "#ignore#",
			"logs" => "#ignore#"
		),
		"views" => "#keep#"
	),
	"config" => array(
		"preview",
		"production"
	),
	"manager" => array(
		"config",
		"modules" => "#keep#"
	),
	"public"
);

make_dir($folders);

make_file("../deploy.sh", "chmod -Rf 777 app/storage/", function () {
	echo "> exec sh delpoy.sh\n";

	$out = shell_exec("cd ..; chmod +x deploy.sh; sh deploy.sh");
	if ($out)
	{
		echo $out . "\n";
	}
});

make_file("../index.php", '<?php

define("DS", DIRECTORY_SEPARATOR);
define("EXT", ".php");
define("CRLF", PHP_EOL);

$dir = realpath(__DIR__);

define("J_START", microtime(true));
define("J_PATH", $dir . DS);
define("J_APPPATH", $dir . DS . "app" . DS);
define("J_MANAGERPATH", $dir . DS . "manager" . DS);
define("J_SYSTEMPATH", $dir . DS . "system" . DS);
define("J_LOCAL_ENV", "local");
define("J_PREVIEW_ENV", "preview");

require J_SYSTEMPATH . "core" . DS . "core" . EXT;

?>');

make_file("../app/config/routes.php");

make_file("../app/config/application.php", function () {
	$dict = array("f", "4", "G", "a", "D", "8", "P", "K", "Z", "u", "Y", "x", "c", "M", "y", "w", "r", "7", "5", "0", "S", "g", "F", "Q", "o", "R", "E", "h", "m", "t", "C", "s", "z", "9", "e", "V");
	$dictLength = count($dict);

	$key = "";
	for ($i = 0; $i < 32; $i++)
	{
		$key .= $dict[rand(0, $dictLength - 1)];
	}

	return '<?php
return array(
	//random alpha-numeric 32 characters for cookie encriptation
	"key" => "' . $key . '",

	//use or not the build version
	"usedist" => false,
	"publicDist" => "public/_dist/",

	//allowed directories that can have files downloaded from
	"downloadPaths" => array(
		"app/storage/"
	),

	//allowed directories that can have images resized automatically
	"thumbPaths" => array(
		"app/storage/",
		"app/assets/img/",
		"public/img/"
	)
);
?>';
});

make_file("../config/errors.php", '<?php
return array(
	//Output errors
	"show" => true,

	//Log errors into file (app/storage/logs/YYYY-mm-dd.log)
	"log" => true,

	//Ignore errors specified by its code
	// Ex. array(8191, 8192)
	"ignore" => array()
);
?>');

make_file("../config/production/errors.php", '<?php
return array(
	//Output errors
	"show" => false,

	//Log errors into file (app/storage/logs/YYYY-mm-dd.log)
	"log" => true,

	//Ignore errors specified by its code
	// Ex. array(8191, 8192)
	"ignore" => array()
);
?>');

make_file("../.gitignore", "node_modules/
public/_dist/
config/databases.php");

make_file(array("../config/databases.sample.php", "../config/databases.php", "../config/production/databases.php"), '<?php
return array(
	"mysql" => array(
		"type" => "mysql",
		"host" => "localhost",
		"user" => "root",
		"pass" => "",
		"database" => "#sample#",
		"tablePrefix" => "s_"
	)
);
?>');

make_file("../config/preview/databases.php", '<?php
return array(
	"mysql" => array(
		"type" => "mysql",
		"host" => "localhost",
		"user" => "root",
		"pass" => "brocolis11",
		"database" => "#sample#",
		"tablePrefix" => "s_"
	)
);
?>');

make_file("../config/environments.php", '<?php
return array(
	J_LOCAL_ENV => array("localhost", "127.0.0.1", "macbook.local", "imac.local"),
	J_PREVIEW_ENV => array("preview.joy-interactive.com"),
	"production" => "*"
);
?>');

make_file("../manager/config/modules.php", '<?php
return array(
	array("class" => "UsersForm")
);
?>');

make_file("../manager/modules/UsersForm.php", '<?php
class UsersForm extends FormModule
{

	public function __construct()
	{
		parent::__construct();

		$this->tableName = "manager_users";
		$this->title = "Usuários";
		$this->icon = "icon-group";
	}

	public function fields()
	{
		$f = new TextField("name", "Nome");
		$this->addField($f, "LOFCRU");

		$f = new TextField("email", "E-mail");
		$f->validation("email");
		$this->addField($f, "LOFCRU");

		$f = new TextField("username", "Usuário");
		$this->addField($f, "LOFCRU");

		$f = new PasswordField("password", "Senha");
		$this->addField($f, "CRU");

		$f = new ToggleField();
		$this->addField($f, "LOFCRU");
	}
}
?>');

make_file("../.htaccess", '<IfModule mod_rewrite.c>
	Options -MultiViews
	IndexIgnore *

	RewriteEngine On

	#Manager index.html
	RewriteCond %{REQUEST_FILENAME} !-f
	RewriteRule ^manager/$ system/manager/index.html [L,QSA]

	#Manager files
	RewriteCond %{REQUEST_FILENAME} !-f
	RewriteCond %{REQUEST_URI} !manager/api(.+)$
	RewriteRule ^manager(.+)$ system/manager$1 [L,QSA]

	#App assets
	RewriteCond %{REQUEST_FILENAME} !-f
	RewriteRule ^assets(.+)$ app/assets$1 [L,QSA]

	#App public
	RewriteCond %{REQUEST_FILENAME} !-f
	RewriteRule ^(.*)\.(html|css|js|jpg|png|gif|ttf|eot|svg|woff|pdf) public/$1.$2 [QSA]

	#App public index.html
	RewriteRule ^$ public/index.html [QSA]

	#Routes
	RewriteCond %{REQUEST_FILENAME} !-f
	RewriteRule ^(.+)$ index.php/$1 [L,QSA]

	#IE htc files
	AddType text/x-component .htc
</IfModule>', null, true);

?>