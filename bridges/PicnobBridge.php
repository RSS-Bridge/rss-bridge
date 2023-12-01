<?php

class PicnobBridge extends BridgeAbstract
{
        const MAINTAINER = 'sysadminstory';
        const NAME = 'Picnob Bridge';
        const URI = 'https://www.picnob.com/';
        const CACHE_TIMEOUT = 3600; // 1h
        const DESCRIPTION = 'Returns Picnob (Instagram viewer) posts by user or by hashtag';

        const PARAMETERS = [
                'Username' => [
                        'u' => [
                                'name' => 'username',
                                'type' => 'text',
                                'title' => 'Instagram username you want to follow',
                                'exampleValue' => 'aesoprockwins',
                                'required' => true,
                        ],
                ],
                'Hashtag' => [
                        'h' => [
                                'name' => 'hashtag',
                                'type' => 'text',
                                'title' => 'Instagram hastag you want to follow, without the \'#\'',
                                'exampleValue' => 'beautifulday',
                                'required' => true,
                        ],
                ]
        ];

        public function getURI()
        {
            if (!is_null($this->getInput('u'))) {
                    return urljoin(self::URI, '/profile/' . $this->getInput('u') . '/');
            }

            if (!is_null($this->getInput('h'))) {
                    return urljoin(self::URI, '/tag/' . trim($this->getInput('h') . '/'));
            }

                return parent::getURI();
        }

        public function collectData()
        {
            $html = getSimpleHTMLDOM($this->getURI());
            foreach ($html->find('.items') as $part) {
                foreach ($part->find('.item') as $element) {
                    $url = urljoin(self::URI, $element->find('a', 0)->href);

                    $date = date_create();
                    $relativeDate = date_interval_create_from_date_string(str_replace(' ago', '', $element->find('.time', 0)->plaintext));
                    if ($relativeDate) {
                        date_sub($date, $relativeDate);
                    }

                    $description = defaultLinkTo(trim($element->find('.sum', 0)->innertext), self::URI);

                    $isVideo = (bool) $element->find('.icon_video', 0);
                    $videoNote = $isVideo ? '<p><i>(video)</i></p>' : '';

                    $isTV = (bool) $element->find('.icon_tv', 0);
                    $tvNote = $isTV ? '<p><i>(TV)</i></p>' : '';

                    $isMoreContent = (bool) $element->find('.icon_multi', 0);
                    $moreContentNote = $isMoreContent ? '<p><i>(multiple images and/or videos)</i></p>' : '';

                    $imageUrl = $element->find('.img', 0)->getAttribute('data-src');

                    $uid = explode('/', parse_url($url, PHP_URL_PATH))[2];

                    $this->items[] = [
                        'uri'        => $url,
                        'timestamp'  => date_format($date, 'r'),
                        'title'      => strlen($description) > 60 ? mb_substr($description, 0, 57) . '...' : $description,
                        'thumbnail'  => $imageUrl,
                        'enclosures' => [$imageUrl],
                        'content'    => <<<HTML
<a href="{$url}">
        <img loading="lazy" src="{$imageUrl}" />
</a>
{$videoNote}
{$tvNote}
{$moreContentNote}
<p>{$description}<p>
HTML,
                        'uid' => $uid
                    ];
                }
            }
        }

        public function getName()
        {
            if (!is_null($this->getInput('u'))) {
                    return 'Username ' . $this->getInput('u') . ' - Picnob Bridge';
            }

            if (!is_null($this->getInput('h'))) {
                    return 'Hashtag ' . $this->getInput('h') . ' - Picnob Bridge';
            }

                return parent::getName();
        }
}
