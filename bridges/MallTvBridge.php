<?php

class MallTvBridge extends BridgeAbstract
{
    const NAME = 'MALL.TV Bridge';
    const URI = 'https://www.mall.tv';
    const CACHE_TIMEOUT = 3600;
    const DESCRIPTION = 'Return newest videos';
    const MAINTAINER = 'kolarcz';

    const PARAMETERS = [
        [
            'url' => [
                'name' => 'url to the show',
                'required' => true,
                'exampleValue' => 'https://www.mall.tv/zivot-je-hra'
            ]
        ]
    ];

    private function fixChars($text)
    {
        return html_entity_decode($text, ENT_QUOTES, 'UTF-8');
    }

    private function getUploadTimeFromUrl($url)
    {
        $html = getSimpleHTMLDOM($url);

        $scriptLdJson = $html->find('script[type="application/ld+json"]', 0)->innertext;
        if (!preg_match('/[\'"]uploadDate[\'"]\s*:\s*[\'"](\d{4}-\d{2}-\d{2})[\'"]/', $scriptLdJson, $match)) {
            throwServerException('Could not get date from MALL.TV detail page');
        }

        return strtotime($match[1]);
    }

    public function collectData()
    {
        $url = $this->getInput('url');

        if (!preg_match('/^https:\/\/www\.mall\.tv\/[a-z0-9-]+(\/[a-z0-9-]+)?\/?$/', $url)) {
            throwServerException('Invalid url');
        }

        $html = getSimpleHTMLDOM($url);

        $this->feedUri = $url;
        $this->feedName = $this->fixChars($html->find('title', 0)->plaintext);

        foreach ($html->find('section.isVideo .video-card') as $element) {
            $itemTitle = $element->find('.video-card__details-link', 0);
            $itemThumbnail = $element->find('.video-card__thumbnail', 0);
            $itemUri = self::URI . $itemTitle->getAttribute('href');

            $item = [
                'title' => $this->fixChars($itemTitle->plaintext),
                'uri' => $itemUri,
                'content' => '<img src="' . $itemThumbnail->getAttribute('data-src') . '" />',
                'timestamp' => $this->getUploadTimeFromUrl($itemUri)
            ];

            $this->items[] = $item;
        }
    }

    public function getURI()
    {
        return $this->feedUri ?? parent::getURI();
    }

    public function getName()
    {
        return $this->feedName ?? parent::getName();
    }
}
