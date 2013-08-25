<?php
//TODO: Create path, if don't exists

class UploadField extends Field
{
	public $limit;
	public $path;
	public $fileName;

	private $resourceURL;
	private $sessionKey;

	private static function storagePath()
	{
		return J_APPPATH . "storage" . DS;
	}

	private static function storageRoot()
	{
		return URL::root() . "storage/";
	}

	private static function tmpPath()
	{
		$path = J_APPPATH . "storage" . DS . "tmp" . DS;
		File::mkdir($path);

		return $path;
	}

	private static function tmpRoot()
	{
		return URL::root() . "storage/tmp/";
	}

	private static function tmpFile()
	{
		$file = substr("00000" . rand(1, 999999), -6) . "_" . time();

		if (File::exists(static::tmpPath() . $file))
		{
			return static::tmpFile();
		}

		return $file;
	}

	public function __construct($name, $label = null, $path, $fileName)
	{
		parent::__construct($name, $label);
		$this->type = "upload";

		$id = uniqueID();
		$this->sessionKey = "manager_" . $this->type . $id;
		$this->resourceURL = "fields/" . $this->type . $id;
		$this->limit = 1;
		$this->path = $path;
		$this->fileName = $fileName;

		Router::register('POST', "manager/api/" . $this->resourceURL . "/(:num)/(:segment)", function ($id, $flag) {
			$flag = Str::upper($flag);
			$this->module->flag = $flag;

			if ($flag == "U")
			{
				$this->orm = ORM::make($this->module->tableName)
							->findFirst($id);
			}

			$return = array();

			if (isset($_FILES["attachment"]))
			{
				$info = $_FILES["attachment"];
				$count = count($this->items());
				$num = count($info["name"]);

				for ($i = 0; $i < $num; $i++)
				{
					$ext = File::extension($info["name"][$i]);

					$dest = "";
					$tmpFile = "";
					if ($flag == "C")
					{
						$tmpFile = static::tmpFile() . "." . $ext;
						$dest = static::tmpPath() . $tmpFile;
					}
					else
					{
						$dest = static::storagePath() . $this->path() . $this->unmask($this->fileName, $count + $i) . "." . $ext;
					}

					if (move_uploaded_file($info["tmp_name"][$i], $dest))
					{
						if ($flag == "C")
						{
							$list = Session::get($this->sessionKey);

							if (!$list)
							{
								$list = array();
							}

							$list[] = $tmpFile;

							Session::set($this->sessionKey, $list);
						}

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
				}

				//TODO: Check allowed extension
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
			Session::clear($this->sessionKey);

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
		return $this->limit == 0 ? 150 : $this->limit;
	}

	private function items()
	{
		$items = array();

		if ($this->module->flag == "C")
		{
			$list = Session::get($this->sessionKey);

			if ($list)
			{
				foreach ($list as $file)
				{
					$items[] = array("path" => static::tmpRoot() . $file);
				}
			}
		}
		else
		{
			$path = $this->path();

			$files = File::lsdir(static::storagePath() . $path);
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
					$items[] = array("path" => static::storageRoot() . $path . $found, "fsPath" => static::storagePath() . $path . $found);
				}
			}

			//TODO: Reorder files if necessary
		}

		return $items;
	}

	public function afterSave($flag)
	{
		if ($flag == "C")
		{
			$list = Session::get($this->sessionKey);

			if ($list)
			{
				File::mkdir(static::storagePath() . $this->path());

				$i = 0;
				foreach ($list as $file)
				{
					$ext = File::extension($file);
					$dest = static::storagePath() . $this->path() . $this->unmask($this->fileName, $i) . "." . $ext;

					File::move(static::tmpPath() . $file, $dest);

					$i++;
				}
			}

			Session::clear($this->sessionKey);
		}
		else if ($flag == "D")
		{
			$list = $this->items();

			foreach ($list as $info)
			{
				File::delete($info["fsPath"]);
			}

			$files = File::lsdir(static::storagePath() . $this->path());
			if (count($files) == 0)
			{
				File::rmdir($files);
			}
		}
	}


}
?>