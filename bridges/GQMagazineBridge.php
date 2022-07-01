<?php

/**
 * An extension of the previous SexactuBridge to cover the whole GQMagazine.
 * This one taks a page (as an example sexe/news or journaliste/maia-mazaurette) which is to be configured,
 * reads all the articles visible on that page, and make a stream out of it.
 * @author nicolas-delsaux
 *
 */
class GQMagazineBridge extends BridgeAbstract
{
    const MAINTAINER = 'Riduidel';

    const NAME = 'GQMagazine';

    // URI is no more valid, since we can address the whole gq galaxy
    const URI = 'https://www.gqmagazine.fr';

    const CACHE_TIMEOUT = 7200; // 2h
    const DESCRIPTION = 'GQMagazine section extractor bridge. This bridge allows you get only a specific section.';

    const DEFAULT_DOMAIN = 'www.gqmagazine.fr';

    const PARAMETERS = [ [
        'domain' => [
            'name' => 'Domain to use',
            'required' => true,
            'defaultValue' => self::DEFAULT_DOMAIN
        ],
        'page' => [
            'name' => 'Initial page to load',
            'required' => true,
            'exampleValue' => 'sexe/news'
        ],
        'limit' => self::LIMIT,
    ]];

    const REPLACED_ATTRIBUTES = [
        'href' => 'href',
        'src' => 'src',
        'data-original' => 'src'
    ];

    const POSSIBLE_TITLES = [
        'h2',
        'h3'
    ];

    private function getDomain()
    {
        $domain = $this->getInput('domain');
        if (empty($domain)) {
            $domain = self::DEFAULT_DOMAIN;
        }
        if (strpos($domain, '://') === false) {
            $domain = 'https://' . $domain;
        }
        return $domain;
    }

    public function getURI()
    {
        return $this->getDomain() . '/' . $this->getInput('page');
    }

    private function findTitleOf($link)
    {
        foreach (self::POSSIBLE_TITLES as $tag) {
            $title = $link->parent()->find($tag, 0);
            if ($title !== null) {
                if ($title->plaintext !== null) {
                    return $title->plaintext;
                }
            }
        }
    }

    public function collectData()
    {
        $html = getSimpleHTMLDOM($this->getURI());

        // Since GQ don't want simple class scrapping, let's do it the hard way and ... discover content !
        $main = $html->find('main', 0);
        $limit = $this->getInput('limit') ?? 10;
        foreach ($main->find('a') as $link) {
            if (count($this->items) >= $limit) {
                break;
            }

            $uri = $link->href;
            $date = $link->parent()->find('time', 0);

            $item = [];
            $author = $link->parent()->find('span[itemprop=name]', 0);
            if ($author !== null) {
                $item['author'] = $author->plaintext;
                $item['title'] = $this->findTitleOf($link);
                switch (substr($uri, 0, 1)) {
                    case 'h': // absolute uri
                        $item['uri'] = $uri;
                        break;
                    case '/': // domain relative uri
                        $item['uri'] = $this->getDomain() . $uri;
                        break;
                    default:
                        $item['uri'] = $this->getDomain() . '/' . $uri;
                }
                $article = $this->loadFullArticle($item['uri']);
                if ($article) {
                    $item['content'] = $this->replaceUriInHtmlElement($article);
                } else {
                    $item['content'] = "<strong>Article body couldn't be loaded</strong>. It must be a bug!";
                }
                $short_date = $date->datetime;
                $item['timestamp'] = strtotime($short_date);
                $this->items[] = $item;
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
        return $html->find('article', 0);
    }

    /**
     * Replaces all relative URIs with absolute ones
     * @param $element A simplehtmldom element
     * @return The $element->innertext with all URIs replaced
     */
    private function replaceUriInHtmlElement($element)
    {
        $returned = $element->innertext;
        foreach (self::REPLACED_ATTRIBUTES as $initial => $final) {
            $returned = str_replace($initial . '="/', $final . '="' . self::URI . '/', $returned);
        }
        return $returned;
    }
}
