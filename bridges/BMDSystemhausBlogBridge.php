<?php

class BMDSystemhausBlogBridge extends BridgeAbstract
{
    const MAINTAINER = 'cn-tools';
    const NAME = 'BMD SYSTEMHAUS GesmbH';
    const CACHE_TIMEOUT = 21600; //6h
    const URI = 'https://www.bmd.com';
    const DESCRIPTION = 'BMD Systemhaus - Blog';

    const PARAMETERS = [
        'Blog' => [
            'country' => [
                'name' => 'Country',
                'type' => 'list',
                'values' => [
                    'Österreich' => 'at',
                    'Deutschland' => 'de',
                    'Schweiz' => 'ch',
                    'Slovensko' => 'sk',
                    'Cesko' => 'cz',
                    'Hungary' => 'hu'
                ]
            ]
        ]
    ];

    //-----------------------------------------------------
    public function collectData()
    {
/*
        $item['uri']        // URI to reach the subject ("https://...")
        $item['title']      // Title of the item
        $item['timestamp']  // Timestamp of the item in numeric or text format (compatible for strtotime())
        $item['author']     // Name of the author for this item
        $item['content']    // Content in HTML format
        $item['enclosures'] // Array of URIs to an attachments (pictures, files, etc...)
        $item['categories'] // Array of categories / tags / topics
        $item['uid']        // A unique ID to identify the current item
*/

        $html = getSimpleHTMLDOM($this->getURI());

        foreach ($html->find('div#bmdNewsList div#bmdNewsList-Item') as $element) {
            $itemScope = $element->find('div[itemscope=itemscope]', 0);

            $item = [];
            $item['title'] = $this->getMetaItemPropContent($itemScope, 'headline');
            $item['content'] = $this->getMetaItemPropContent($itemScope, 'description');
            $item['timestamp'] = strtotime($this->getMetaItemPropContent($itemScope, 'datePublished'));

            $item['author'] = $this->getMetaItemPropContent($itemScope->find('div[itemprop=author]', 0), 'name');
            $item['enclosures'] = [self::URI . $element->find('div.mediaelement.mediaelement-image img', 0)->src];

            $link = $element->find('div#bmdNewsList-Text div#bmdNewsList-Title a', 0);
            if (!is_null($link)) {
                $item['uri'] = self::URI . $link->href;
            }

            $categories = [];
            $tmpOne = [];
            $tmpTwo = [];

            // search first categorie span
            $catElem = $element->find('div#bmdNewsList-Text div#bmdNewsList-Category span.news-list-category', 0);
            $txt = trim($catElem->innertext);
            $tmpOne = explode('/', $txt);

            // split by 2 spaces
            foreach ($tmpOne as $tmpElem) {
                $tmpElem = trim($tmpElem);
                $tmpData = preg_split('/  /', $tmpElem);
                $tmpTwo = array_merge($tmpTwo, $tmpData);
            }

            // split by tabulator
            foreach ($tmpTwo as $tmpElem) {
                $tmpElem = trim($tmpElem);
                $tmpData = preg_split('/\t+/', $tmpElem);
                $categories = array_merge($categories, $tmpData);
            }

            // trim all entries
            $categories = array_map('trim', $categories);

            // remove empty entries
            $categories = array_filter($categories, function ($value) {
                return !is_null($value) && $value !== '';
            });

            // set categories
            if (count($categories) > 0) {
                $item['categories'] = $categories;
            }

            if (($item['title'] != '') and ($item['content'] != '') and ($item['uri'] != '')) {
                $this->items[] = $item;
            }
        }
    }

    //-----------------------------------------------------
    public function detectParameters($url)
    {
        try {
            $parsedUrl = Url::fromString($url);
        } catch (UrlException $e) {
            return null;
        }

        if ($parsedUrl->getHost() != 'www.bmd.com') {
            return null;
        }

        $path = explode('/', $parsedUrl->getPath());

        if ($this->getURIbyCountry($path[1]) == '') {
            return null;
        }

        $params = [];
        $params['country'] = $path[1];
        return $params;
    }

    //-----------------------------------------------------
    public function getURI()
    {
        $lURI = $this->getURIbyCountry($this->getInput('country'));
        return $lURI != '' ? $lURI : parent::getURI();
    }

    //-----------------------------------------------------
    public function getIcon()
    {
        return 'https://www.bmd.com/favicon.ico';
    }

    //-----------------------------------------------------
    private function getMetaItemPropContent($elem, $key)
    {
        if (($key != '') and (!is_null($elem))) {
            $metaElem = $elem->find('meta[itemprop=' . $key . ']', 0);
            if (!is_null($metaElem)) {
                return $metaElem->getAttribute('content');
            }
        }

        return '';
    }

    //-----------------------------------------------------
    private function getURIbyCountry($country)
    {
        switch ($country) {
            case 'at':
                return 'https://www.bmd.com/at/ueber-bmd/blog-ohne-filter.html';
            case 'de':
                return 'https://www.bmd.com/de/das-ist-bmd/blog.html';
            case 'ch':
                return 'https://www.bmd.com/ch/das-ist-bmd/blog.html';
            case 'sk':
                return 'https://www.bmd.com/sk/firma/blog.html';
            case 'cz':
                return 'https://www.bmd.com/cz/firma/news-blog.html';
            case 'hu':
                return 'https://www.bmd.com/hu/rolunk/hirek.html';
            default:
                return '';
        }
    }
}
