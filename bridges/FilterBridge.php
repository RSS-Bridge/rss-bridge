<?php

class FilterBridge extends FeedExpander
{
    const MAINTAINER = 'Frenzie, ORelio';
    const NAME = 'Filter';
    const CACHE_TIMEOUT = 3600; // 1h
    const DESCRIPTION = 'Filters a feed of your choice';
    const URI = 'https://github.com/RSS-Bridge/rss-bridge';

    const PARAMETERS = [[
        'url' => [
            'name' => 'Feed URL',
            'type'  => 'text',
            'exampleValue' => 'https://lorem-rss.herokuapp.com/feed?unit=day',
            'required' => true,
        ],
        'filter' => [
            'name' => 'Filter (regular expression!!!)',
            'required' => false,
        ],
        'filter_type' => [
            'name' => 'Filter type',
            'type' => 'list',
            'required' => false,
            'values' => [
                'Keep matching items' => 'permit',
                'Hide matching items' => 'block',
            ],
            'defaultValue' => 'permit',
        ],
        'case_insensitive' => [
            'name' => 'Case-insensitive filter',
            'type' => 'checkbox',
            'required' => false,
        ],
        'fix_encoding' => [
            'name' => 'Attempt Latin1/UTF-8 fixes when evaluating filter',
            'type' => 'checkbox',
            'required' => false,
        ],
        'target_author' => [
            'name' => 'Apply filter on author',
            'type' => 'checkbox',
            'required' => false,
        ],
        'target_content' => [
            'name' => 'Apply filter on content',
            'type' => 'checkbox',
            'required' => false,
        ],
        'target_title' => [
            'name' => 'Apply filter on title',
            'type' => 'checkbox',
            'required' => false,
            'defaultValue' => 'checked'
        ],
        'target_uri' => [
            'name' => 'Apply filter on URI/URL',
            'type' => 'checkbox',
            'required' => false,
        ],
        'title_from_content' => [
            'name' => 'Generate title from content (overwrite existing title)',
            'type' => 'checkbox',
            'required' => false,
        ],
        'length_limit' => [
            'name' => 'Max length analyzed by filter (-1: no limit)',
            'type' => 'number',
            'required' => false,
            'defaultValue' => -1,
        ],
    ]];

    public function collectData()
    {
        $url = $this->getInput('url');
        if (!Url::validate($url)) {
            returnClientError('The url parameter must either refer to http or https protocol.');
        }
        $this->collectExpandableDatas($this->getURI());
    }

    protected function parseItem(array $item)
    {
        // Generate title from first 50 characters of content?
        if ($this->getInput('title_from_content') && array_key_exists('content', $item)) {
            $content = str_get_html($item['content']);
            $plaintext = $content->plaintext;
            if (mb_strlen($plaintext) < 51) {
                $item['title'] = $plaintext;
            } else {
                $pos = strpos($item['content'], ' ', 50);
                $item['title'] = substr($plaintext, 0, $pos);
                if (strlen($plaintext) >= $pos) {
                    $item['title'] .= '...';
                }
            }
        }

        $filter = $this->getInput('filter');
        if (! str_contains($filter, '#')) {
            $delimiter = '#';
        } elseif (! str_contains($filter, '/')) {
            $delimiter = '/';
        } else {
            throw new \Exception('Cannot use both / and # inside filter');
        }

        $regex = $delimiter . $filter . $delimiter;
        if ($this->getInput('case_insensitive')) {
            $regex .= 'i';
        }

        // Retrieve fields to check
        $filter_fields = [];
        if ($this->getInput('target_author')) {
            $filter_fields[] = $item['author'] ?? null;
        }
        if ($this->getInput('target_content')) {
            $filter_fields[] = $item['content'] ?? null;
        }
        if ($this->getInput('target_title')) {
            $filter_fields[] = $item['title'] ?? null;
        }
        if ($this->getInput('target_uri')) {
            // todo: maybe consider 'http' and 'https' equivalent? Also maybe optionally .www subdomain?
            $filter_fields[] = $item['uri'] ?? null;
        }

        // Apply filter on item
        $keep_item = false;
        $length_limit = intval($this->getInput('length_limit'));
        foreach ($filter_fields as $field) {
            if ($length_limit > 0) {
                $field = substr($field, 0, $length_limit);
            }
            $result = preg_match($regex, $field);
            if ($result === false) {
                // todo: maybe notify user about the error here?
            }
            $keep_item |= boolval($result);
            if ($this->getInput('fix_encoding')) {
                $keep_item |= boolval(preg_match($regex, utf8_decode($field)));
                $keep_item |= boolval(preg_match($regex, utf8_encode($field)));
            }
        }

        // Reverse result? (keep everything but matching items)
        if ($this->getInput('filter_type') === 'block') {
            $keep_item = !$keep_item;
        }

        return $keep_item ? $item : null;
    }

    public function getURI()
    {
        $url = $this->getInput('url');

        if (empty($url)) {
            $url = parent::getURI();
        }

        return $url;
    }
}
