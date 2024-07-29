<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="<?= $charset ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/ >
    <meta name="description" content="RSS-Bridge" />
    <title><?= e($title) ?></title>
    <link href="static/style.css?2023-03-24" rel="stylesheet">
    <link rel="icon" type="image/png" href="static/favicon.png">

    <?php foreach ($formats as $format): ?>

        <link
            href="<?= e($format['url']) ?>"
            title="<?= e($format['name']) ?>"
            rel="alternate"
            type="<?= e($format['type']) ?>"
        >
	<?php endforeach; ?>

    <meta name="robots" content="noindex, follow">
</head>

<body>

    <div class="container">

        <h1 class="pagetitle">
            <a href="<?= e($uri) ?>" target="_blank"><?= e($title) ?></a>
        </h1>

        <?php if (UrlEncryptionService::enabled() && $encryption_token !== 'yes'): ?>
            <div style="text-align:center;">
                <details>
                    <summary style="list-style:none;cursor:pointer;">
                        &#x1f512;&nbsp;<small style="color:gray;font-style:italic;">Encrypt This Feed</small>
                    </summary>
                    <div style="padding:20px;">
                        <p>Obscure the parameters used to create this feed. This is essential if your
                            feed includes private data like API tokens, passphrases, etc.</p>
                        <p><small>
                            If you specifically want an encrypted link to another format, click the button below and choose
                                a format from the generated page.
                        </small></p>
                        <p>
                            <!--
                                TODO: Hitting this button over and over can result in non-cached
                                hits to the feed's target website.
                            -->
                            <a target="_blank" href="<?php print(
                                strtok($_SERVER['REQUEST_URI'], '?')
                                . '?' . UrlEncryptionService::PARAMETER_NAME . '='
                                . urlencode(UrlEncryptionService::generateFromQueryString($_SERVER['QUERY_STRING']))
                            ); ?>">
                                <button class="rss-feed">
                                    Encrypt URL
                                </button>
                            </a>
                        </p>
                    </div>
                </details>
            </div>
        <?php else: ?>
            <div style="text-align:center;">
                <p><span style="color:green;">&#x2713;</span>&nbsp;<small style="color:gray;font-style:italic;">Encrypted</small></p>
            </div>
        <?php endif; ?>

        <div class="buttons">
            <a href="<?php if($bridge_name) { print('./#bridge-' . $bridge_name); } else { print('/'); } ?>">
                <button class="backbutton">← back to rss-bridge</button>
            </a>
            <?php foreach ($formats as $format): ?>
                <?php if (!UrlEncryptionService::enabled() || $encryption_token !== 'yes'): ?>
                    <a href="<?= e($format['url']) ?>">
                <?php else: ?>
                        <a target="_blank" href="<?php print(
                            strtok($_SERVER['REQUEST_URI'], '?')
                            . '?' . UrlEncryptionService::PARAMETER_NAME . '='
                            . urlencode(UrlEncryptionService::generateFromQueryString($format['url']))
                        ); ?>">
                <?php endif; ?>
                        <button class="rss-feed">
                            <?= e($format['name']) ?>
                        </button>
                    </a>
            <?php endforeach; ?>
            <?php if ($donation_uri): ?>
                <a href="<?= e($donation_uri) ?>">
                    <button class="rss-feed">
                        Donate to maintainer
                    </button>
                </a>
            <?php endif; ?>
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
                    <p></p>
                <?php endif; ?>

                <?php if ($item['author']): ?>
                    <p class="author">by: <?= e($item['author']) ?></p>
                <?php endif; ?>

                <!-- Intentionally not escaping for html context -->
                <?= break_annoying_html_tags($item['content']) ?>

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
