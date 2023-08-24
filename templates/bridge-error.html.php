
<?= raw($error) ?>

<a href="<?= raw($searchUrl) ?>" title="Opens GitHub to search for similar issues">
    <button>Find similar bugs</button>
</a>

<a href="<?= raw($issueUrl) ?>" title="After clicking this button you can review the issue before submitting it">
    <button>Create GitHub Issue</button>
</a>

<p class="maintainer">
    <?= e($maintainer) ?>
</p>