<?php
class UploadField extends Field
{
	public function __construct($name, $label = null)
	{
		parent::__construct($name, $label);
		$this->type = "upload";

		Router::register('POST', 'manager/api/upload_file', function () {
			return Response::json([
				['path' => Url::root() . '../app/storage/images/dl_adventure_bpack_15in_331-5363.png'],
				['path' => Url::root() . '../app/storage/images/dl_adventure_bpack_15in_331-5363.png']
			]);
		});

	}

	public function value($orm, $flag)
	{
		return [
			['path' => Url::root() . '../app/storage/images/dl_adventure_bpack_15in_331-5363.png']
		];
	}

	public function includeOnSQL()
	{
		return false;
	}


}
?>