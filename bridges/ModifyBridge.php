<?php

class ModifyBridge extends FeedExpander
{
    const MAINTAINER = 'Mynacol';
    const NAME = 'Modify Feed';
    const CACHE_TIMEOUT = 3600; // 1h
    const DESCRIPTION = 'Modifies a feed of your choice with regexes';
    const URI = 'https://github.com/RSS-Bridge/rss-bridge';

    const PARAMETERS = [[
        'url' => [
            'name' => 'Feed URL',
            'type'  => 'text',
            'exampleValue' => 'https://lorem-rss.herokuapp.com/feed?unit=day',
            'required' => true,
        ],
        'title_pattern' => [
            'name' => 'Title find pattern (regular expression!)',
            'type' => 'text',
            'exampleValue' => 'Unwanted part in title',
            'required' => false,
        ],
        'title_replacement' => [
            'name' => 'Title replacement for the find pattern',
            'type' => 'text',
            'exampleValue' => '${0}',
            'required' => false,
        ],
        'author_pattern' => [
            'name' => 'Author find pattern (regular expression!)',
            'type' => 'text',
            'exampleValue' => '^(author)\s*|\s*publisher$',
            'required' => false,
        ],
        'author_replacement' => [
            'name' => 'Author replacement for the find pattern',
            'type' => 'text',
            'exampleValue' => '${1}',
            'required' => false,
        ],
        'content_pattern' => [
            'name' => 'Content find pattern (regular expression!)',
            'type' => 'text',
            'exampleValue' => '(content)\s+advertisement\s+(content)',
            'required' => false,
        ],
        'content_replacement' => [
            'name' => 'Content replacement for the find pattern',
            'type' => 'text',
            'exampleValue' => '${1} ${2}',
            'required' => false,
        ],
        'uri_pattern' => [
            'name' => 'URI/URL find pattern (regular expression!)',
            'type' => 'text',
            'exampleValue' => '^https?://(.*)/(.*)$',
            'required' => false,
        ],
        'uri_replacement' => [
            'name' => 'URI/URL replacement for the find pattern',
            'type' => 'text',
            'exampleValue' => 'https://${1}/foo/${2}',
            'required' => false,
        ],
        'enclosure_pattern' => [
            'name' => 'Enclosure URI/URL find pattern (regular expression!)',
            'type' => 'text',
            'exampleValue' => '^https?://(.*)/(.*)$',
            'required' => false,
        ],
        'enclosure_replacement' => [
            'name' => 'Enclosure URI/URL replacement for the find pattern',
            'type' => 'text',
            'exampleValue' => 'https://${1}/foo/${2}',
            'required' => false,
        ],
        'case_insensitive' => [
            'name' => 'Case-insensitive find patterns',
            'type' => 'checkbox',
            'required' => false,
        ],
    ]];

    public function collectData()
    {
        $url = $this->getInput('url');
        if (!Url::validate($url)) {
            throw new \Exception('The url parameter must either refer to http or https protocol.');
        }
        $this->collectExpandableDatas($this->getURI());
    }

    protected function parseItem(array $item)
    {
        // Title
        $pattern = $this->buildPattern($this->getInput('title_pattern'));
        $replacement = $this->getInput('title_replacement');
        $res = preg_replace($pattern, $replacement, $item['title']);
        if ($res !== null) {
            $item['title'] = $res;
        }

        // Author
        $pattern = $this->buildPattern($this->getInput('author_pattern'));
        $replacement = $this->getInput('author_replacement');
        $res = preg_replace($pattern, $replacement, $item['author']);
        if ($res !== null) {
            $item['author'] = $res;
        }

        // Content
        $pattern = $this->buildPattern($this->getInput('content_pattern'));
        $replacement = $this->getInput('content_replacement');
        $res = preg_replace($pattern, $replacement, $item['content']);
        if ($res !== null) {
            $item['content'] = $res;
        }

        // URI
        $pattern = $this->buildPattern($this->getInput('uri_pattern'));
        $replacement = $this->getInput('uri_replacement');
        $res = preg_replace($pattern, $replacement, $item['uri']);
        if ($res !== null) {
            $item['uri'] = $res;
        }

        // Enclosures
        if (array_key_exists('enclosures', $item)) {
            $pattern = $this->buildPattern($this->getInput('enclosure_pattern'));
            $replacement = $this->getInput('enclosure_replacement');
            foreach ($item['enclosures'] as $key => $val) {
                $res = preg_replace($pattern, $replacement, $val);
                if ($res !== null) {
                    $item['enclosures'][$key] = $res;
                }
            }
        }
        if (array_key_exists('enclosure', $item)) {
            $pattern = $this->buildPattern($this->getInput('enclosure_pattern'));
            $replacement = $this->getInput('enclosure_replacement');
            $res = preg_replace($pattern, $replacement, $item['enclosure']['url']);
            if ($res !== null) {
                $item['enclosure']['url'] = $res;
            }
        }

        return $item;
    }

    private function buildPattern($pattern)
    {
        if (! str_contains($pattern, '#')) {
            $delimiter = '#';
        } elseif (! str_contains($pattern, '/')) {
            $delimiter = '/';
        } else {
            throw new \Exception('Cannot use both / and # inside filter');
        }

        $regex = $delimiter . $pattern . $delimiter;
        if ($this->getInput('case_insensitive')) {
            $regex .= 'i';
        }
        return $regex;
    }

    public function getURI()
    {
        $url = $this->getInput('url');
        if ($url) {
            return $url;
        }
        return parent::getURI();
    }

    public function getName()
    {
        return parent::getName();
    }
}
