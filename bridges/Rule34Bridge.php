<?php

declare(strict_types=1);

class Rule34Bridge extends GelbooruBridge
{
    const NAME = 'Rule34';
    const URI = 'https://api.rule34.xxx/';
    const DESCRIPTION = 'Returns images from rule34.xxx search';
    const MAINTAINER = 'LordArrin';
    const CACHE_TIMEOUT = 1800;
    const VIEW_URI = 'https://rule34.xxx/';

    const CONFIGURATION = [
        'api_key' => [
            'required' => false,
        ],
        'user_id' => [
            'required' => false,
        ],
    ];

    const PARAMETERS = [
        'global' => [
            'api_key' => [
                'name' => 'API Key',
                'type' => 'text',
                'required' => false,
                'title' => 'Your Rule34 API key. Leave empty to use server default'
            ],
            'user_id' => [
                'name' => 'User ID',
                'type' => 'number',
                'required' => false,
                'title' => 'Your Rule34 user ID. Leave empty to use server default'
            ],
            'q' => [
                'name' => 'Query (Tags)',
                'type' => 'text',
                'required' => true,
                'title' => 'Tags for search, separated by commas or spaces (e.g., "tag1, tag2" or "tag1 tag2")'
            ],
            'l' => [
                'name' => 'Posts limit',
                'type' => 'number',
                'required' => false,
                'title' => 'Maximum number of posts to fetch (API hard limit is 1000 per request)',
                'defaultValue' => 10
            ],
            'exclude_ai' => [
                'name' => 'Exclude AI-generated content',
                'type' => 'checkbox',
                'required' => false,
                'defaultValue' => 'checked'
            ],
            'hide_details' => [
                'name' => 'Hide tags and source',
                'type' => 'checkbox',
                'required' => false,
                'defaultValue' => 'checked'
            ]
        ],
        0 => []
    ];

    protected function getFullURI()
    {
        $query = str_replace(',', ' ', $this->getInput('q') ?? '');
        $query = trim(preg_replace('/\s+/', ' ', $query));

        if ($this->getInput('exclude_ai')) {
            $query = trim($query . ' -ai_generated');
        }

        $limit = (int)($this->getInput('l') ?? 10);
        $tags = str_replace(' ', '+', $query);
        $apiKey = urlencode($this->getInput('api_key') ?? '');
        $userId = urlencode($this->getInput('user_id') ?? '');

        return "{$this->getURI()}index.php?page=dapi&s=post&q=index&json=1&pid=0&limit={$limit}&tags={$tags}&api_key={$apiKey}&user_id={$userId}";
    }

    public function collectData()
    {
        $apiKey = $this->getInput('api_key') ?: $this->getOption('api_key');
        $userId = $this->getInput('user_id') ?: $this->getOption('user_id');

        if (empty($apiKey) || empty($userId)) {
            throw new \Exception('API key and user ID are required. Provide them in the bridge parameters or in config.ini.php under the [Rule34Bridge] section.');
        }

        $this->inputs[$this->queriedContext]['api_key']['value'] = $apiKey;
        $this->inputs[$this->queriedContext]['user_id']['value'] = $userId;

        $content = getContents($this->getFullURI());

        if ($content === '') {
            return;
        }

        $posts = Json::decode($content, false);

        if (isset($posts->success) && $posts->success === false) {
            throw new \Exception('API error: ' . ($posts->message ?? 'Unknown error'));
        }

        $posts = $posts->post ?? $posts;

        if (!is_array($posts)) {
            return;
        }

        foreach ($posts as $post) {
            $this->items[] = $this->getItemFromElement($post);
        }
    }

    protected function getItemFromElement($element)
    {
        $pageUrl = self::VIEW_URI . 'index.php?page=post&s=view&id=' . $element->id;
        $thumbnailUrl = $element->preview_url ?? $this->buildThumbnailURI($element);

        $content = sprintf(
            '<a href="%s"><img src="%s" /></a><br><br><b>Dimensions:</b> %s x %s',
            htmlspecialchars($pageUrl),
            htmlspecialchars($thumbnailUrl),
            $element->width,
            $element->height
        );

        if (!$this->getInput('hide_details')) {
            $content .= sprintf('<br><br><b>Tags:</b> %s', htmlspecialchars($element->tags));

            if (!empty($element->source)) {
                $content .= sprintf(
                    '<br><br><b>Source:</b> <a href="%s">%s</a>',
                    htmlspecialchars($element->source),
                    htmlspecialchars($element->source)
                );
            }
        }

        $timestamp = time();
        if (isset($element->created_at)) {
            $timestamp = is_numeric($element->created_at) ? (int)$element->created_at : (strtotime($element->created_at) ?: time());
        } elseif (isset($element->change)) {
            $timestamp = (int)$element->change;
        }

        return [
            'uri'       => $pageUrl,
            'id'        => $pageUrl,
            'title'     => sprintf('Image %s', $element->id),
            'content'   => $content,
            'author'    => $element->owner ?? 'unknown',
            'timestamp' => $timestamp,
        ];
    }
}