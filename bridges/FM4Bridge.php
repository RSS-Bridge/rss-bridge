<?php

class FM4Bridge extends BridgeAbstract
{
    const MAINTAINER = 'joni1993';
    const NAME = 'FM4 Bridge';
    const URI = 'https://fm4.orf.at';
    const CACHE_TIMEOUT = 1800; // 30min
    const DESCRIPTION = 'Feed for FM4 articles by tags (authors)';
    const PARAMETERS = array(
        array(
            'tag' => array(
                'name' => 'Tag (author, category, ...)',
                'title' => 'Tag to retrieve',
                'exampleValue' => 'musik'
            ),
            'loadcontent' => array(
                'name' => 'Load Full Article Content',
                'title' => 'Retrieve full content of articles (may take longer)',
                'type' => 'checkbox'
            ),
            'pages' => array(
                'name' => 'Pages',
                'title' => 'Amount of pages to load',
                'type' => 'number',
                'defaultValue' => 1
            )
        )
    );

    private function getPageData($tag, $page)
    {
        if ($tag) {
            $uri = self::URI . '/tags/' . $tag;
        } else {
            $uri = self::URI;
        }

        $uri = $uri . '?page=' . $page;

        $html = getSimpleHTMLDOM($uri);

        $page_items = array();

        foreach ($html->find('div[class*=listItem]') as $article) {
            $item = array();

            $item['uri'] = $article->find('a', 0)->href;
            $item['title'] = $article->find('h2', 0)->plaintext;
            $item['author'] = $article->find('p[class*=keyword]', 0)->plaintext;
            $item['timestamp'] = strtotime($article->find('p[class*=time]', 0)->plaintext);

            if ($this->getInput('loadcontent')) {
                $item['content'] = getSimpleHTMLDOM($item['uri'])->find('div[class=storyText]', 0);
            }

            $page_items[] = $item;
        }
        return $page_items;
    }

    public function collectData()
    {
        for ($cur_page = 1; $cur_page <= $this->getInput('pages'); $cur_page++) {
            $this->items = array_merge($this->items, $this->getPageData($this->getInput('tag'), $cur_page));
        }
    }
}
