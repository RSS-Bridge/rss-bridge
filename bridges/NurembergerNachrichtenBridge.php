<?php

class NurembergerNachrichtenBridge extends BridgeAbstract
{
    const MAINTAINER = 'schabi.org';
    const NAME = 'Nürnberger Nachrichten';
    const CACHE_TIMEOUT = 3600;
    const URI = 'https://www.nn.de';
    const DESCRIPTION = 'Bridge for NurembergerNachrichten news site nn.de';
    const PARAMETERS = [ [
        'region' => [
            'name' => 'region',
            'type' => 'list',
            'exampleValue' => 'Nürnberg',
            'title' => 'Select a region',
            'values' => [
                'Ansbach' => 'ansbach',
                'Erlangen' => 'erlangen',
                'Erlangen-Höchstadt' => 'erlangen-hoechstadt',
                'Forchheim' => 'forchheim',
                'Fürth' => 'fuerth',
                'Gunzenhausen' => 'gunzenhausen',
                'Neumarkt' => 'neumarkt',
                'Neustadt/Aisch-Bad Windsheim' => 'neustadt-aisch-bad-windsheim',
                'Nürnberg' => 'nuernberg',
                'Nürnberger Land' => 'nuernberger-land',
                'Pegnitz' => 'pegnitz',
                'Roth' => 'roth',
                'Schwabach' => 'schwabach',
                'Weißenburg' => 'weissenburg'
            ]
        ],
        'hideNNPlus' => [
            'name' => 'Hide NN+ articles',
            'type' => 'checkbox',
            'exampleValue' => 'unchecked',
            'title' => 'Hide all paywall articles on NN'
        ],
    ]];

    public function collectData()
    {
        $region = $this->getInput('region');
        if (
            $region === 'neustadt-aisch-bad-windsheim' ||
            $region === 'erlangen-hoechstadt' ||
            $region === ''
        ) {
            $region = 'region/' . $region;
        }
        $url = self::URI . '/' . $region;
        $listSite = getSimpleHTMLDOM($url);

        $this->handleNewsblock($listSite);
    }

    private function handleNewsblock($listSite)
    {
        $main = $listSite->find('main', 0);
        foreach ($main->find('article') as $article) {
            $url = $article->find('a', 0)->href;
            $url = urljoin(self::URI, $url);

            $articleContent = getSimpleHTMLDOMCached($url, 86400 * 7);

            // exclude nn+ articles if desired
            if (
                $this->getInput('hideNNPlus') &&
                $articleContent->find('span[class=icon-nnplus]')
            ) {
                continue;
            }

            $item = $this->parseArticle($articleContent, $url);
            $articleContent->clear();

            $this->items[] = $item;
        }
    }

    private function parseArticle($article, $link)
    {
        $item = [];
        defaultLinkTo($article, self::URI);

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
            $content = $article->find('div[class*=modul__teaser]', 0)->find('p', 0);
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

        return $item;
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
            } elseif ($element->tag === 'ul') {
                $content .= $element;
            }
        }
        return $content;
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
}
