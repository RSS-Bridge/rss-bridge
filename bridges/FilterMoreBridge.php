<?php

class FilterMoreBridge extends FeedExpander
{
    const MAINTAINER = 'boyska';
    const NAME = 'FilterMore';
    const CACHE_TIMEOUT = 2;
    const DESCRIPTION = 'Filters a feed of your choice';
    const URI = 'https://git.lattuta.net/boyska/rss-bridge';

    const PARAMETERS = [
        [
        'url' => [
            'name' => 'Feed URL',
            'required' => true,
            'exampleValue' => 'https://example.com/feed/',
        ],
        'conj_type' => [
            'name' => 'Conjunction type type',
            'type' => 'list',
            'required' => false,
            'values' => [
                'All conditions must be met' => 'and',
                'Any condition must be met' => 'or',
            ],
            'defaultValue' => 'permit',
        ],

        'title_re' => [
            'name' => 'Filter item title (regular expression, see php.net/pcre_match for details)',
            'required' => false,
            'exampleValue' => '/breaking\ news/i',
        ],
        'body_re' => [
            'name' => 'Filter body (regular expression)',
            'required' => false,
            'exampleValue' => '/(technology|politics)/i',
        ],
        'author_re' => [
            'name' => 'Filter author (regular expression)',
            'required' => false,
            'exampleValue' => '/(technology|politics)/i',
        ],
        'newer_than' => [
            'name' => 'Filter date: ok if newer than the value (see php.net/strtotime for details)',
            'required' => false,
            'exampleValue' => '-14 days',
        ],
        'older_than' => [
            'name' => 'Filter date: ok if older than the value (see php.net/strtotime for details)',
            'required' => false,
            'exampleValue' => '-1 hour',
        ],

        'has_media' => [
            'name' => 'Has at least 1 media inside',
            'type' => 'checkbox',
            'required' => false,
            'defaultValue' => false,
        ],

        'invert_filter' => [
            'name' => 'Invert filter result',
            'type' => 'checkbox',
            'required' => false,
            'defaultValue' => false,
        ],

        'sort_by' => [
            'name' => 'Sort by',
            'type' => 'list',
            'required' => true,
            'values' => [
                "Don't sort" => 'none',
                'Date' => 'timestamp',
                'Title' => 'title',
                'Random' => 'random',
            ],
            'defaultValue' => 'timestamp',
        ],
        'sort_dir' => [
            'name' => 'Sort direction',
            'type' => 'list',
            'required' => true,
            'values' => [
                'Ascending' => 'asc',
                'Descending' => 'desc',
            ],
            'defaultValue' => 'asc',
        ],
        'limit' => self::LIMIT,

        ]];

    public function collectData()
    {
        if ($this->getInput('url') && substr($this->getInput('url'), 0, strlen('http')) !== 'http') {
            // just in case someone find a way to access local files by playing with the url
            returnClientError('The url parameter must either refer to http or https protocol.');
        }
        try {
            $this->collectExpandableDatas($this->getURI());
        } catch (HttpException $e) {
            $this->collectExpandableDatas($this->getURI());
        }
    }

    protected function parseItem($item)
    {
        $item['enclosures'] = [];
        if (isset($newItem->enclosure)) {
            foreach ($newItem->enclosure as $encl) {
                $serialized = [];
                foreach ($encl->attributes() as $key => $value) {
                    $serialized[$key] = (string)$value;
                }
                $serialized['length'] = intval($serialized['length']);
                $item['enclosures'][] = $serialized;
            }
        }
        if (isset($newItem->link)) {
            foreach ($newItem->link as $el) {
                if (((string)$el['rel']) !== 'enclosure') {
                    continue;
                }
                $serialized = [];
                $serialized['url'] = (string)$el['href'];

                $item['enclosures'][] = $serialized;
            }
        }

        $filters = ['filterByTitle', 'filterByBody', 'filterByAuthor', 'filterByDateNewer', 'filterByDateOlder', 'filterByMedia'];
        $results = [];

        foreach ($filters as $filter) {
            $filter_res = $this->$filter($item);
            if ($filter_res === null) {
                continue;
            }
            $results[] = $filter_res;
        }

        $old_enclosures = $item['enclosures'];
        $item['enclosures'] = [];
        foreach ($old_enclosures as $e) {
            $item['enclosures'][] = $e['url'];
        }
        if (count($results) === 0) {
            return $item;
        }
        if ($this->getConjType() === 'and') {
            $result = !in_array(false, $results);
        } else { // or
            $result = in_array(true, $results);
        }
        if ($this->getInvertResult()) {
            $result = !$result;
        }
        if ($result) {
            return $item;
        } else {
            return null;
        }
    }

    public function collectExpandableDatas($url, $maxItems = -1)
    {
        parent::collectExpandableDatas($url, $maxItems);
        $limit = (int)($this->getInput('limit') ?: 10);
        if ($this->getInput('sort_by') === 'random') {
            shuffle($this->items);
        } elseif ($this->getInput('sort_by') !== 'none') {
            usort($this->items, function ($itemA, $itemB) {
                $valA = $this->sortItemKey($itemA);
                $valB = $this->sortItemKey($itemB);
                $cmp = strcmp($valA, $valB);
                return $cmp;
            });
        }
        if ($this->getInput('sort_dir') === 'desc') {
            $this->items = array_reverse($this->items);
        }
        if ($limit > 0) {
            $this->items = array_slice($this->items, 0, $limit);
        }
    }

    protected function sortItemKey($item)
    {
        $sort_by = $this->getInput('sort_by');
        $key = $item[$sort_by];
        return $key;
    }


    private function cmp($a, $b)
    {
        if ($a > $b) {
            return 1;
        } elseif ($a < $b) {
            return -1;
        }
        return 0;
    }
    private function filterByFieldRegexp($field, $re)
    {
        if ($re === '' || $re === null) {
            return null;
        } elseif (preg_match($re, $field)) {
            return true;
        }
        return false;
    }
    protected function filterByTitle($item)
    {
        $re = $this->getInput('title_re');
        return $this->filterByFieldRegexp($item['title'], $re);
    }
    protected function filterByBody($item)
    {
        $re = $this->getInput('body_re');
        return $this->filterByFieldRegexp($item['content'], $re);
    }
    protected function filterByAuthor($item)
    {
        $re = $this->getInput('author_re');
        return $this->filterByFieldRegexp($item['author'], $re);
    }
    private function filterByDate($item, $input, $expected)
    {
        $val = $this->getInput($input);
        if ($val === '' || $val === null) {
            return null;
        }
        $ts = strtotime($val);
        if ($ts === false) {
            throw new Exception('Invalid time specification: ' . $val);
        }
        $cmp = $this->cmp($item['timestamp'], $ts); // 1 if newer, -1 if older
        return $cmp === $expected;
    }
    protected function filterByDateNewer($item)
    {
        return $this->filterByDate($item, 'newer_than', 1);
    }
    protected function filterByDateOlder($item)
    {
        return $this->filterByDate($item, 'older_than', -1);
    }
    protected function filterByMedia($item)
    {
        if (!$this->getInput('has_media')) {
            return null;
        } elseif (count($item['enclosures']) > 0) {
            return true;
        }
        return false;
    }

    protected function getConjType()
    {
        return $this->getInput('conj_type');
    }
    protected function getInvertResult()
    {
        return $this->getInput('invert_filter');
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

