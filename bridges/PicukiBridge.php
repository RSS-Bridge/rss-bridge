<?php

class PicukiBridge extends BridgeAbstract
{
    const MAINTAINER = 'marcus-at-localhost';
    const NAME = 'Picuki Bridge';
    const URI = 'https://www.picuki.com/';
    const CACHE_TIMEOUT = 3600; // 1h
    const DESCRIPTION = 'Returns Picuki posts by user and by hashtag';

    const PARAMETERS = [
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
        $html = getSimpleHTMLDOM($this->getURI());

        foreach ($html->find('.box-photos .box-photo') as $element) {
            // skip ad items
            if (in_array('adv', explode(' ', $element->class))) {
                continue;
            }

            $url = urljoin(self::URI, $element->find('a', 0)->href);

            $author = trim($element->find('.user-nickname', 0)->plaintext);

            $date = date_create();
            $relativeDate = str_replace(' ago', '', $element->find('.time', 0)->plaintext);
            date_sub($date, date_interval_create_from_date_string($relativeDate));

            $description = trim($element->find('.photo-description', 0)->plaintext);

            $isVideo = (bool) $element->find('.video-icon', 0);
            $videoNote = $isVideo ? '<p><i>(video)</i></p>' : '';

            $imageUrl = $element->find('.post-image', 0)->src;

            // the last path segment needs to be encoded, because it contains special characters like + or |
            $imageUrlParts = explode('/', $imageUrl);
            $imageUrlParts[count($imageUrlParts) - 1] = urlencode($imageUrlParts[count($imageUrlParts) - 1]);
            $imageUrl = implode('/', $imageUrlParts);

            // add fake file extension for it to be recognized as image/jpeg instead of application/octet-stream
            $imageUrl = $imageUrl . '#.jpg';

            $this->items[] = [
                'uri'        => $url,
                'author'     => $author,
                'timestamp'  => date_format($date, 'r'),
                'title'      => strlen($description) > 60 ? mb_substr($description, 0, 57) . '...' : $description,
                'thumbnail'  => $imageUrl,
                'enclosures' => [$imageUrl],
                'content'    => <<<HTML
<a href="{$url}">
	<img loading="lazy" src="{$imageUrl}" />
</a>
{$videoNote}
<p>{$description}<p>
HTML
            ];
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
