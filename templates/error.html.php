<div style="width: 60%; margin: 30px auto">

    <h1>Something went wrong</h1>

    <p>
        <?= e($message) ?>
    </p>

    <h2>Stacktrace</h2>

    <?php foreach ($trace as $i => $frame) : ?>
        <code>
            #<?= $i ?> <?= e(frame_to_call_point($frame)) ?>
        </code>
        <br>
    <?php endforeach; ?>

    <br>

    <h2>Context</h2>

    <p>
        Query string: <?= e(urldecode($_SERVER['QUERY_STRING'] ?? '')) ?>
    </p>
    <p>
        Version: <?= raw(Configuration::getVersion()) ?>
    </p>
    <p>
        OS: <?= raw(PHP_OS_FAMILY) ?>
    </p>
    <p>
        PHP version: <?= raw(PHP_VERSION ?: 'Unknown') ?>
    </p>
</div>

