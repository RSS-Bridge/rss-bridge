<?php

class PhoronixBridge extends FeedExpander
{
    const MAINTAINER = 'IceWreck';
    const NAME = 'Phoronix Bridge';
    const URI = 'https://www.phoronix.com';
    const CACHE_TIMEOUT = 3600;
    const DESCRIPTION = 'RSS feed for Linux news website Phoronix';
    const PARAMETERS = [[
        'n' => [
            'name' => 'Limit',
            'type' => 'number',
            'required' => false,
            'title' => 'Maximum number of items to return',
            'defaultValue' => 10
        ],
        'svgAsImg' => [
            'name' => 'SVG in "image" tag',
            'type' => 'checkbox',
            'title' => 'Some benchmarks are exported as SVG with "object" tag,
but some RSS readers don\'t support this. "img" tag are supported by most browsers',
            'defaultValue' => false
        ],
    ]];

    public function collectData()
    {
        $this->collectExpandableDatas('https://www.phoronix.com/rss.php', $this->getInput('n'));
    }

    protected function parseItem($newsItem)
    {
        $item = parent::parseItem($newsItem);
        // $articlePage gets the entire page's contents
        $articlePage = getSimpleHTMLDOM($newsItem->link);
        $articlePage = defaultLinkTo($articlePage, $this->getURI());
        // Extract final link. From Facebook's like plugin.
        parse_str(parse_url($articlePage->find('iframe[src^=//www.facebook.com/plugins]', 0), PHP_URL_QUERY), $facebookQuery);
        if (array_key_exists('href', $facebookQuery)) {
            $newsItem->link = $facebookQuery['href'];
        }
        $item['content'] = $this->extractContent($articlePage);

        $pages = $articlePage->find('.pagination a[!title]');
        foreach ($pages as $page) {
            $pageURI = urljoin($newsItem->link, html_entity_decode($page->href));
            $page = getSimpleHTMLDOM($pageURI);
            $item['content'] .= $this->extractContent($page);
        }
        return $item;
    }

    private function extractContent($page)
    {
        $content = $page->find('.content', 0);
        $objects = $content->find('script[src^=//openbenchmarking.org]');
        foreach ($objects as $object) {
            $objectSrc = preg_replace('/p=0/', 'p=2', $object->src);
            if ($this->getInput('svgAsImg')) {
                $object->outertext = '<a href="' . $objectSrc . '"><img src="' . $objectSrc . '"/></a>';
            } else {
                $object->outertext = '<object data="' . $objectSrc . '" type="image/svg+xml"></object>';
            }
        }
        $content = stripWithDelimiters($content, '<script', '</script>');
        return $content;
    }
}
