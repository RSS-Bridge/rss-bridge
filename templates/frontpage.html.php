<script src="static/rss-bridge.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', rssbridge_toggle_bridge);
    document.addEventListener('DOMContentLoaded', rssbridge_list_search);
</script>

<?php if (Debug::isEnabled()): ?>
    <?php if (!Debug::isSecure()): ?>
        <section class="critical-warning">
            Warning : Debug mode is active from any location,
            make sure only you can access RSS-Bridge.
        </section>
    <?php else: ?>
        <section class="warning">
            Warning : Debug mode is active from your IP address,
            your requests will bypass the cache.
        </section>
    <?php endif; ?>
<?php endif; ?>

<section class="searchbar">
    <h3>Search</h3>
    <input
        type="text"
        name="searchfield"
        id="searchfield"
        placeholder="Insert URL or bridge name"
        onchange="rssbridge_list_search()"
        onkeyup="rssbridge_list_search()"
        value=""
    >
</section>

<?= raw($bridges) ?>

<section class="footer">
    <a href="https://github.com/rss-bridge/rss-bridge">RSS-Bridge ~ Public Domain</a><br>
    <p class="version"><?= e(Configuration::getVersion()) ?></p>

    <?= $active_bridges ?>/<?= $total_bridges ?> active bridges.<br>

    <?php if ($active_bridges !== $total_bridges): ?>
        <?php if ($show_inactive): ?>
            <a href="?show_inactive=0">
                <button class="small">Hide inactive bridges</button>
            </a>
            <br>
        <?php else: ?>
            <a href="?show_inactive=1">
                <button class="small">Show inactive bridges</button>
            </a>
            <br>
        <?php endif; ?>
    <?php endif; ?>

    <br>

    <?php if ($admin_email): ?>
        <div>
            Email: <a href="mailto:<?= e($admin_email) ?>"><?= e($admin_email) ?></a>
        </div>
    <?php endif; ?>

    <?php if ($admin_telegram): ?>
        <div>
            Telegram: <a href="<?= e($admin_telegram) ?>"><?= e($admin_telegram) ?></a>
        </div>
    <?php endif; ?>

</section>
