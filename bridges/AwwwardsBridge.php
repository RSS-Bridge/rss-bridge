<?php

class AwwwardsBridge extends BridgeAbstract
{
    const NAME = 'Awwwards';
    const URI = 'https://www.awwwards.com/';
    const DESCRIPTION = 'Fetches the latest ten sites of the day from Awwwards';
    const MAINTAINER = 'Paroleen';
    const CACHE_TIMEOUT = 3600;

    const SITESURI = 'https://www.awwwards.com/websites/sites_of_the_day/';
    const SITEURI = 'https://www.awwwards.com/sites/';
    const ASSETSURI = 'https://assets.awwwards.com/awards/media/cache/thumb_417_299/';

    private $sites = [];

    public function collectData()
    {
        $this->fetchSites();

        foreach ($this->sites as $site) {
            $item = [];
            $item['title'] = $site['title'];
            $item['timestamp'] = $site['createdAt'];
            $item['categories'] = $site['tags'];

            $item['content'] = '<img src="'
                . self::ASSETSURI
                . $site['images']['thumbnail']
                . '">';
            $item['uri'] = self::SITEURI . $site['slug'];

            $this->items[] = $item;

            if (count($this->items) >= 10) {
                break;
            }
        }
    }

    public function getIcon()
    {
        return 'https://www.awwwards.com/favicon.ico';
    }

    private function fetchSites()
    {
        $sites = getSimpleHTMLDOM(self::SITESURI);
        foreach ($sites->find('.grid-sites li') as $li) {
            $encodedJson = $li->attr['data-collectable-model-value'] ?? null;
            if (!$encodedJson) {
                continue;
            }
            $json = html_entity_decode($encodedJson, ENT_QUOTES, 'utf-8');
            $site = Json::decode($json);
            $this->sites[] = $site;
        }
    }
}
