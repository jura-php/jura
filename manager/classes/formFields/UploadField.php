<?php
//TODO: Fazer verificação de extensões válidas no upload

class UploadField extends Field
{
	public $limit;
	public $path;

	protected $sessionKey;
	protected $accepts;
	protected $acceptsMask;

	private $resourceURL;

	protected static function storagePath()
	{
		return J_APPPATH . "storage" . DS;
	}

	protected static function storageRoot()
	{
		return URL::root(false) . "storage/";
	}

	protected static function tmpPath()
	{
		$path = J_APPPATH . "storage" . DS . "tmp" . DS;
		File::mkdir($path);

		return $path;
	}

	protected static function tmpRoot()
	{
		return URL::root(false) . "storage/tmp/";
	}

	protected static function tmpKey()
	{
		return time() . substr("00000" . rand(1, 999999), -6);
	}

	protected static function unique($path)
	{
		if (File::exists($path))
		{
			$ext = File::extension($path);
			$file = File::removeExtension(File::fileName($path));
			$path = File::dirName($path);

			if (preg_match ('/(.*)-(\d+)$/', $file, $matches))
			{
				$num = (int)$matches[2] + 1;
				$file = $matches[1];
			}
			else
			{
				$num = 1;
			}

			$file = $file . "-" . $num . "." . $ext;

			return static::unique($path . $file);
		}

		return $path;
	}

	public function __construct($name, $label, $path)
	{
		parent::__construct($name, $label);
		$this->type = "upload";

		$id = uniqueID();
		$this->sessionKey = "manager_" . $this->type . $id;
		$this->resourceURL = "fields/" . $this->type . $id;
		$this->limit = 1;
		$this->path = $path;
		$this->defaultValue = array();
		$this->accepts = array();
		$this->acceptsMask = null;

		$that = $this;

		Router::register("POST", "manager/api/" . $this->resourceURL . "/(:segment)/(:num)/(:segment)", function ($action, $id, $flag) use ($that) {
			if (($token = User::validateToken()) !== true)
			{
				return $token;
			}

			$flag = Str::upper($flag);
			$that->module->flag = $flag;

			switch ($action) {
				default:
				case "update":
					return Response::json($that->upload($flag, $id));

					break;
				case "delete":
					return Response::json($that->delete((int)Request::post("index", -1)));

					break;
			}

			return Response::code(500);
		});
	}

	public function accepts($mimetypes)
	{
		if (is_array($mimetypes))
		{
			$accepts = $mimetypes;
		}
		else
		{
			$accepts = explode(",", $mimetypes);
		}

		if (!is_null($this->acceptsMask))
		{
			$accepts = array_intersect($accepts, $this->acceptsMask);
		}

		$this->accepts = array_unique($accepts);
	}

	public function config()
	{
		$arr = parent::config();

		return array_merge(array(
			"limit" => $this->limit,
			"resource_url" => "api/" . $this->resourceURL,
			"accepts" => implode(",", $this->accepts)
		), $arr);
	}

	public function init()
	{
		if ($this->module->flag == "C" && !$this->module->orm)
		{
			Session::set($this->sessionKey, json_encode(array()));
		}
	}

	public function value()
	{
		$value = $this->module->orm->field($this->name);

		if (!is_array(@json_decode($value, true)))
		{
			$value = json_encode(array());
		}

		Session::set($this->sessionKey, $value);

		return $this->items();
	}

	protected function items()
	{
		$files = json_decode(Session::get($this->sessionKey), true);
		$items = array();

		foreach ($files as $file)
		{
			if (array_key_exists("_tmpName", $file))
			{
				$items[] = array(
					"path" => static::tmpRoot() . $file["_tmpName"],
					"name" => $file["_name"]
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

	public function save($value)
	{
		$flag = $this->module->flag;
		$path = File::formatDir($this->path);
		$destPath = static::storagePath() . File::formatDir($this->path);
		
		File::mkdir($destPath);

		if (!is_writable($destPath))
		{
			Response::code(500);
			return Response::json(array(
				"error" => true,
				"error_description" => "Directory '" . $destPath . "' is not writtable."
			));
		}

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
						if (!array_key_exists("_tmpName", $file) && ($oldFile["path"] == $file["path"]))
						{
							$found = true;
							break;
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

				if (count(File::lsdir($destPath)) == 0)
				{
					File::rmdir($destPath);
				}
			}
		}

		if ($flag == "C" || $flag == "U")
		{
			//Copy tmp files to it's target place and save
			foreach ($files as $k => $file)
			{
				if (array_key_exists("_tmpName", $file))
				{
					$unique = static::unique($destPath . $file["_name"]);
					File::move(static::tmpPath() . $file["_tmpName"], $unique);

					$files[$k] = array(
						"path" => $path . File::fileName($unique)
					);
				}
			}

			$this->module->orm->setField($this->name, json_encode($files));
		}
	}

	private function upload($flag, $id)
	{
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

				if (@move_uploaded_file($info["tmp_name"][$i], static::tmpPath() . $destFile))
				{
					$files = json_decode(Session::get($this->sessionKey), true);

					$files[] = array(
						"_name" => $fileName . "." . $ext,
						"_tmpName" => $destFile
					);

					Session::set($this->sessionKey, json_encode($files));

					return array(
						"error" => false,
						"items" => $this->items()
					);
				}
				else
				{
					return array(
						"error" => true,
						"error_description" => "Erro de permissão de arquivo. Contate o desenvolvedor."
					);
				}
			}
		}

		return array(
			"error" => true,
			"error_description" => "O arquivo é maior que o limite de " . ini_get('upload_max_filesize') . " do servidor"
		);
	}

	private function delete($index)
	{
		if ($index >= 0)
		{
			$files = json_decode(Session::get($this->sessionKey), true);

			if ($index < count($files))
			{
				$file = $files[$index];

				if (array_key_exists("_tmpName", $file))
				{
					File::delete(static::tmpPath() . $file["_tmpName"]);
				}

				array_splice($files, $index, 1);

				Session::set($this->sessionKey, json_encode($files));

				return array(
					"error" => false,
					"items" => $this->items()
				);
			}
			else
			{
				return array(
					"error" => true,
					"error_description" => "Index inválido"
				);
			}
		}
		return array(
			"error" => true,
			"error_description" => "Index inválido"
		);
	}
}
?>