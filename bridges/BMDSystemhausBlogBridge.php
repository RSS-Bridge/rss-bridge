<?php

class BMDSystemhausBlogBridge extends BridgeAbstract
{
    const MAINTAINER = 'cn-tools';
    const NAME = 'BMD SYSTEMHAUS GesmbH';
    const CACHE_TIMEOUT = 21600; //6h
    const URI = 'https://www.bmd.com';
    const DONATION_URI = 'https://paypal.me/cntools';
    const DESCRIPTION = 'BMD Systemhaus - We make business easy';

    const ITEMSTYLE = [
        'ilcr' => '<table width="100%"><tr><td style="vertical-align: top;">{data_img}</td><td style="vertical-align: top;">{data_content}</td></tr></table>',
        'clir' => '<table width="100%"><tr><td style="vertical-align: top;">{data_content}</td><td style="vertical-align: top;">{data_img}</td></tr></table>',
        'itcb' => '<div>{data_img}<br />{data_content}</div>',
        'ctib' => '<div>{data_content}<br />{data_img}</div>',
        'co' => '{data_content}',
        'io' => '{data_img}'
    ];

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
                    'Hungary' => 'hu',
                ],
                'defaultValue' => 'at',
            ],
            'style' => [
                'name' => 'Style',
                'type' => 'list',
                'values' => [
                    'Image left, content right' => 'ilcr',
                    'Content left, image right' => 'clir',
                    'Image top, content bottom' => 'itcb',
                    'Content top, image bottom' => 'ctib',
                    'Content only' => 'co',
                    'Image only' => 'io',
                ],
                'defaultValue' => 'ilcr',
            ]
        ]
    ];

    //-----------------------------------------------------
    public function collectData()
    {
        // get website content
        $html = getSimpleHTMLDOM($this->getURI()) or returnServerError('No contents received!');

        // Convert relative links in HTML into absolute links
        $html = defaultLinkTo($html, self::URI);

        // Convert lazy-loading images and frames (video embeds) into static elements
        $html = convertLazyLoading($html);

        foreach ($html->find('div#bmdNewsList div#bmdNewsList-Item') as $element) {
            $itemScope = $element->find('div[itemscope=itemscope]', 0);

            $item = [];

            // set base article data
            $item['title'] = $this->getMetaItemPropContent($itemScope, 'headline');
            $item['timestamp'] = strtotime($this->getMetaItemPropContent($itemScope, 'datePublished'));
            $item['author'] = $this->getMetaItemPropContent($itemScope->find('div[itemprop=author]', 0), 'name');

            // find article image
            $imageTag = '';
            $image = $element->find('div.mediaelement.mediaelement-image img', 0);
            if ((!is_null($image)) and ($image->src != '')) {
                $item['enclosures'] = [$image->src];
                $imageTag = '<img src="' . $image->src . '"/>';
            }

            // begin with right style
            $content = self::ITEMSTYLE[$this->getInput('style')];

            // render placeholder
            $content = str_replace('{data_content}', $this->getMetaItemPropContent($itemScope, 'description'), $content);
            $content = str_replace('{data_img}', $imageTag, $content);

            // set finished content
            $item['content'] = $content;

            // get link to article
            $link = $element->find('div#bmdNewsList-Text div#bmdNewsList-Title a', 0);
            if (!is_null($link)) {
                $item['uri'] = $link->href;
            }

            // init categories
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

            // trim each categorie entries
            $categories = array_map('trim', $categories);

            // remove empty entries
            $categories = array_filter($categories, function ($value) {
                return !is_null($value) && $value !== '';
            });

            // set categories
            if (count($categories) > 0) {
                $item['categories'] = $categories;
            }

            // add item
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
