<?php

class Image
{
	const BLANK = "blank";

	const DATA = "data";

	const RESIZE_METHOD_NONE = "none";
	const RESIZE_METHOD_FIT = "fit";
	const RESIZE_METHOD_FIT_NO_MARGING = "fitNoMarging";
	const RESIZE_METHOD_FILL = "fill";
	const RESIZE_METHOD_FIXED_WIDTH = "fixedWidth";
	const RESIZE_METHOD_FIXED_HEIGHT = "fixedHeight";

	public $width;
	public $height;

	public $resourceID = null;
	private $sourceResourceID = null;
	private $path = "";

	public static function headerData($path, $data)
	{
		header("Content-Type: image/" . static::mimetype(File::extension($path)));
		header('Content-Transfer-Encoding: binary');
		header('Last-Modified: '.gmdate('D, d M Y H:i:s', time()).' GMT');
		header('Content-Length: ' . strlen($data));
		
		echo $data;
	}
	
	private static function mimetype($extension)
	{
		if ($extension == "jpg")
		{
			return "image/jpeg";
		}

		return "image/" . $extension;
	}

	function __construct($path = "", $width = 100, $height = 100)
	{
		if ($path != "")
		{
			if ($path == Image::BLANK)
			{
				$this->loadBlankImage($width, $height);
			}
			else
			{
				$this->load($path);
			}
		}
	}
	
	public function loadBlankImage($width, $height)
	{
		$this->destroy();
		
		$this->resourceID = imagecreatetruecolor($width, $height);
		
		if ($this->resourceID)
		{
			$this->width = imagesx($this->resourceID);
			$this->height = imagesy($this->resourceID);

			return true;
		}

		return false;
	}
	
	public function load($path, $extension = null, $content = null)
	{
		global $F;
	
		$this->destroy();
		$this->path = $path;
	
		if (is_null($extension))
		{
			$extension = File::extension($path);
		}
	
		switch ($extension)
		{
			default:
			case "jpg":
				$this->resourceID = @imagecreatefromjpeg($path);
				
				break;
			case "gif":
				$this->resourceID = @imagecreatefromgif($path);
				
				break;
			case "png":
				$this->resourceID = @imagecreatefrompng($path);
				imagesavealpha($this->resourceID, true);
				
				break;
			case Image::DATA:
				$this->resourceID = @imagecreatefromstring($content);

				break;
		}
		
		if ($this->resourceID)
		{
			$this->width = imagesx($this->resourceID);
			$this->height = imagesy($this->resourceID);
			
			return true;
		}
		else
		{
			return false;
		}
	}
	
	function resize($width, $height, $method = "fit", $backgroundColor = 0xFFFFFF)
	{
		if ($method == Image::RESIZE_METHOD_NONE)
		{
			return;
		}
	
		if (!$this->sourceResourceID)
		{
			$this->sourceResourceID = $this->resourceID;
			imagesavealpha($this->sourceResourceID, true);
		}
		
		$width = (int)$width;
		$height = (int)$height;

		if ($width == 0 && $height == 0)
		{
			$width = $this->width;
			$height = $this->height;
		}

		$newRect = array(0, 0, 0, 0);
		$relW = $this->width / $width;
		$relH = $this->height / $height;
		
		switch ($method)
		{
			default:
			case Image::RESIZE_METHOD_FIT:
				if ($relW > $relH)
				{	
					$newRect[2] = $width;
					$newRect[3] = (int)($this->height / $relW);
					$newRect[1] = (int)(($height - $newRect[3]) / 2);
				}
				else
				{
					$newRect[2] = (int)($this->width / $relH);
					$newRect[3] = $height;
					$newRect[0] = (int)(($width - $newRect[2]) / 2);
				}
				
				break;
			case Image::RESIZE_METHOD_FILL:
				if ($relW > $relH)
				{
					$newRect[2] = (int)($this->width / $relH);
					$newRect[3] = $height;
					$newRect[0] = (int)(($width - $newRect[2]) / 2);
				}
				else
				{
					$newRect[2] = $width;
					$newRect[3] = (int)($this->height / $relW);
					$newRect[1] = (int)(($height - $newRect[3]) / 2);
				}
			
				break;
			case Image::RESIZE_METHOD_FIT_NO_MARGING:
				if ($relW > $relH)
				{
					$newRect[2] = $width;
					$newRect[3] = (int)($this->height / $relW);
				}
				else
				{
					$newRect[2] = (int)($this->width / $relH);
					$newRect[3] = $height;
				}
				
				$width = $newRect[2];
				$height = $newRect[3];
				
				break;
			case Image::RESIZE_METHOD_FIXED_WIDTH:
				$newRect[2] = $width;
				$newRect[3] = (int)($this->height / $relW);
			
				$width = $newRect[2];
				$height = $newRect[3];
			
				break;
			case Image::RESIZE_METHOD_FIXED_HEIGHT:
				$newRect[2] = (int)($this->width / $relH);
				$newRect[3] = $height;
				
				$width = $newRect[2];
				$height = $newRect[3];
				
				break;
		}
		
		if (!($newRect[2] > $this->width || $newRect[3] > $this->height) && !($newRect[2] == $this->width && $newRect[3] == $this->height))
		{
			$this->resourceID = imagecreatetruecolor($width, $height);

			if (!(($this->path != "") && (File::extension($this->path) == "png")))
			{
				$color = imagecolorallocate($this->resourceID, ($backgroundColor >> 16) & 0xFF, ($backgroundColor >> 8) & 0xFF, $backgroundColor & 0xFF);
				imagefilledrectangle($this->resourceID, 0, 0, $width, $height, $color);
			}
			else
			{
				imagesavealpha($this->resourceID, true);
				imagefill($this->resourceID, 0, 0, imagecolorallocatealpha($this->resourceID, 0, 0, 0, 127));
			}
			
			imagecopyresampled($this->resourceID, $this->sourceResourceID, $newRect[0], $newRect[1], 0, 0, $newRect[2], $newRect[3], $this->width, $this->height);

			$this->width = $width;
			$this->height = $height;
		}
	}
	
