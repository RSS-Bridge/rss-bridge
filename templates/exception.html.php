<?php
/**
 * This template is used for rendering exceptions
 */
?>
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

        <?php if ($e->getCode() === 400): ?>
            <h2>400 Bad Request</h2>
            <p>
                This is usually caused by an incorrectly constructed http request.
            </p>
        <?php endif; ?>

        <?php if ($e->getCode() === 403): ?>
            <h2>403 Forbidden</h2>
            <p>
                The HTTP 403 Forbidden response status code indicates that the
                server understands the request but refuses to authorize it.
            </p>
        <?php endif; ?>

        <?php if ($e->getCode() === 404): ?>
            <h2>404 Page Not Found</h2>
            <p>
                RSS-Bridge tried to fetch a page on a website.
                But it doesn't exists.
            </p>
        <?php endif; ?>

        <?php if ($e->getCode() === 429): ?>
            <h2>429 Too Many Requests</h2>
            <p>
                RSS-Bridge tried to fetch a website.
                They told us to try again later.
            </p>
        <?php endif; ?>

        <?php if ($e->getCode() === 503): ?>
            <h2>503 Service Unavailable</h2>
            <p>
                Common causes are a server that is down for maintenance
                or that is overloaded.
            </p>
        <?php endif; ?>

        <?php if ($e->getCode() === 0): ?>
            <p>
                See
                <a href="https://curl.haxx.se/libcurl/c/libcurl-errors.html">
                    https://curl.haxx.se/libcurl/c/libcurl-errors.html
                </a>
                for description of the curl error code.
            </p>
        <?php else: ?>
            <p>
                <a href="https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/<?= raw($e->getCode()) ?>">
                    https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/<?= raw($e->getCode()) ?>
                </a>
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
        <div class="error-type">
            <strong>Type:</strong> <?= e(get_class($e)) ?>
        </div>

        <div>
            <strong>Code:</strong> <?= e($e->getCode()) ?>
        </div>

        <div class="error-message">
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

