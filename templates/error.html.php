<div class="error">

    <h1>Application Error</h1>

    <p>The application could not run because of the following error:</p>

    <h2>Details</h2>

    <div style="margin-bottom: 15px">
        <div>
            <strong>Type:</strong> <?= e(get_class($e)) ?>
        </div>

        <div>
            <strong>Code:</strong> <?= e($e->getCode()) ?>
        </div>

        <div>
            <strong>Message:</strong> <?= e(sanitize_root($e->getMessage())) ?>
        </div>

        <div>
            <strong>File:</strong> <?= e(sanitize_root($e->getFile())) ?>
        </div>

        <div>
            <strong>Line:</strong> <?= e($e->getLine()) ?>
        </div>
    </div>

    <h2>Trace</h2>

    <?php foreach (trace_from_exception($e) as $i => $frame) : ?>
        <code>
            #<?= $i ?> <?= e(frame_to_call_point($frame)) ?>
        </code>
        <br>
    <?php endforeach; ?>

    <br>

    <h2>Context</h2>

    <div>
        <strong>Query:</strong> <?= e(urldecode($_SERVER['QUERY_STRING'] ?? '')) ?>
    </div>

    <div>
        <strong>Version:</strong> <?= raw(Configuration::getVersion()) ?>
    </div>

    <div>
        <strong>OS:</strong> <?= raw(PHP_OS_FAMILY) ?>
    </div>

    <div>
        <strong>PHP:</strong> <?= raw(PHP_VERSION ?: 'Unknown') ?>
    </div>

    <br>

    <a href="/">Go back</a>
</div>

