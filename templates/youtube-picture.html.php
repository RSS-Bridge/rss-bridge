<a href="<?= $videoUri ?>">
    <picture>
        <source
            srcset="<?= $webpSrcset ?>"
            type="image/webp"
            referrerpolicy="no-referrer"
        />
        <img
            srcset="<?= $jpegSrcset ?>"
            src="<?= $fallbackUri ?>"
            alt="Video thumbnail"
            title="YouTube video thumbnail"
            referrerpolicy="no-referrer"
        />
    </picture>
</a>
<p>
    <a href="<?= $videoUri ?>"><?= $videoUri ?></a>
</p>
