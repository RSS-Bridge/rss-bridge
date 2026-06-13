<?php

class Rule34Bridge extends GelbooruBridge
{
    const MAINTAINER = 'LordArrin';
    const NAME = 'Rule34';
    const URI = 'https://api.rule34.xxx/';
    const VIEW_URI = 'https://rule34.xxx/';
    const DESCRIPTION = 'Returns images from rule34.xxx search';

    const PARAMETERS = [
        'global' => [
            'api_key' => [
                'name' => 'API Key',
                'type' => 'text',
                'required' => true,
                'title' => 'Your Rule34 API key - you must register to get it in settings'
            ],
            'user_id' => [
                'name' => 'User ID',
                'type' => 'number',
                'required' => true,
                'title' => 'Your Rule34 user ID - you must register to get it in settings'
            ],
            't' => [
                'name' => 'Query (Tags)',
                'type' => 'text',
                'required' => true,
                'title' => 'Tags separated by commas or spaces (e.g., "tag1, tag2" or "tag1 tag2")'
            ],
            'l' => [
                'name' => 'Posts limit',
                'type' => 'number',
                'required' => false,
                'title' => 'Maximum number of posts to fetch (API allows up to 100)',
                'defaultValue' => 10
            ],
            'hide_tags' => [
                'name' => 'Hide tags',
                'type' => 'checkbox',
                'required' => false,
                'title' => 'Check this box to completely hide the tags list from the post content'
            ]
        ],
        0 => []
    ];

    public function collectData()
    {
        $tags = $this->getInput('t') ?? '';
        if (!empty($tags)) {
            $tags = str_replace(',', ' ', $tags);
            $tags = preg_replace('/\s+/', ' ', $tags);
            $tags = trim($tags);
            $this->inputs['t']['value'] = $tags;
        }

        parent::collectData();
    }

    protected function getItemFromElement($element)
    {
        $pageUrl = self::VIEW_URI . 'index.php?page=post&s=view&id=' . $element->id;
        $thumbnailUrl = $element->preview_url ?? $this->buildThumbnailURI($element);

        $content = sprintf(
            '<a href="%s"><img src="%s" /></a>',
            htmlspecialchars($pageUrl),
            htmlspecialchars($thumbnailUrl)
        );
        $content .= '<br><br>';
        $content .= sprintf(
            '<b>Dimensions:</b> %s x %s',
            $element->width,
            $element->height
        );

        $hideTags = $this->getInput('hide_tags') ?? false;
        if (!$hideTags) {
            $content .= '<br><br>';
            $content .= sprintf('<b>Tags:</b> %s', htmlspecialchars($element->tags));
        }

        if (isset($element->source) && !empty($element->source)) {
            $content .= '<br><br>';
            $content .= sprintf(
                '<b>Source:</b> <a href="%s">%s</a>',
                htmlspecialchars($element->source),
                htmlspecialchars($element->source)
            );
        }

        $title = sprintf('Image %s', $element->id);

        return [
            'uri'       => $pageUrl,
            'id'        => $pageUrl,
            'title'     => $title,
            'content'   => $content,
            'author'    => $element->owner ?? 'unknown',
            'timestamp' => (int) ($element->change ?? time()),
        ];
    }
}