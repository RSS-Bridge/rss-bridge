<?php

class UsbekEtRicaBridge extends BridgeAbstract
{
    const MAINTAINER = 'logmanoriginal';
    const NAME = 'Usbek & Rica Bridge';
    const URI = 'https://usbeketrica.com';
    const DESCRIPTION = 'Returns latest articles from the front page';

    const PARAMETERS = [
        [
            'limit' => [
                'name' => 'Number of articles to return',
                'type' => 'number',
                'required' => false,
                'title' => 'Specifies the maximum number of articles to return',
                'defaultValue' => -1
            ],
            'fullarticle' => [
                'name' => 'Load full article',
                'type' => 'checkbox',
                'required' => false,
                'title' => 'Activate to load full articles',
            ]
        ]
    ];

    public function collectData()
    {
        $limit = $this->getInput('limit');
        $fullarticle = $this->getInput('fullarticle');
        $html = getSimpleHTMLDOM($this->getURI());

        $articles = $html->find('article');

        foreach ($articles as $article) {
            $item = [];

            $title = $article->find('h2', 0);
            if ($title) {
                $item['title'] = $title->plaintext;
            } else {
                // Sometimes we get rubbish, ignore.
                continue;
            }

            $author = $article->find('div.author span', 0);
            if ($author) {
                $item['author'] = $author->plaintext;
            }

            $u = $article->find('a.card-img', 0);

            $uri = $u->href;
            if (substr($uri, 0, 1) === 'h') { // absolute uri
                $item['uri'] = $uri;
            } else { // relative uri
                $item['uri'] = $this->getURI() . $uri;
            }

            if ($fullarticle) {
                $content = $this->loadFullArticle($item['uri']);
            }

            if ($fullarticle && !is_null($content)) {
                $item['content'] = $content;
            } else {
                $excerpt = $article->find('div.card-excerpt', 0);
                if ($excerpt) {
                    $item['content'] = $excerpt->plaintext;
                }
            }

            $image = $article->find('div.card-img img', 0);
            if ($image) {
                $item['enclosures'] = [
                    $image->src
                ];
            }

            $this->items[] = $item;

            if ($limit > 0 && count($this->items) >= $limit) {
                break;
            }
        }
    }

    /**
    * Loads the full article and returns the contents
    * @param $uri The article URI
    * @return The article content
    */
    private function loadFullArticle($uri)
    {
        $html = getSimpleHTMLDOMCached($uri);

        $content = $html->find('div.rich-text', 1);
        if ($content) {
            return $this->replaceUriInHtmlElement($content);
        }

        return null;
    }

    /**
    * Replaces all relative URIs with absolute ones
    * @param $element A simplehtmldom element
    * @return The $element->innertext with all URIs replaced
    */
    private function replaceUriInHtmlElement($element)
    {
        return str_replace('href="/', 'href="' . $this->getURI() . '/', $element->innertext);
    }
}
