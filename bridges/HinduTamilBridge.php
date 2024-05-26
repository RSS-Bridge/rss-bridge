<?php

class HinduTamilBridge extends FeedExpander
{
    const NAME = 'HinduTamil';
    const URI = 'https://www.hindutamil.in';
    const DESCRIPTION = 'Retrieve full articles from hindutamil.in feeds';
    const MAINTAINER = 'tillcash';
    const PARAMETERS = [
        [
            'topic' => [
                'name' => 'topic',
                'type' => 'list',
                'defaultValue' => 'crime',
                'values' => [
                    'Astrology' => 'astrology',
                    'Blogs' => 'blogs',
                    'Business' => 'business',
                    'Cartoon' => 'cartoon',
                    'Cinema' => 'cinema',
                    'Crime' => 'crime',
                    'Discussion' => 'discussion',
                    'Education' => 'education',
                    'Environment' => 'environment',
                    'India' => 'india',
                    'Lifestyle' => 'life-style',
                    'Literature' => 'literature',
                    'Opinion' => 'opinion',
                    'Reporters' => 'reporters-page',
                    'Socialmedia' => 'social-media',
                    'Spirituals' => 'spirituals',
                    'Sports' => 'sports',
                    'Supplements' => 'supplements',
                    'Tamilnadu' => 'tamilnadu',
                    'Technology' => 'technology',
                    'Tourism' => 'tourism',
                    'World' => 'world',
                ],
            ],
            'limit' => [
                'name' => 'limit (max 100)',
                'type' => 'number',
                'defaultValue' => 10,
            ],
        ],
    ];

    const FEED_BASE_URL = 'https://feeds.feedburner.com/Hindu_Tamil_';

    public function getName()
    {
        $topic = $this->getKey('topic');
        return self::NAME . ($topic ? ' - ' . $topic : '');
    }

    public function collectData()
    {
        $limit = min(100, $this->getInput('limit'));
        $url = self::FEED_BASE_URL . $this->getInput('topic');
        $this->collectExpandableDatas($url, $limit);
    }

    protected function parseItem($item)
    {
        $dom = getSimpleHTMLDOMCached($item['uri']);

        $date = $dom->find('p span.date', 1);
        if ($date) {
            $item['timestamp'] = $date->innertext;
        }

        $content = $dom->find('#pgContentPrint', 0);
        if (!$content) {
            return $item;
        }

        $image = $dom->find('#LoadArticle figure', 0);
        $item['content'] = $image . $this->cleanContent($content);

        return $item;
    }

    private function cleanContent($content)
    {
        foreach ($content->find('div[align="center"], script') as $remove) {
            $remove->outertext = '';
        }

        return $content;
    }
}
