<div class="error">

    <?php if ($e instanceof HttpException): ?>
        <?php if ($e instanceof CloudFlareException): ?>
            <h2>The website is protected by CloudFlare</h2>
            <p>
                RSS-Bridge tried to fetch a website.
                The fetching was blocked by CloudFlare.
                CloudFlare is anti-bot software.
                Its purpose is to block non-humans.
            </p>
        <?php endif; ?>

        <?php if ($e->getCode() === 404): ?>
            <h2>The website was not found</h2>
            <p>
                RSS-Bridge tried to fetch a page on a website.
                But it doesn't exists.
            </p>
        <?php endif; ?>

        <?php if ($e->getCode() === 429): ?>
            <h2>Try again later</h2>
            <p>
                RSS-Bridge tried to fetch a website.
                They told us to try again later.
            </p>
        <?php endif; ?>

    <?php else: ?>
        <?php if ($e->getCode() === 10): ?>
            <h2>The rss feed is completely empty</h2>
            <p>
                RSS-Bridge tried parse the empty string as xml.
                The fetched url is not pointing to real xml.
            </p>
        <?php endif; ?>

        <?php if ($e->getCode() === 11): ?>
            <h2>There is something wrong with the rss feed</h2>
            <p>
                RSS-Bridge tried parse xml. It failed. The xml is probably broken.
            </p>
        <?php endif; ?>
    <?php endif; ?>

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

