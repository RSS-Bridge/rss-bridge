<!DOCTYPE html>
<html>
<head>
	<meta charset="<?= $charset ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<title><?= $title ?></title>
	<link href="static/HtmlFormat.css" rel="stylesheet">
	<link rel="icon" type="image/png" href="static/favicon.png">
	<?= $links ?>

	<meta name="robots" content="noindex, follow">
</head>
<body>
	<h1 class="pagetitle"><a href="<?= $uri ?>" target="_blank"><?= $title ?></a></h1>
	<div class="buttons">
		<a href="./#bridge-<?= $bridge ?>"><button class="backbutton">← back to rss-bridge</button></a>
		<?= $buttons ?>

	</div>
<?= $entries ?>

</body>
</html>