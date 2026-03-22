<?php

class Rule34Bridge extends GelbooruBridge
{
    const MAINTAINER = 'LordArrin';
    const NAME = 'Rule34';
    const URI = 'https://api.rule34.xxx/';          // API endpoint
    const VIEW_URI = 'https://rule34.xxx/';         // Main site
    const DESCRIPTION = 'Returns images from given page';

    protected function getItemFromElement($element)
    {
        $pageUrl = self::VIEW_URI . 'index.php?page=post&s=view&id=' . $element->id;
        $fullImageUrl = $element->file_url ?? '';
        $thumbnailUrl = $element->preview_url ?? $this->buildThumbnailURI($element);

        // Сlickable thumbnail
        $content = sprintf(
            '<a href="%s"><img src="%s" /></a>',
            htmlspecialchars($fullImageUrl),
            htmlspecialchars($thumbnailUrl)
        );
        $content .= '<br><br>';

        $content .= '<b>Dimensions:</b> ' . $element->width . ' x ' . $element->height . '<br>';

        $content .= '<br>';
        $content .= '<b>Tags:</b> ' . htmlspecialchars($element->tags);

        if (isset($element->source) && !empty($element->source)) {
            $content .= '<br><br><b>Source: </b><a href="' . htmlspecialchars($element->source) . '">'
                      . htmlspecialchars($element->source) . '</a>';
        }

        return [
            'uri'       => $pageUrl,
            'id'        => $pageUrl,
            'title'     => $this->getName() . ' | ' . $element->id,
            'content'   => $content,
            'author'    => $element->owner ?? 'unknown',
            'timestamp' => (int) ($element->change ?? time()), // Unix timestamp
        ];
    }
}
