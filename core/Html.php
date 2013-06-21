<?php
class Html
{
	public static function headers()
	{
		$root = URL::root();

		echo "<meta http-equiv='Content-Type' content='text/html; charset=UTF-8' />
		<link rel=\"stylesheet\" href=\"" . $root . "allCSS/\">
		<script type='text/javascript' src='" . $root . "allJS/'></script>
		<script type='text/javascript'>window.J_ROOT = '" . $root . "';</script>
		\n";
	}
}
?>