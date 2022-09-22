<div style="width: 60%; margin: 30px auto">

    <h1>Something went wrong</h1>

    <br>

    Exception

    <a href="https://www.php.net/manual/en/class.<?= strtolower($class) ?>.php">
        <?= $class ?>
    </a>

    <b>
        <?= e($message) ?>
    </b>

    in

    <a href="<?= render_github_url($file, $line) ?>">
        <?= $file ?>(<?= $line ?>)
    </a>

    <br>
    <br>
    <h2>Stacktrace</h2>
    <br>

    <?php foreach ($trace as $i => $frame) : ?>
        #<?= $i ?>

        <a href="<?= render_github_url($frame['file'], $frame['line']) ?>">
            <?= frame_to_call_point($frame) ?>
        </a>

        <br>
    <?php endforeach; ?>

    <br>

    <h2>Context</h2>
    <br>

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

