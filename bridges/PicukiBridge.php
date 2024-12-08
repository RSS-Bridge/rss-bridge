<?php

class PicukiBridge extends BridgeAbstract
{
    const MAINTAINER = 'marcus-at-localhost';
    const NAME = 'Picuki Bridge';
    const URI = 'https://www.picuki.com/';
    const CACHE_TIMEOUT = 3600; // 1h
    const DESCRIPTION = 'Returns Picuki (Instagram viewer) posts by user and by hashtag';

    const PARAMETERS = [
        'global' => [
            'count' => [
                'name' => 'Count',
                'type' => 'number',
                'title' => 'How many posts to fetch',
                'defaultValue' => 12
            ]
        ],
        'Username' => [
            'u' => [
                'name' => 'username',
                'exampleValue' => 'aesoprockwins',
                'required' => true,
            ],
        ],
        'Hashtag' => [
            'h' => [
                'name' => 'hashtag',
                'exampleValue' => 'beautifulday',
                'required' => true,
            ],
        ]
    ];

    public function getURI()
    {
        if (!is_null($this->getInput('u'))) {
            return urljoin(self::URI, '/profile/' . $this->getInput('u'));
        }

        if (!is_null($this->getInput('h'))) {
            return urljoin(self::URI, '/tag/' . trim($this->getInput('h'), '#'));
        }

        return parent::getURI();
    }

    public function collectData()
    {
        $re = '#let short_code = "(.*?)";\s*$#m';
        $html = getSimpleHTMLDOM($this->getURI());

        $requestedCount = $this->getInput('count');
        if ($requestedCount > 12) {
            // Picuki shows 12 posts per page at initial load.
            throw new \Exception('Maximum count is 12');
        }

        $count = 0;
        foreach ($html->find('div[class=.box-photo][data-s=media]') as $element) {
            // skip ad items
            if (in_array('adv', explode(' ', $element->class))) {
                continue;
            }

            $url = $element->find('a', 0)->href;
            $html_single = getSimpleHTMLDOMCached($url);
            $sourceUrl = null;
            if (preg_match($re, $html_single, $matches) > 0) {
                $sourceUrl = 'https://instagram.com/p/' . $matches[1];
            }

            //$author = trim($element->find('.single-photo-nickname', 0)->plaintext);

            $date = date_create();
            $relativeDate = str_replace(' ago', '', $element->find('.time', 0)->plaintext);
            date_sub($date, date_interval_create_from_date_string($relativeDate));

            $description = trim($element->find('.photo-action-description', 0)->plaintext);

            $isVideo = (bool) $element->find('.video-icon', 0);
            $videoNote = $isVideo ? '<p><i>(video)</i></p>' : '';

            $imageUrl = $element->find('.post-image', 0)->src;

            // the last path segment needs to be encoded, because it contains special characters like + or |
            $imageUrlParts = explode('/', $imageUrl);
            $imageUrlParts[count($imageUrlParts) - 1] = urlencode($imageUrlParts[count($imageUrlParts) - 1]);
            $imageUrl = implode('/', $imageUrlParts);

            $this->items[] = [
                'uri'        => $url,
                /*'author'     => $author,*/
                'timestamp'  => date_format($date, 'r'),
                'title'      => strlen($description) > 60 ? mb_substr($description, 0, 57) . '...' : $description,
                'thumbnail'  => $imageUrl,
                'source'     => $sourceUrl,
                'enclosures' => [$imageUrl],
                'content'    => <<<HTML
                    <a href="{$url}">
                        <img loading="lazy" src="{$imageUrl}" />
                    </a>
                    <a href="{$sourceUrl}">{$sourceUrl}</a>
                    {$videoNote}
                    <p>{$description}<p>
                    HTML
            ];

            $count++;
            if ($count >= $requestedCount) {
                break;
            }
        }
    }

    public function getName()
    {
        if (!is_null($this->getInput('u'))) {
            return $this->getInput('u') . ' - Picuki Bridge';
        }

        if (!is_null($this->getInput('h'))) {
            return $this->getInput('h') . ' - Picuki Bridge';
        }

        return parent::getName();
    }
}
