<!DOCTYPE html>
<html>
	<head>
		<?php Html::headers(); ?>
		<meta name="viewport" content="width=device-width; initial-scale=1.0; maximum-scale=1.0; minimum-scale:1.0; user-scalable=no">
		<title>App Exemplo</title>
	</head>
	<body>
		<div class="container">
			<h1>Tweets</h1>
			<div class="list">
				TODO
			</div>
			<div class="form">
				<form action="<?php echo URL::to("addtweet/"); ?>" method="POST">
					<input type="text" name="text" />
					<input type="submit" label="Enviar" />
				</form>
			</div>
		</div>
	</body>
</html>