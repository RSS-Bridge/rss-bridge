<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="RSS-Bridge" />
    <title><?= e($_title ?? 'RSS-Bridge') ?></title>
    <link href="static/style.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="static/favicon.png">
</head>
<body>
    <header>
        <div class="logo"></div>
    </header>

    <?= raw($page) ?>
</html>

