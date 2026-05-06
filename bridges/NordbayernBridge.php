<?php

class NordbayernBridge extends BridgeAbstract
{
    const MAINTAINER = 'schabi.org';
    const NAME = 'Nordbayern';
    const CACHE_TIMEOUT = 3600;
    const URI = 'https://www.nordbayern.de';
    const DESCRIPTION = 'Bridge for Bavarian regional news site nordbayern.de';
    const PARAMETERS = [
        [
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
                    'Weißenburg-Gunzenhausen' => 'weissenburg-gunzenhausen'
                ]
            ],
            'hideGenussShopping' => [
                'name' => 'Hide Genuss & Shopping',
                'type' => 'checkbox',
                'exampleValue' => 'unchecked',
                'title' => 'Hide articles categorized as Genuss & Shopping'
            ],
            'hideSport' => [
                'name' => 'Hide Sport',
                'type' => 'checkbox',
                'exampleValue' => 'unchecked',
                'title' => 'Hide articles categorized as Sport'
            ],
            'hidePromiesTrends' => [
                'name' => 'Hide Promies & Trends',
                'type' => 'checkbox',
                'exampleValue' => 'unchecked',
                'title' => 'Hide articles categorized as Promies & Trends'
            ],
            'hideService' => [
                'name' => 'Hide Service',
                'type' => 'checkbox',
                'exampleValue' => 'unchecked',
                'title' => 'Hide articles categorized as Service'
            ],
            'hideFranken' => [
                'name' => 'Hide Franken',
                'type' => 'checkbox',
                'exampleValue' => 'unchecked',
                'title' => 'Hide articles categorized as Franken'
            ],
            'hideBayern' => [
                'name' => 'Hide Bayern',
                'type' => 'checkbox',
                'exampleValue' => 'unchecked',
                'title' => 'Hide articles categorized as Bayern'
            ],
            'hidePanorama' => [
                'name' => 'Hide Panorama',
                'type' => 'checkbox',
                'exampleValue' => 'unchecked',
                'title' => 'Hide articles categorized as Panorama'
            ],
            'hidePolizeiberichte' => [
                'name' => 'Hide Polizeiberichte',
                'type' => 'checkbox',
                'exampleValue' => 'unchecked',
                'title' => 'Hide articles categorized as Polizeiberichte'
            ],
            'hideNN' => [
                'name' => 'Hide Nürnberger Nachrichten',
                'type' => 'checkbox',
                'exampleValue' => 'checked',
                'defaultValue' => 'checked',
                'title' => 'Hide articles hosted on www.nn.de'
            ]
        ]
    ];

    public function setInput(array $input)
    {
        // Translate legacy parameter names so existing feed URLs keep working.
        if (isset($input['hideNNPlus'])) {
            $input['hideNN'] = $input['hideNNPlus'];
            unset($input['hideNNPlus']);
        }
        if (isset($input['policeReports'])) {
            if (!filter_var($input['policeReports'], FILTER_VALIDATE_BOOLEAN)) {
                $input['hidePolizeiberichte'] = 'on';
            }
            unset($input['policeReports']);
        }
        parent::setInput($input);
    }

    public function collectData()
    {
        $region = $this->getInput('region');
        if ($region !== 'nurnberg' && $region !== 'fuerth' && $region !== 'erlangen') {
            $region = 'region/' . $region;
        }
        $url = self::URI . '/' . $region;
        $listSite = getSimpleHTMLDOM($url);

        $this->handleNewsblock($listSite);
    }


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
                $content .= $this->getUseFullContent($element->find('article', 0));
            } elseif ($element->tag === 'header') {
                $content .= $this->getUseFullContent($element);
            } elseif (
                $element->tag === 'div' &&
                !str_contains($element->class, 'article__infobox') &&
                !str_contains($element->class, 'authorinfo')
            ) {
                $content .= $this->getUseFullContent($element);
            } elseif (
                $element->tag === 'section' &&
                (str_contains($element->class, 'article__richtext') ||
                    str_contains($element->class, 'article__context'))
            ) {
                $content .= $this->getUseFullContent($element);
            } elseif ($element->tag === 'picture') {
                $content .= $this->getValidImage($element);
            } elseif ($element->tag === 'button') {
                $content .= $this->getUseFullContent($element);
            } elseif ($element->tag === 'ul') {
                $content .= $element;
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

    private function getArticle($link)
    {
        $item = [];
        $article = getSimpleHTMLDOM($link);
        defaultLinkTo($article, self::URI);
        $content = $article->find('article[id=article]', 0);
        $item['uri'] = $link;
        $item['uid'] = hash('sha256', $link);

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
            $item['content'] .= $this->getTeaser($content);
            $item['content'] .= $this->getUseFullContent($content);
        }

        $categories = $article->find('[class=themen]', 0);
        if ($categories) {
            $item['categories'] = [];
            foreach ($categories->find('a') as $category) {
                $item['categories'][] = $category->innertext;
            }
        }

        $article->clear();
        return $item;
    }

    private function findMostReadSection($main)
    {
        foreach ($main->find('section') as $section) {
            $header = $section->find('div[class=modul__header]', 0);
            if ($header !== null && str_contains($header->plaintext, 'Meistgelesen in Nürnberg')) {
                return $section;
            }
        }
        return null;
    }

    private function isInsideSection($article, $section)
    {
        if ($section === null) {
            return false;
        }
        $ancestor = $article->parent;
        while ($ancestor !== null) {
            if ($ancestor === $section) {
                return true;
            }
            $ancestor = $ancestor->parent;
        }
        return false;
    }

    private function handleNewsblock($listSite)
    {
        $main = $listSite->find('main', 0);
        $meistgelesenSection = $this->findMostReadSection($main);
        foreach ($main->find('article') as $article) {
            // skip articles inside the "Meistgelesen in Nürnberg" section
            if ($this->isInsideSection($article, $meistgelesenSection)) {
                continue;
            }

            // skip empty articles
            if (is_null($article->find('a', 0))) {
                continue;
            }

            $url = $article->find('a', 0)->href;
            $url = urljoin(self::URI, $url);

            // skip articles based on category segment in URL
            if ($this->getInput('hideGenussShopping') && str_contains($url, '/genuss-shopping/')) {
                continue;
            }
            if ($this->getInput('hideSport') && str_contains($url, '/sport/')) {
                continue;
            }
            if ($this->getInput('hidePromiesTrends') && str_contains($url, '/promis-trends/')) {
                continue;
            }
            if ($this->getInput('hideService') && str_contains($url, '/service/')) {
                continue;
            }
            if ($this->getInput('hideFranken') && str_contains($url, '/franken/')) {
                continue;
            }
            if ($this->getInput('hideBayern') && str_contains($url, '/bayern/')) {
                continue;
            }
            if ($this->getInput('hidePanorama') && str_contains($url, '/panorama/')) {
                continue;
            }
            if ($this->getInput('hidePolizeiberichte') && str_contains($url, '/polizeibericht')) {
                continue;
            }
            if ($this->getInput('hideNN') && str_contains($url, 'www.nn.de')) {
                continue;
            }

            $item = $this->getArticle($url);

            if (
                $this->getInput('hidePolizeiberichte')
                && str_contains($item['content'], 'Hier geht es zu allen aktuellen Polizeimeldungen.')
            ) {
                continue;
            }

            $this->items[] = $item;
        }
    }
}
