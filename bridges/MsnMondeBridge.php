<?php

class MsnMondeBridge extends FeedExpander
{
    const MAINTAINER = 'kranack';
    const NAME = 'MSN Actu Monde';
    const DESCRIPTION = 'Returns the 10 newest posts from MSN Actualités (full text)';
    const URI = 'https://www.msn.com/fr-fr/actualite';
    const FEED_URL = 'https://rss.msn.com/fr-fr';
    const JSON_URL = 'https://assets.msn.com/content/view/v2/Detail/fr-fr/';
    const LIMIT = 10;

    public function getName()
    {
        return 'MSN Actualités';
    }

    public function getURI()
    {
        return self::URI;
    }

    public function collectData()
    {
        $this->collectExpandableDatas(self::FEED_URL, 10);
    }

    protected function parseItem(array $item)
    {
        if (!preg_match('#fr-fr/actualite.*/ar-(?<id>[\w]*)\?#', $item['uri'], $matches)) {
            return null;
        }

        $jsonString = getContents(self::JSON_URL . $matches['id']);
        $json = json_decode($jsonString, true);
        $item['content'] = $json['body'];
        if (!empty($json['authors'])) {
            $item['author'] = reset($json['authors'])['name'];
        }
        $item['timestamp'] = $json['createdDateTime'];
        foreach ($json['tags'] as $tag) {
            $item['categories'][] = $tag['label'];
        }
        return $item;
    }
}
