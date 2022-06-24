<?php

class FSecureBlogBridge extends BridgeAbstract
{
    const NAME = 'F-Secure Blog';
    const URI = 'https://blog.f-secure.com';
    const DESCRIPTION = 'F-Secure Blog';
    const MAINTAINER = 'simon816';
    const PARAMETERS = array(
        '' => array(
            'categories' => array(
                'name' => 'Blog categories',
                'exampleValue' => 'home-security',
            ),
            'language' => array(
                'name' => 'Language',
                'required' => true,
                'defaultValue' => 'en',
            ),
            'oldest_date' => array(
                'name' => 'Oldest article date',
                'exampleValue' => '-6 months',
            ),
        )
    );

    public function getURI()
    {
        $lang = $this->getInput('language') or 'en';
        if ($lang === 'en') {
            return self::URI;
        }
        return self::URI . "/$lang";
    }

    public function collectData()
    {
        $this->items = array();
        $this->seen = array();

        $this->oldest = strtotime($this->getInput('oldest_date')) ?: 0;

        $categories = $this->getInput('categories');
        if (!empty($categories)) {
            foreach (explode(',', $categories) as $cat) {
                if (!empty($cat)) {
                    $this->collectCategory($cat);
                }
            }
            return;
        }

        $html = getSimpleHTMLDOMCached($this->getURI() . '/');

        foreach ($html->find('ul.c-header-menu-desktop__list li a') as $link) {
            $url = parse_url($link->href);
            if (($pos = strpos($url['path'], '/category/')) !== false) {
                $cat = substr($url['path'], $pos + strlen('/category/'), -1);
                $this->collectCategory($cat);
            }
        }
    }

    private function collectCategory($category)
    {
        $url = $this->getURI() . "/category/$category/";
        while ($url) {
            //Limit total amount of requests
            if (count($this->items) >= 20) {
                break;
            }
            $url = $this->collectListing($url);
        }
    }

    // n.b. this relies on articles to be ordered by date so the cutoff works
    private function collectListing($url)
    {
        $html = getSimpleHTMLDOMCached($url, 60 * 60);
        $items = $html->find('section.b-blog .l-blog__content__listing div.c-listing-item');

        $catName = trim($html->find('section.b-blog .c-blog-header__title', 0)->plaintext);

        foreach ($items as $item) {
            $url = $item->getAttribute('data-url');
            if (!$this->collectArticle($url)) {
                return null; // Too old, stop collecting
            }
        }

        // Point's to 404 for non-english blog
        // $next = $html->find('link[rel=next]', 0);
        $next = $html->find('ul.page-numbers a.next', 0);
        return $next ? $next->href : null;
    }

    // Returns a boolean whether to continue collecting articles
    // i.e. date is after oldest cutoff
    private function collectArticle($url)
    {
        if (array_key_exists($url, $this->seen)) {
            return true;
        }
        $html = getSimpleHTMLDOMCached($url);

        $rssItem = array( 'uri' => $url, 'uid' => $url );
        $rssItem['title'] = $html->find('meta[property=og:title]', 0)->content;
        $dt = $html->find('meta[property=article:published_time]', 0)->content;
        // Exit if too old
        if (strtotime($dt) < $this->oldest) {
            return false;
        }
        $rssItem['timestamp'] = $dt;
        $img = $html->find('meta[property=og:image]', 0);
        $rssItem['enclosures'] = $img ? array($img->content) : array();
        $rssItem['author'] = trim($html->find('.c-blog-author__text a', 0)->plaintext);
        $rssItem['categories'] = array_map(function ($link) {
            return trim($link->plaintext);
        }, $html->find('.b-single-header__categories .c-category-list a'));
        $rssItem['content'] = trim($html->find('article', 0)->innertext);

        $this->items[] = $rssItem;
        $this->seen[$url] = 1;
        return true;
    }
}
