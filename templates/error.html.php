<?php
/**
 * This template is for rendering error messages (not exceptions)
 */
?>

<?php if (isset($title)): ?>
    <h1>
        <?= e($title) ?>
    </h1>
<?php endif; ?>

<p>
    <?= e($message) ?>
</p>
