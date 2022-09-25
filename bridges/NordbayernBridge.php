<?php

class NordbayernBridge extends BridgeAbstract
{
    const MAINTAINER = 'schabi.org';
    const NAME = 'Nordbayern';
    const CACHE_TIMEOUT = 3600;
    const URI = 'https://www.nordbayern.de';
    const DESCRIPTION = 'Bridge for Bavarian regional news site nordbayern.de';
    const PARAMETERS = [ [
        'region' => [
            'name' => 'region',
            'type' => 'list',
            'exampleValue' => 'Nürnberg',
            'title' => 'Select a region',
            'values' => [
                'Ansbach' => 'ansbach',
                'Bamberg' => 'bamberg',
                'Bayreuth' => 'bayreuth',
                'Erlangen' => 'erlangen',
                'Forchheim' => 'forchheim',
                'Fürth' => 'fuerth',
                'Gunzenhausen' => 'gunzenhausen',
                'Herzogenaurach' => 'herzogenaurach',
                'Höchstadt' => 'hoechstadt',
                'Neumarkt' => 'neumarkt',
                'Neustadt/Aisch-Bad Windsheim' => 'neustadt-aisch-bad-windsheim',
                'Nürnberg' => 'nuernberg',
                'Nürnberger Land' => 'nuernberger-land',
                'Regensburg' => 'regensburg',
                'Roth' => 'roth',
                'Schwabach' => 'schwabach',
                'Weißenburg' => 'weissenburg'
            ]
        ],
        'policeReports' => [
            'name' => 'Police Reports',
            'type' => 'checkbox',
            'exampleValue' => 'checked',
            'title' => 'Include Police Reports',
        ]
    ]];

    private function getValidImage($picture)
    {
        $img = $picture->find('img', 0);
        if ($img) {
            $imgUrl = $img->src;
            if (!preg_match('#/logo-.*\.png#', $imgUrl)) {
                return '<br><img src="' . $imgUrl . '">';
            }
        }
        return '';
    }

    private function getUseFullContent($rawContent)
    {
        $content = '';
        foreach ($rawContent->children as $element) {
            if (
                ($element->tag === 'p' || $element->tag === 'h3') &&
                $element->class !== 'article__teaser'
            ) {
                $content .= $element;
            } elseif ($element->tag === 'main') {
                $content .= self::getUseFullContent($element->find('article', 0));
            } elseif ($element->tag === 'header') {
                $content .= self::getUseFullContent($element);
            } elseif (
                $element->tag === 'div' &&
                !str_contains($element->class, 'article__infobox') &&
                !str_contains($element->class, 'authorinfo')
            ) {
                $content .= self::getUseFullContent($element);
            } elseif (
                $element->tag === 'section' &&
                (str_contains($element->class, 'article__richtext') ||
                    str_contains($element->class, 'article__context'))
            ) {
                $content .= self::getUseFullContent($element);
            } elseif ($element->tag === 'picture') {
                $content .= self::getValidImage($element);
            }
        }
        return $content;
    }

    private function getTeaser($content)
    {
        $teaser = $content->find('p[class=article__teaser]', 0);
        if ($teaser === null) {
            return '';
        }
        $teaser = $teaser->plaintext;
        $teaser = preg_replace('/[ ]{2,}/', ' ', $teaser);
        $teaser = '<p class="article__teaser">' . $teaser . '</p>';
        return $teaser;
    }

    private function handleArticle($link)
    {
        $item = [];
        $article = getSimpleHTMLDOM($link);
        defaultLinkTo($article, self::URI);
        $content = $article->find('article[id=article]', 0);
        $item['uri'] = $link;

        $author = $article->find('.article__author', 1);
        if ($author !== null) {
            $item['author'] = trim($author->plaintext);
        }

        $createdAt = $article->find('[class=article__release]', 0);
        if ($createdAt) {
            $item['timestamp'] = strtotime(str_replace('Uhr', '', $createdAt->plaintext));
        }

        if ($article->find('h2', 0) === null) {
            $item['title'] = $article->find('h3', 0)->innertext;
        } else {
            $item['title'] = $article->find('h2', 0)->innertext;
        }
        $item['content'] = '';

        if ($article->find('section[class*=article__richtext]', 0) === null) {
            $content = $article->find('div[class*=modul__teaser]', 0)
                           ->find('p', 0);
            $item['content'] .= $content;
        } else {
            $content = $article->find('article', 0);
            // change order of article teaser in order to show it on top
            // of the title image. If we didn't do this some rss programs
            // would show the subtitle of the title image as teaser instead
            // of the actuall article teaser.
            $item['content'] .= self::getTeaser($content);
            $item['content'] .= self::getUseFullContent($content);
        }

        // exclude police reports if desired
        if (
            $this->getInput('policeReports') ||
            !str_contains($item['content'], 'Hier geht es zu allen aktuellen Polizeimeldungen.')
        ) {
            $this->items[] = $item;
        }

        $article->clear();
    }

    private function handleNewsblock($listSite)
    {
        $main = $listSite->find('main', 0);
        foreach ($main->find('article') as $article) {
            $url = $article->find('a', 0)->href;
            $url = urljoin(self::URI, $url);
            self::handleArticle($url);
        }
    }

    public function collectData()
    {
        $region = $this->getInput('region');
        if ($region === 'rothenburg-o-d-t') {
            $region = 'rothenburg-ob-der-tauber';
        }
        $url = self::URI . '/region/' . $region;
        $listSite = getSimpleHTMLDOM($url);

        self::handleNewsblock($listSite);
    }
}
