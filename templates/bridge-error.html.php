<section>
    <p class="exception-message">
        <?= e($message ?? '') ?>
    </p>

    <?php if (isset($stacktrace)): ?>
        <?php foreach ($stacktrace as $frame): ?>
            <code>
                <?= e($frame) ?>
            </code>
            <br>
        <?php endforeach; ?>
    <?php endif; ?>

    <br>

    <p>
        Query string: <?= e($_SERVER['QUERY_STRING'] ?? '') ?>
    </p>
    <p>
        Version: <?= raw(Configuration::getVersion()) ?>
    </p>
    <p>
        OS: <?= raw(PHP_OS_FAMILY) ?>
    </p>
    <p>
        PHP version: <?= raw(phpversion() ?: 'Unknown'()) ?>
    </p>

    <div class="advice">
        <ul class="advice">
            <li>Press Return to check your input parameters</li>
            <li>Press F5 to retry</li>
            <li>Check if this issue was already reported on <a href="<?= raw($searchUrl) ?>">GitHub</a> (give it a thumbs-up)</li>
            <li>Open a <a href="<?= raw($issueUrl) ?>">GitHub Issue</a> if this error persists</li>
        </ul>
    </div>

    <a href="<?= raw($searchUrl) ?>" title="Opens GitHub to search for similar issues">
        <button>Search GitHub Issues</button>
    </a>

    <a href="<?= raw($issueUrl) ?>" title="After clicking this button you can review the issue before submitting it">
        <button>Open GitHub Issue</button>
    </a>

    <p class="maintainer"><?= e($bridge->getMaintainer()) ?></p>
</section>

