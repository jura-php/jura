<?php
class UploadField extends Field
{
	public function __construct($name, $label = null)
	{
		parent::__construct($name, $label);

		$this->type = "upload";

	}


}
?>