<?php

class NextgovBridge extends FeedExpander
{
    const MAINTAINER = 'ORelio';
    const NAME = 'Nextgov Bridge';
    const URI = 'https://www.nextgov.com/';
    const DESCRIPTION = 'USA Federal technology news, best practices, and web 2.0 tools.';

    const PARAMETERS = [ [
        'category' => [
            'name' => 'Category',
            'type' => 'list',
            'values' => [
                'All' => 'all',
                'Technology News' => 'technology-news',
                'CIO Briefing' => 'cio-briefing',
                'Emerging Tech' => 'emerging-tech',
                'Cybersecurity' => 'cybersecurity',
                'IT Modernization' => 'it-modernization',
                'Policy' => 'policy',
                'Ideas' => 'ideas',
            ]
        ]
    ]];

    public function collectData()
    {
        $url = self::URI . 'rss/' . $this->getInput('category') . '/';
        $limit = 10;
        $this->collectExpandableDatas($url, $limit);
    }

    protected function parseItem(array $item)
    {
        $article_thumbnail = 'https://cdn.nextgov.com/nextgov/images/logo.png';
        $item['content'] = '<p><b>' . $item['content'] . '</b></p>';

//        $namespaces = $newsItem->getNamespaces(true);
//        if (isset($namespaces['media'])) {
//            $media = $newsItem->children($namespaces['media']);
//            if (isset($media->content)) {
//                $attributes = $media->content->attributes();
//                $item['content'] = '<p><img src="' . $attributes['url'] . '"></p>' . $item['content'];
//                $article_thumbnail = str_replace(
//                    'large.jpg',
//                    'small.jpg',
//                    strval($attributes['url'])
//                );
//            }
//        }

        $item['enclosures'] = [$article_thumbnail];
        $item['content'] .= $this->extractContent($item['uri']);
        return $item;
    }

    private function extractContent($url)
    {
        $article = getSimpleHTMLDOMCached($url);

        if (!is_object($article)) {
            return 'Could not request Nextgov: ' . $url;
        }

        $contents = $article->find('div.wysiwyg', 0);
        $contents = $contents->innertext;
        $contents = stripWithDelimiters($contents, '<div class="ad-container">', '</div>');
        $contents = stripWithDelimiters($contents, '<div', '</div>'); //ad outer div
        return trim(stripWithDelimiters($contents, '<script', '</script>'));
    }
}
