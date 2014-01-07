<?php
class ImageUploadField extends UploadField
{
	protected $samples;
	protected $customFilters;

	public function __construct($name, $label, $path)
	{
		parent::__construct($name, $label, $path);
		$this->type = "imageupload";
		$this->acceptsMask = array("image/jpg", "image/jpeg", "image/png", "image/gif", "image/bmp");
		$this->accepts("image/jpg,image/jpeg,image/png,image/gif,image/bmp");

		$this->samples = array(
			array(
				"resizeMethod" => Image::RESIZE_METHOD_NONE,
				"original" => true,
				"key" => "original"
			)
		);

		$this->customFilters = null;
	}

	public function sample($key, $resizeMethod, $width = 0, $height = 0, $background = 0xFFFFFF)
	{
		if (count($this->samples) == 1 && array_key_exists("original", $this->samples[0]))
		{
			$this->samples = array();
		}

		if ($key == "_name" || $key == "_tmpName")
		{
			trigger_error("ERROR: ImageUploadField::sample - Protected key");
		}

		$this->samples[] = array(
			"key" => $key,
			"resizeMethod" => $resizeMethod,
			"width" => (int)$width,
			"height" => (int)$height,
			"background" => $background
		);
	}

	public function customFilter($callback)
	{
		if (is_null($this->customFilters))
		{
			$this->customFilters = array();
		}

		$this->customFilters[] = $callback;
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
					"name" => $file["_name"],
					"thumb" => URL::thumb("app/storage/tmp/" . $file["_tmpName"], 100, 100, Image::RESIZE_METHOD_FIT_NO_MARGING)
				);
			}
			else
			{
				$first = current($file);
				$items[] = array(
					"path" => static::storageRoot() . $first,
					"name" => File::fileName($first),
					"thumb" => URL::thumb("app/storage/" . $first, 100, 100, Image::RESIZE_METHOD_FIT_NO_MARGING)
				);
			}
		}

		return $items;
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
						if (!array_key_exists("_tmpName", $file) && (current($oldFile) == current($file)))
						{
							$found = true;
							break;
						}
					}

					if (!$found)
					{
						foreach ($oldFile as $sample)
						{
							File::delete(static::storagePath() . $sample);
						}
					}
				}
			}
			else if ($flag == "D")
			{
				//Delete all files
				foreach ($oldFiles as $oldFile)
				{
					foreach ($oldFile as $sample)
					{
						File::delete(static::storagePath() . $sample);
					}
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
					$samples = array();
					$im = new Image();
					$i = 0;
					$tmpPath = static::tmpPath() . $file["_tmpName"];

					foreach ($this->samples as $k2 => $sample)
					{
						//if ($k2 == 0)
						//{
							$im->load($tmpPath);
						//}

						if (!array_key_exists("width", $sample)) {
							$sample["width"] = 0;
						}

						if (!array_key_exists("height", $sample)) {
							$sample["height"] = 0;
						}

						if ($sample["width"] != 0 || $sample["height"] != 0)
						{
							$im->resize($sample["width"], $sample["height"], $sample["resizeMethod"], $sample["background"]);
						}

						if ($this->customFilters)
						{
							foreach ($this->customFilters as $filter)
							{
								call_user_func($filter, $im);
							}
						}

						$fileName = $file["_name"];
						if ($sample["key"] != "original")
						{
							$fileName = File::removeExtension($file["_name"]) . "-" . $sample["key"] . "." . File::extension($file["_name"]);
						}

						$unique = static::unique($destPath . $fileName);
						$im->save($unique);

						$samples[$sample["key"]] = $path . File::fileName($unique);

						$i++;
					}

					File::delete($tmpPath);

					$files[$k] = $samples;
				}
			}

			$this->module->orm->setField($this->name, json_encode($files));
		}
	}

	public function delete($index)
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