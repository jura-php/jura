<?php
//TODO: Fazer proteção se o campo na tabela estiver com um json inválido
//TODO: Create path, if don't exists

class UploadField extends Field
{
	public $limit;
	public $path;

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

	private static function tmpKey()
	{
		return time() . substr("00000" . rand(1, 999999), -6);
	}

	private static function unique($path)
	{
		if (File::exists($path))
		{
			$ext = File::extension($path);
			$file = File::removeExtension(File::fileName($path));
			$path = File::dirName($path);

			list($name, $num) = explode("-", $file);

			if (!is_null($num) && (int)$num == $num)
			{
				$file = $name . "-" . ((int)$num + 1) . "." . $ext;
			}
			else
			{
				$file = $file . "-1." . $ext;
			}

			return static::unique($path . $file);
		}

		return $path;
	}

	public function __construct($name, $label = null, $path)
	{
		parent::__construct($name, $label);
		$this->type = "upload";

		$id = uniqueID();
		$this->sessionKey = "manager_" . $this->type . $id;
		$this->resourceURL = "fields/" . $this->type . $id;
		$this->limit = 1;
		$this->path = $path;
		$this->defaultValue = array();

		Router::register("POST", "manager/api/" . $this->resourceURL . "/(:segment)/(:num)/(:segment)", function ($action, $id, $flag) {
			$flag = Str::upper($flag);
			$this->module->flag = $flag;

			$return = array();

			switch ($action) {
				default:
				case "update":
					if (isset($_FILES["attachment"]))
					{
						$info = $_FILES["attachment"];
						$num = count($info["name"]);

						//TODO: Check allowed extension

						for ($i = 0; $i < $num; $i++)
						{
							$ext = File::extension($info["name"][$i]);

							$fileName = File::removeExtension($info["name"][$i]);
							$destFile = $fileName . static::tmpKey() . "." . $ext;

							if (move_uploaded_file($info["tmp_name"][$i], static::tmpPath() . $destFile))
							{
								//Session::set($this->sessionKey, json_encode(array())); //TEST

								$files = json_decode(Session::get($this->sessionKey), true);

								$files[] = array(
									"name" => $fileName . "." . $ext,
									"tmpName" => $destFile
								);

								Session::set($this->sessionKey, json_encode($files));

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
					}
					else
					{
						$return = array(
							"error" => "O arquivo é maior que o limite de " . ini_get('upload_max_filesize') . " do servidor"
						);
					}

					break;
				case "delete":
					$index = (int)Request::post("index", -1);

					if ($index >= 0)
					{
						$files = json_decode(Session::get($this->sessionKey), true);

						if ($index < count($files))
						{
							$file = $files[$index];

							if (array_key_exists("tmpName", $file))
							{
								File::delete(static::tmpPath() . $file["tmpName"]);
							}

							array_splice($files, $index, 1);

							Session::set($this->sessionKey, json_encode($files));

							$return = array(
								"error" => false,
								"items" => $this->items()
							);
						}
						else
						{
							$return = array(
								"error" => "Index inválido"
							);
						}
					}
					else
					{
						$return = array(
							"error" => "Index inválido"
						);
					}

					break;
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

	public function init($flag)
	{
		if ($flag == "C" && !$this->module->orm)
		{
			Session::set($this->sessionKey, json_encode(array()));
		}
	}

	public function value($flag)
	{
		$value = $this->module->orm->field($this->name);

		if (!is_array(@json_decode($value, true)))
		{
			$value = json_encode(array());
		}

		Session::set($this->sessionKey, $value);

		return $this->items();
	}

	private function items()
	{
		$files = json_decode(Session::get($this->sessionKey), true);
		$items = array();

		foreach ($files as $file)
		{
			if (array_key_exists("tmpName", $file))
			{
				$items[] = array(
					"path" => static::tmpRoot() . $file["tmpName"],
					"name" => $file["name"]
				);
			}
			else
			{
				$items[] = array(
					"path" => static::storageRoot() . $file["path"],
					"name" => File::fileName($file["path"])
				);
			}
		}

		return $items;
	}

	public function save($value, $flag)
	{
		$path = File::formatDir($this->path);
		$destPath = static::storagePath() . File::formatDir($this->path);
		File::mkdir($destPath);

		$files = json_decode(Session::get($this->sessionKey), true);

		if ($flag == "U" || $flag == "D")
		{
			$oldFiles = $this->module->orm->field($this->name);

			if (!is_array(@json_decode($oldFiles, true)))
			{
				$oldFiles = json_encode(array());
			}

			$oldFiles = json_decode($oldFiles, true);

			if ($flag == "U")
			{
				//Delete files that are on our table but not on our session list
				foreach ($oldFiles as $oldFile)
				{
					$found = false;

					foreach ($files as $file)
					{
						if (!array_key_exists("tmpName", $file))
						{
							if ($oldFile["path"] == $file["path"])
							{
								$found = true;
								break;
							}
						}
					}

					if (!$found)
					{
						File::delete(static::storagePath() . $oldFile["path"]);
					}
				}
			}
			else if ($flag == "D")
			{
				//Delete all files
				foreach ($oldFiles as $oldFile)
				{
					File::delete(static::storagePath() . $oldFile["path"]);
				}
			}
		}

		if ($flag == "C" || $flag == "U")
		{
			//Copy tmp files to it's target place and save
			foreach ($files as $k => $file)
			{
				if (array_key_exists("tmpName", $file))
				{
					$unique = static::unique(static::storagePath() . $path . $file["name"]);
					File::move(static::tmpPath() . $file["tmpName"], $unique);

					$files[$k] = array(
						"path" => $path . File::fileName($unique)
					);
				}
			}

			$this->module->orm->setField($this->name, json_encode($files));
		}
	}
}
?>