	public function header($type = null, $quality = 90)
	{
		$extension = "";
		if (is_null($type))
		{
			if ($this->path != "")
			{
				$vals = @getimagesize($this->path);
				$types = array(1 => "gif", 2 => "jpg", 3 => "png");
				$mime = (isset($types[$vals["2"]])) ? static::mimetype($types[$vals["2"]]) : "image/jpeg";
				
				$extension = File::extension($this->path);
			}
			else
			{
				$extension = "jpg";
			}
		}
		else
		{
			$extension = $type;
			$mime = static::mimetype($type);
		}
		
		header("Content-Type: " . $mime);
		header('Content-Transfer-Encoding: binary');
		header('Last-Modified: '.gmdate('D, d M Y H:i:s', time()).' GMT');
		
		$data = "";
		
		ob_start();
		switch ($extension)
		{
			case "png":
				imagepng($this->resourceID);
			
				break;
			case "gif";
				imagegif($this->resourceID);
				
				break;
			case "jpg":
			default:
				imagejpeg($this->resourceID, null, $quality);
				
				break;
		}
		
		$data = ob_get_contents();
		
		ob_clean();
		
		//Save to cache
		// if ($this->path != "" && $saveCache)
		// {
		// 	$key = str_replace("/", "_", substr($this->path, strlen(RF_APPPATH))) . "_" . $this->width . "_" . $this->height . "_" . $this->resizeMethod . "_" . $this->backgroundColor;
			
		// 	global $C;
		// 	$C->save($key, $data);
		// }
		
		echo $data;
	}
	
	public function save($path, $quality = 90)
	{
		switch (File::extension($path))
		{
			case "png":
				return imagepng($this->resourceID, $path);
			
				break;
			case "gif";
				return imagegif($this->resourceID, $path);
				
				break;
			case "jpg":
			default:
				return imagejpeg($this->resourceID, $path, $quality);
				
				break;
		}
	}
	
	public function destroy()
	{
		if (!is_null($this->resourceID))
		{
			@imagedestroy($this->resourceID);
			$this->resourceID = null;
			$this->path = "";
		}
		
		if (!is_null($this->sourceResourceID))
		{
			@imagedestroy($this->sourceResourceID);
			$this->sourceResourceID = null;
		}
	}
}

// function imageDataFromCache($path, $width, $height, $resizeMethod = "inside", $backgroundColor = 0xFFFFFF)
// {
// 	$key = str_replace("/", "_", substr($path, strlen(RF_APPPATH))) . "_" . $width . "_" . $height . "_" . $resizeMethod . "_" . $backgroundColor;
	
// 	global $C;
// 	return $C->get($key);
// }

?>