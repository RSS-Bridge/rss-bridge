<?php

class CustomBridge extends BridgeAbstract
{
    const MAINTAINER = 'ORelio';
    const NAME = 'Custom Bridge';
    const URI = 'https://github.com/RSS-Bridge/rss-bridge/';
    const DESCRIPTION = 'Quickly convert any site to RSS feed using HTML selectors (Advanced Users)';
    const PARAMETERS = [
        [
            'home_page' => [
                'name' => 'Site URL: Home page with latest articles',
                'exampleValue' => 'https://example.com/blog/',
                'required' => true
            ],
            'url_selector' => [
                'name' => 'Selector for article links or their parent elements',
                'exampleValue' => 'a.article',
                'required' => true
            ],
            'content_selector' => [
                'name' => 'Selector for each article content',
                'exampleValue' => 'article.content',
                'required' => true
            ],
            'content_cleanup' => [
                'name' => '[Optional] Content cleanup: List of items to remove',
                'exampleValue' => 'div.ads, div.comments',
            ],
            'title_cleanup' => [
                'name' => '[Optional] Text to remove from article title',
                'exampleValue' => ' | BlogName',
            ],
            'limit' => self::LIMIT
        ]
    ];

    private $feedName = '';

    public function getURI()
    {
        $url = $this->getInput('home_page');
        if (empty($url)) {
            $url = parent::getURI();
        }
        return $url;
    }

    public function getName()
    {
        if (!empty($this->feedName)) {
            return $this->feedName;
        }
        return parent::getName();
    }

    public function collectData()
    {
        $limit = $this->getInput('limit') ?? 10;
        $content_cleanup = $this->getInput('content_cleanup');
        $title_cleanup = $this->getInput('title_cleanup');
        $url = $this->getInput('home_page');
        $html = defaultLinkTo(getSimpleHTMLDOM($url), $url);
        $this->feedName = html_entity_decode($html->find('title', 0)->plaintext);
        $links = $html->find($this->getInput('url_selector'));

        if (empty($links)) {
            returnClientError('No results for URL selector');
        }

        if (!empty($title_cleanup)) {
            $this->feedName = trim(str_replace($title_cleanup, '', $this->feedName));
        }

        if ($limit > 0 && count($links) > $limit) {
            $links = array_slice($links, 0, $limit);
        }

        foreach ($links as $link) {
            if ($link->tag != 'a') {
                $link = $link->find('a', 0);
            }

            $entry_url = $link->href;
            $entry_html = getSimpleHTMLDOMCached($entry_url);
            $article_content = $entry_html->find($this->getInput('content_selector'));

            if (!empty($article_content)) {
                $article_content = $article_content[0];
            } else {
                returnClientError('Could not find content selector at URL: ' . $entry_url);
            }

            if (!empty($content_cleanup)) {
                foreach ($article_content->find($content_cleanup) as $item_to_clean) {
                    $item_to_clean->outertext = '';
                }
            }

            $article_title = html_entity_decode($entry_html->find('title', 0)->plaintext);
            if (!empty($title_cleanup)) {
                $article_title = trim(str_replace($title_cleanup, '', $article_title));
            }

            $article_content = convertLazyLoading($article_content);
            $article_content = defaultLinkTo($article_content, $entry_url);

            $item = [];
            $item['uri'] = $entry_url;
            $item['title'] = $article_title;
            $item['content'] = $article_content;
            $this->items[] = $item;
        }
    }
}
