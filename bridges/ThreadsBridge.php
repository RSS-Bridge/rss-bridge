<?php

class ThreadsBridge extends BridgeAbstract
{
    const NAME = 'Threads';
    const URI = 'https://www.threads.net/';
    const DESCRIPTION = 'Say more with Threads &#x2014; Instagram&#039;s new text app.';
    const MAINTAINER = 'mdemoss';
    const CACHE_TIMEOUT = 3600;

    const PARAMETERS = [
        'By username' => [
            'u' => [
                'name' => 'username',
                'required' => true,
                'exampleValue' => 'zuck',
                'title' => 'Insert a user name'
            ],
            'limit' => [
                'name' => 'Limit',
                'type' => 'number',
                'required' => false,
                'title' => 'Specify number of posts to fetch',
                'defaultValue' => 5
            ]
        ]
    ];

    protected $feedName = self::NAME;
    public function getName()
    {
        return $this->feedName;
    }

    public function detectParameters($url)
    {
        // By username
        $regex = '/^(https?:\/\/)?(www\.)?threads\.net\/(@)?([^\/?\n]+)/';
        if (preg_match($regex, $url, $matches) > 0) {
            $params['context'] = 'By username';
            $params['u'] = urldecode($matches[3]);
            return $params;
        }
        return null;
    }

    public function getURI()
    {
        return self::URI . '@' . $this->getInput('u');
    }

    // https://stackoverflow.com/a/3975706/421140
    // Found this in FlaschenpostBridge, modified to return an array and take an object.
    private function recursiveFind($haystack, $needle)
    {
        $found = [];
        $iterator = new \RecursiveArrayIterator($haystack);
        $recursive = new \RecursiveIteratorIterator(
            $iterator,
            \RecursiveIteratorIterator::SELF_FIRST
        );
        foreach ($recursive as $key => $value) {
            if ($key === $needle) {
                $found[] = $value;
            }
        }
        return $found;
    }

    public function collectData()
    {
        $html = getSimpleHTMLDOMCached($this->getURI(), static::CACHE_TIMEOUT);

        $jsonBlobs = $html->find('script[type="application/json"]');

        $gatheredPosts = [];
        $limit = $this->getInput('limit');
        foreach ($jsonBlobs as $jsonBlob) {
            // The structure of the JSON document is likely to change, but we're looking for "post" objects
            foreach ($this->recursiveFind(json_decode($jsonBlob->innertext), 'post') as $post) {
                if (!is_object($post) && !is_array($post)) {
                    continue;
                }
                $post = (array)$post;
                if (!isset($post['code'])) {
                    continue;
                }
                $candidateCode = $post['code'];
                // code should be like CzZk4-USq1O or Cy3m1VnRiwP or Cywjyrdv9T6 or CzZk4-USq1O
                if (grapheme_strlen($candidateCode) == 11 and !isset($gatheredPosts[$candidateCode])) {
                    $gatheredPosts[$candidateCode] = [
                        'code' => $candidateCode,
                        'taken_at' => $post['taken_at'] ?? null,
                    ];
                    if (count($gatheredPosts) >= $limit) {
                        break 2;
                    }
                }
            }
        }

        $this->feedName = html_entity_decode($html->find('meta[property=og:title]', 0)->content);
        // todo: meta[property=og:description] could populate the feed description

        foreach ($gatheredPosts as $postData) {
            $item = [];
            // post URL is like: https://www.threads.net/@zuck/post/Czrr520PZfh
            $item['uri'] = $this->getURI() . '/post/' . $postData['code'];
            $articleHtml = getSimpleHTMLDOMCached($item['uri'], 15778800); // cache time: six months

            // Relying on meta tags ought to be more reliable.
            if ($articleHtml->find('meta[property=og:type]', 0)->content != 'article') {
                continue;
            }
            $item['title'] = $articleHtml->find('meta[property=og:description]', 0)->content;
            $item['content'] = $articleHtml->find('meta[property=og:description]', 0)->content;
            $item['author'] = html_entity_decode($articleHtml->find('meta[property=og:title]', 0)->content);

            $imageUrl = $articleHtml->find('meta[property=og:image]', 0);
            if ($imageUrl) {
                $item['enclosures'][] = html_entity_decode($imageUrl->content);
            }

            // todo: parse hashtags out of content for $item['categories']

            // Extract timestamp from profile JSON data (taken_at is a Unix timestamp)
            if (isset($postData['taken_at']) && $postData['taken_at']) {
                $item['timestamp'] = $postData['taken_at'];
            }

            $this->items[] = $item;
        }
    }
}
