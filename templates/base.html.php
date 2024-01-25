<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="RSS-Bridge" />
    <title><?= e($_title ?? 'RSS-Bridge') ?></title>
    <link href="static/style.css?2023-03-24" rel="stylesheet">
    <link rel="icon" type="image/png" href="static/favicon.png">

    <script src="static/rss-bridge.js"></script>
</head>

<body>
    <div class="container">
        <header>
            <a href="./">
                <img width="400" src="static/logo_600px.png">
            </a>
        </header>

        <?php foreach ($messages as $message): ?>
            <div class="alert-<?= raw($message['level'] ?? 'info') ?>">
                <?= raw($message['body']) ?>
            </div>
        <?php endforeach; ?>

        <?= raw($page) ?>
    </div>
</body>
</html>

