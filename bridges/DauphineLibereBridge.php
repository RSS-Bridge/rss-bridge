<?php

class DauphineLibereBridge extends FeedExpander
{
    const MAINTAINER = 'qwertygc';
    const NAME = 'Dauphine Bridge';
    const URI = 'https://www.ledauphine.com/';
    const CACHE_TIMEOUT = 7200; // 2h
    const DESCRIPTION = 'Returns the newest articles.';

    const PARAMETERS = [ [
        'u' => [
            'name' => 'Catégorie de l\'article',
            'type' => 'list',
            'values' => [
                'À la une' => '',
                'France Monde' => 'france-monde',
                'Faits Divers' => 'faits-divers',
                'Économie et Finance' => 'economie-et-finance',
                'Politique' => 'politique',
                'Sport' => 'sport',
                'Ain' => 'ain',
                'Alpes-de-Haute-Provence' => 'haute-provence',
                'Hautes-Alpes' => 'hautes-alpes',
                'Ardèche' => 'ardeche',
                'Drôme' => 'drome',
                'Isère Sud' => 'isere-sud',
                'Savoie' => 'savoie',
                'Haute-Savoie' => 'haute-savoie',
                'Vaucluse' => 'vaucluse'
            ]
        ]
    ]];

    public function collectData()
    {
        $url = self::URI . 'rss';

        if (empty($this->getInput('u'))) {
            $url = self::URI . $this->getInput('u') . '/rss';
        }

        $this->collectExpandableDatas($url, 10);
    }

    protected function parseItem(array $item)
    {
        $item['content'] = $this->extractContent($item['uri']);
        return $item;
    }

    private function extractContent($url)
    {
        $html2 = getSimpleHTMLDOMCached($url);
        foreach ($html2->find('.noprint, link, script, iframe, .shareTool, .contentInfo') as $remove) {
            $remove->outertext = '';
        }
        return $html2->find('div.content', 0)->innertext;
    }
}
