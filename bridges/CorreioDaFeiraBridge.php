<?php

class CorreioDaFeiraBridge extends BridgeAbstract
{
    const NAME = 'Correio da Feira';
    const URI = 'https://www.correiodafeira.pt/';
    const DESCRIPTION = 'Returns news from the Portuguese local newspaper Correio da Feira';
    const MAINTAINER = 'rmscoelho';
    const CACHE_TIMEOUT = 86400;
    const PARAMETERS = [
        [
            'feed' => [
                'name' => 'News Feed',
                'type' => 'list',
                'title' => 'Feeds from the Portuguese sports newspaper A BOLA.PT',
                'values' => [
                    'Cultura' => 'cultura',
                    'Desporto' => 'desporto',
                    'Economia' => 'economia',
                    'Entrevista' => 'entrevista',
                    'Freguesias' => 'freguesias',
                    'Justiça' => 'justica',
                    'Opinião' => 'opiniao',
                    'Política' => 'politica',
                    'Reportagem' => 'reportagem',
                    'Sociedade' => 'sociedade',
                    'Tecnologia' => 'tecnologia',
                ]
            ]
        ]
    ];

    public function getIcon()
    {
        return 'https://www.correiodafeira.pt/wp-content/uploads/base_reporter-200x200.jpg';
    }

    public function getName()
    {
        return !is_null($this->getKey('feed')) ? self::NAME . ' | ' . $this->getKey('feed') : self::NAME;
    }

    public function getURI()
    {
        return self::URI . $this->getInput('feed');
    }

    public function collectData()
    {
        $url = sprintf('https://www.correiodafeira.pt/categoria/%s', $this->getInput('feed'));
        $dom = getSimpleHTMLDOM($url);
        $dom = $dom->find('main', 0);
        if (!$dom) {
            throw new \Exception(sprintf('Unable to find css selector on `%s`', $url));
        }
        $dom = defaultLinkTo($dom, $this->getURI());
        foreach ($dom->find('div.post') as $article) {
            $a = $article->find('div.blog-box', 0);
            //Get date and time of publishing
            $time = $a->find('.post-date > :nth-child(2)', 0)->plaintext;
            $datetime = explode('/', $time);
            $year = $datetime[2];
            $month = $datetime[1];
            $day = $datetime[0];
            $timestamp = mktime(0, 0, 0, $month, $day, $year);
            $this->items[] = [
                'title' => $a->find('h2.entry-title > a', 0)->plaintext,
                'uri' => $a->find('h2.entry-title > a', 0)->href,
                'author' => $a->find('li.post-author > a', 0)->plaintext,
                'content' => $a->find('.entry-content > p', 0)->plaintext,
                'timestamp' => $timestamp,
            ];
        }
    }
}
