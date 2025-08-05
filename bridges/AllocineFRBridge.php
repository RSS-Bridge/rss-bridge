<?php

class AllocineFRBridge extends BridgeAbstract
{
    const MAINTAINER = 'superbaillot.net';
    const NAME = 'Allo Cine Bridge';
    const CACHE_TIMEOUT = 25200; // 7h
    const URI = 'https://www.allocine.fr';
    const DESCRIPTION = 'Bridge for allocine.fr';
    const PARAMETERS = [ [
        'category' => [
            'name' => 'Emission',
            'type' => 'list',
            'title' => 'Sélectionner l\'emission',
            'values' => [
                'Faux Raccord' => 'faux-raccord',
                'Fanzone' => 'fanzone',
                'Game In Ciné' => 'game-in-cine',
                'Pour la faire courte' => 'pour-la-faire-courte',
                'Home Cinéma' => 'home-cinema',
                'PILS - Par Ici Les Sorties' => 'pils-par-ici-les-sorties',
                'AlloCiné : l\'émission, sur LeStream' => 'allocine-lemission-sur-lestream',
                'Give Me Five' => 'give-me-five',
                'Aviez-vous remarqué ?' => 'aviez-vous-remarque',
                'Et paf, il est mort' => 'et-paf-il-est-mort',
                'The Big Fan Theory' => 'the-big-fan-theory',
                'Clichés' => 'cliches',
                'Complètement...' => 'completement',
                '#Fun Facts' => 'fun-facts',
                'Origin Story' => 'origin-story',
            ]
        ]
    ]];

    public function getURI()
    {
        if (!is_null($this->getInput('category'))) {
            $categories = [
                'faux-raccord' => '/video/programme-12284/',
                'fanzone' => '/video/programme-12298/',
                'game-in-cine' => '/video/programme-12288/',
                'pour-la-faire-courte' => '/video/programme-20960/',
                'home-cinema' => '/video/programme-12287/',
                'pils-par-ici-les-sorties' => '/video/programme-25789/',
                'allocine-lemission-sur-lestream' => '/video/programme-25123/',
                'give-me-five' => '/video/programme-21919/saison-34518/',
                'aviez-vous-remarque' => '/video/programme-19518/',
                'et-paf-il-est-mort' => '/video/programme-25113/',
                'the-big-fan-theory' => '/video/programme-20403/',
                'cliches' => '/video/programme-24834/',
                'completement' => '/video/programme-23859/',
                'fun-facts' => '/video/programme-23040/',
                'origin-story' => '/video/programme-25667/'
            ];

            $category = $this->getInput('category');
            if (array_key_exists($category, $categories)) {
                return static::URI . $this->getLastSeasonURI($categories[$category]);
            } else {
                throwClientException('Emission inconnue');
            }
        }

        return parent::getURI();
    }

    private function getLastSeasonURI($category)
    {
        $html = getSimpleHTMLDOMCached(static::URI . $category, 86400);
        $seasonLink = $html->find('section[class=section-wrap section]', 0)->find('div[class=cf]', 0)->find('a', 0);
        $URI = $seasonLink->href;
        return $URI;
    }

    public function getName()
    {
        if (!is_null($this->getInput('category'))) {
            return self::NAME . ' : ' . $this->getKey('category');
        }

        return parent::getName();
    }

    public function collectData()
    {
        $html = getSimpleHTMLDOM($this->getURI());

        foreach ($html->find('div[class=gd-col-left]', 0)->find('div[class*=video-card]') as $element) {
            $item = [];

            $title = $element->find('a[class*=meta-title-link]', 0);
            $content = trim(defaultLinkTo($element->outertext, static::URI));

            // Replace image 'src' with the one in 'data-src'
            $content = preg_replace('@src="data:image/gif;base64,[A-Za-z0-9+\/]*"@', '', $content);
            $content = preg_replace('@data-src=@', 'src=', $content);

            // Remove date in the content to prevent content update while the video is getting older
            $content = preg_replace('@<div class="meta-sub light">.*<span>[^<]*</span>[^<]*</div>@', '', $content);

            $item['content'] = $content;
            $item['title'] = trim($title->innertext);
            $item['uri'] = static::URI . '/' . substr($title->href, 1);
            $this->items[] = $item;
        }
    }
}
