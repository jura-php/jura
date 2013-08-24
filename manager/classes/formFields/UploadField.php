<?php
class UploadField extends Field
{
	public $limit;
	public $path;
	public $fileName;

	private $resourceURL;

	private static function storagePath()
	{
		return J_APPPATH . "storage" . DS;
	}

	private static function storageRoot()
	{
		return URL::root() . "storage/";
	}

	public function __construct($name, $label = null, $path, $fileName)
	{
		parent::__construct($name, $label);
		$this->type = "upload";
		$this->resourceURL = "fields/" . $this->type . uniqueID();
		$this->limit = 1;
		$this->path = $path;
		$this->fileName = $fileName;

		Router::register('POST', "manager/api/" . $this->resourceURL . "/(:num)", function ($id) {
			$this->orm = ORM::make($this->module->tableName)
							->findFirst($id);

			//TODO: Detect flag

			$return = array();

			if (isset($_FILES["attachment"]))
			{
				$info = $_FILES["attachment"];
				$ext = File::extension($info["name"][0]); //TODO: Fazer isso ficar múltiplo..

				//TODO: Check allowed extension
				//TODO: Create C version

				//RU version

				$items = $this->items();
				$dest = static::storagePath() . $this->path() . $this->unmask($this->fileName, count($items)) . "." . $ext;

				if (move_uploaded_file($info["tmp_name"][0], $dest))
				{


					$return = array(
						"error" => false,
						"items" => $this->items()
					);
				}
				else
				{
					$return = array(
						"error" => "Erro de permissão de arquivo. Contate o desenvolvedor."
					);
				}

				// $ext = $F->extension($_FILES['Filedata']["name"]);
				// 	$fileDest = $this->_tmpFile();
					
				// 	$ok = false;
				// 	if (is_array($this->allowedExtensions) && sizeof($this->allowedExtensions) > 0)
				// 	{
				// 		if (array_search($ext, $this->allowedExtensions) !== false) 
				// 		{
				// 			$ok = true;
				// 		}
				// 	}
				// 	else
				// 	{
				// 		$ok = true;
				// 	}
					
				// 	if ($ok)
				// 	{
				// 		if (move_uploaded_file($_FILES['Filedata']["tmp_name"], $fileDest))
				// 		{
				// 			$list = $this->copyFilesToLocation($fileDest, $ext);
				// 			echo "OK||" . implode(",", $list);
				// 		} else {
				// 			echo "ERROR||Erro de permissão de arquivo. Contate o desenvolvedor.";
				// 		}
				// 	} else {					
				// 		echo "ERROR||Por favor, apenas arquivos com extensões " . implode(", ", $this->allowedExtensions);
				// 	}

				
			}
			else
			{
				$return = array(
					"error" => "O arquivo é maior que o limite de " . ini_get('upload_max_filesize') . " do servidor"
				);
			}

			return Response::json($return);
		});
	}

	public function config()
	{
		$arr = parent::config();

		return array_merge([
			'limit' => $this->limit,
			'resource_url' => "api/" . $this->resourceURL
		], $arr);
	}

	public function value($flag)
	{
		if ($flag == "C")
		{
			//TODO: Clear tmp session

			return array();
		}
		else
		{
			return $this->items();
		}
	}

	public function includeOnSQL()
	{
		return false;
	}

	private function unmask($value, $index = 0)
	{
		$value = str_replace("#N#", ($index + 1), $value);

		foreach ($this->orm->fieldNames() as $name)
		{
			$value = str_replace("#" . strtoupper($name) . "#", $this->orm->field($name), $value);
		}

		return $value;
	}

	private function path()
	{
		return File::formatDir($this->unmask($this->path));
	}

	private function limit()
	{
		return $this->limit == 0 ? 100 : $this->limit;
	}

	private function items()
	{
		//TODO: Check C version..
		//$this->module->flag

		//RU version
		$path = $this->path();

		$files = File::lsdir(static::storagePath() . $path);
		$items = array();
		$limit = $this->limit();

		for ($i = 0; $i < $limit; $i++)
		{
			$found = false;
			$name = $this->unmask($this->fileName, $i);

			foreach ($files as $file)
			{
				if (File::removeExtension($file) == $name)
				{
					$found = $file;
					break;
				}
			}

			if ($found)
			{
				$items[] = array("path" => static::storageRoot() . $found);
			}
		}

		return $items;
	}


}
?>