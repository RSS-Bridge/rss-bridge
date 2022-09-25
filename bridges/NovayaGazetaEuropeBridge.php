<?php

class NovayaGazetaEuropeBridge extends BridgeAbstract
{
    const MAINTAINER = 'sqrtminusone';
    const NAME = 'Novaya Gazeta Europe Bridge';
    const URI = 'https://novayagazeta.eu';

    const CACHE_TIMEOUT = 3600; // 1 hour
    const DESCRIPTION = 'Returns articles from Novaya Gazeta Europe';

    const PARAMETERS = [
        '' => [
            'language' => [
                'name' => 'Language',
                'type' => 'list',
                'defaultValue' => 'ru',
                'values' => [
                    'Russian' => 'ru',
                    'English' => 'en',
                ]
            ],
            'limit' => [
                'name' => 'Limit',
                'type' => 'number',
                'required' => false,
                'title' => 'Maximum number of items to return',
                'defaultValue' => 20
            ]
        ]
    ];

    public function collectData()
    {
        $url = 'https://novayagazeta.eu/api/v1/get/main';
        if ($this->getInput('language') != 'ru') {
            $url .= '?lang=' . $this->getInput('language');
        }

        $json = getContents($url);
        $data = json_decode($json);

        foreach ($data->records as $record) {
            foreach ($record->blocks as $block) {
                if (!property_exists($block, 'date')) {
                    continue;
                }
                $title = strip_tags($block->title);
                if (!empty($block->subtitle)) {
                    $title .= '. ' . strip_tags($block->subtitle);
                }
                $item = [
                    'uri' => self::URI . '/articles/' . $block->slug,
                    'block' => $block,
                    'title' => $title,
                    'author' => join(', ', array_map(function ($author) {
                        return $author->name;
                    }, $block->authors)),
                    'timestamp' => $block->date / 1000,
                    'categories' => $block->tags
                ];
                $this->items[] = $item;
            }
        }
        usort($this->items, function ($item1, $item2) {
            return $item2['timestamp'] <=> $item1['timestamp'];
        });
        if ($this->getInput('limit') !== null) {
            $this->items = array_slice($this->items, 0, $this->getInput('limit'));
        }
        foreach ($this->items as &$item) {
            $block = $item['block'];
            $body = '';
            if (property_exists($block, 'body') && $block->body !== null) {
                $body = self::convertBody($block);
            } else {
                $record_json = getContents("https://novayagazeta.eu/api/v1/get/record?slug={$block->slug}");
                $record_data = json_decode($record_json);
                $body = self::convertBody($record_data->record);
            }
            $item['content'] = $body;
            unset($item['block']);
        }
    }

    private static function convertBody($data)
    {
        $body = '';
        if ($data->previewUrl !== null && !$data->isPreviewHidden) {
            $body .= '<figure><img src="' . $data->previewUrl . '"/>';
            if ($data->previewCaption !== null) {
                $body .= '<figcaption>' . $data->previewCaption . '</figcaption>';
            }
            $body .= '</figure>';
        }
        if ($data->lead !== null) {
            $body .= "<p><b>{$data->lead}</b></p>";
        }
        if (!empty($data->body)) {
            foreach ($data->body as $datum) {
                $body .= self::convertElement($datum);
            }
        }
        return $body;
    }

    private static function convertElement($datum)
    {
        switch ($datum->type) {
            case 'text':
                return $datum->data;
            case 'image/single':
                $alt = strip_tags($datum->data);
                $res = "<figure><img src=\"{$datum->previewUrl}\" alt=\"{$alt}\" />";
                if ($datum->data !== null) {
                    $res .= "<figcaption>{$datum->data}</figcaption>";
                }
                $res .= '</figure>';
                return $res;
            case 'text/quote':
                return "<figure><blockquote>{$datum->data}</blockquote></figure><br>";
            case 'embed/native':
                $desc = $datum->link;
                if (property_exists($datum, 'caption')) {
                    $desc = $datum->caption;
                }
                return "<p><a link=\"{$datum->link}\">{$desc}</a></p>";
            case 'text/framed':
                $res = '';
                if (property_exists($datum, 'typeDisplay')) {
                    $res .= "<p><b>{$datum->typeDisplay}</b></p>";
                }
                $res .= "<p>{$datum->data}</p>";
                if (
                    property_exists($datum, 'attachment')
                    && property_exists($datum->attachment, 'type')
                ) {
                    $res .= self::convertElement($datum->attachment);
                }
                return $res;
            default:
                return '';
        }
    }
}
