<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="<?= $charset ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/ >
    <meta name="description" content="RSS-Bridge" />
    <title><?= e($title) ?></title>
    <link href="static/style.css?2023-03-24" rel="stylesheet">
    <link rel="icon" type="image/png" href="static/favicon.png">

	<?php foreach ($linkTags as $link): ?>
        <link
            href="<?= $link['href'] ?>"
            title="<?= $link['title'] ?>"
            rel="alternate"
            type="<?= $link['type'] ?>"
        >
	<?php endforeach; ?>

    <meta name="robots" content="noindex, follow">
</head>

<body>

    <div class="container">

        <h1 class="pagetitle">
            <a href="<?= e($uri) ?>" target="_blank"><?= e($title) ?></a>
        </h1>

        <div class="buttons">
            <a href="./#bridge-<?= $_GET['bridge'] ?>">
                <button class="backbutton">← back to rss-bridge</button>
            </a>

            <?php foreach ($buttons as $button): ?>
                <a href="<?= $button['href'] ?>">
                    <button class="rss-feed"><?= $button['value'] ?></button>
                </a>
            <?php endforeach; ?>
        </div>

        <?php foreach ($items as $item): ?>
            <section class="feeditem">
                <h2>
                    <a
                        class="itemtitle"
                        href="<?= e($item['url']) ?>"
                    ><?= strip_tags($item['title']) ?></a>
                </h2>

                <?php if ($item['timestamp']): ?>
                    <time datetime="<?= date('Y-m-d H:i:s', $item['timestamp']) ?>">
                        <?= date('Y-m-d H:i:s', $item['timestamp']) ?>
                    </time>
                <?php endif; ?>

                <?php if ($item['author']): ?>
                    <br/>
                    <p class="author">by: <?= e($item['author']) ?></p>
                <?php endif; ?>

                <div class="content">
                    <?= sanitize_html($item['content']) ?>
                </div>

                <?php if ($item['enclosures']): ?>
                    <div class="attachments">
                        <p>Attachments:</p>
                        <?php foreach ($item['enclosures'] as $enclosure): ?>
                            <li class="enclosure">
                                <a href="<?= e($enclosure) ?>" rel="noopener noreferrer nofollow">
                                    <?= e(substr($enclosure, strrpos($enclosure, '/') + 1)) ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if ($item['categories']): ?>
                    <div class="categories">
                        <p>Categories:</p>
                        <?php foreach ($item['categories'] as $category): ?>
                            <li class="category"><?= e($category) ?></li>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        <?php endforeach; ?>

    </div>
 </body>
</html>
