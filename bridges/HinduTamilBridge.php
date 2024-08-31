<?php

class HinduTamilBridge extends FeedExpander
{
    const NAME = 'HinduTamil';
    const URI = 'https://www.hindutamil.in';
    const FEED_BASE_URL = 'https://feeds.feedburner.com/Hindu_Tamil_';
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
        $content = $dom->find('#pgContentPrint', 0);

        if ($content === null) {
            return $item;
        }

        $item['timestamp'] = $this->getTimestamp($dom) ?? $item['timestamp'];
        $item['content'] = $this->getImage($dom) . $this->cleanContent($content);

        return $item;
    }

    private function cleanContent($content): string
    {
        foreach ($content->find('div[align="center"], script, .adsplacement') as $remove) {
            $remove->outertext = '';
        }

        return $content->innertext;
    }

    private function getTimestamp($dom): ?string
    {
        $date = $dom->find('meta[property="article:published_time"]', 0);
        return $date ? $date->getAttribute('content') : null;
    }

    private function getImage($dom): string
    {
        $image = $dom->find('meta[property="og:image"]', 0);
        return $image ? sprintf('<p><img src="%s"></p>', $image->getAttribute('content')) : '';
    }
}
