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
    <div class="container">
        <header>
            <a href="./">
                <img width="400" src="static/logo_600px.png">
            </a>
        </header>

        <?php if ($system_message): ?>
            <div class="alert">
                <?= raw($system_message) ?>
            </div>
        <?php endif; ?>

        <?= raw($page) ?>
    </div>
</body>
</html>

