<?php

class PirateCommunityBridge extends BridgeAbstract
{
    const NAME = 'Pirate-Community Bridge';
    const URI = 'https://raymanpc.com/';
    const CACHE_TIMEOUT = 300; // 5min
    const DESCRIPTION = 'Returns replies to topics';
    const MAINTAINER = 'Roliga';
    const PARAMETERS = [ [
        't' => [
            'name' => 'Topic ID',
            'type' => 'number',
            'exampleValue' => '12651',
            'title' => 'Topic ID from topic URL. If the URL contains t=12 the ID is 12.',
            'required' => true
        ]]];

    private $feedName = '';

    public function detectParameters($url)
    {
        $parsed_url = parse_url($url);

        $host = $parsed_url['host'] ?? null;

        if ($host !== 'raymanpc.com') {
            return null;
        }

        parse_str($parsed_url['query'], $parsed_query);

        if (
            $parsed_url['path'] === '/forum/viewtopic.php'
            && array_key_exists('t', $parsed_query)
        ) {
            return ['t' => $parsed_query['t']];
        }

        return null;
    }

    public function getName()
    {
        if (!empty($this->feedName)) {
            return $this->feedName;
        }

        return parent::getName();
    }

    public function getURI()
    {
        if (!is_null($this->getInput('t'))) {
            return self::URI
                . 'forum/viewtopic.php?t='
                . $this->getInput('t')
                . '&sd=d'; // sort posts decending by ate so first page has latest posts
        }

        return parent::getURI();
    }

    public function collectData()
    {
        $html = getSimpleHTMLDOM($this->getURI());

        $this->feedName = $html->find('head title', 0)->plaintext;

        foreach ($html->find('.post') as $reply) {
            $item = [];

            $item['uri'] = $this->getURI()
                . $reply->find('h3 a', 0)->getAttribute('href');

            $item['title'] = $reply->find('h3 a', 0)->plaintext;

            $author_html = $reply->find('.author', 0);
            // author_html contains the timestamp as text directly inside it,
            // so delete all other child elements
            foreach ($author_html->children as $child) {
                $child->outertext = '';
            }
            // Timestamps are always in UTC+1
            $item['timestamp'] = trim($author_html->innertext) . ' +01:00';

            $item['author'] = $reply
                ->find('.username, .username-coloured', 0)
                ->plaintext;

            $item['content'] = defaultLinkTo(
                $reply->find('.content', 0)->innertext,
                $this->getURI()
            );

            $item['enclosures'] = [];
            foreach ($reply->find('.attachbox img.postimage') as $img) {
                $item['enclosures'][] = urljoin($this->getURI(), $img->src);
            }

            $this->items[] = $item;
        }
    }
}
