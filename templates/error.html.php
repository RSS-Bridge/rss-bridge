<div style="width: 60%; margin: 30px auto">

    <h1>
        <?= e($title ?? 'Something went wrong') ?>
    </h1>

    <br>
    <?= e($message) ?>
    <br>
    <br>

    <?php if (isset($stacktrace)): ?>
        <h2>Stacktrace</h2>
        <br>

        <?php foreach ($stacktrace as $frame) : ?>
            <code>
                <?= e($frame) ?>
            </code>
            <br>
        <?php endforeach; ?>
    <?php endif; ?>

</div>

