<section>
    <p class="exception-message">
        <?= e($message) ?>
    </p>

    <?php foreach ($trace as $i => $frame) : ?>
        #<?= $i ?> <?= frame_to_call_point($frame) ?>
        <br>
    <?php endforeach; ?>

    <br>

    <p>
        Query string: <?= e(urldecode($_SERVER['QUERY_STRING']) ?? '') ?>
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

    <a href="<?= raw($searchUrl) ?>" title="Opens GitHub to search for similar issues">
        <button>Find similar bugs</button>
    </a>

    <a href="<?= raw($issueUrl) ?>" title="After clicking this button you can review the issue before submitting it">
        <button>Create GitHub Issue</button>
    </a>

    <p class="maintainer"><?= e($maintainer) ?></p>
</section>

