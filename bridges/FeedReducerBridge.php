<?php

class FeedReducerBridge extends FeedExpander
{
    const MAINTAINER = 'mdemoss';
    const NAME = 'Feed Reducer';
    const URI = 'http://github.com/RSS-Bridge/rss-bridge/';
    const DESCRIPTION = 'Choose a percentage of a feed you want to see.';
    const PARAMETERS = [ [
        'url' => [
            'name' => 'Feed URI',
            'exampleValue' => 'https://lorem-rss.herokuapp.com/feed?length=42',
            'required' => true
        ],
        'percentage' => [
            'name' => 'percentage',
            'type' => 'number',
            'exampleValue' => 50,
            'required' => true
        ]
    ]];
    const CACHE_TIMEOUT = 3600;

    public function collectData()
    {
        $url = $this->getInput('url');
        if (preg_match('#^http(s?)://#i', $url)) {
            $this->collectExpandableDatas($url);
        } else {
            throw new Exception('URI must begin with http(s)://');
        }
    }

    public function getItems()
    {
        $filteredItems = [];
        $intPercentage = (int)preg_replace('/[^0-9]/', '', $this->getInput('percentage'));

        foreach ($this->items as $item) {
            // The URL is included in the hash:
            //  - so you can change the output by adding a local-part to the URL
            //  - so items with the same URI in different feeds won't be correlated

            // $pseudoRandomInteger will be a 16 bit unsigned int mod 100.
            // This won't be uniformly distributed 1-100, but should be close enough.

            $data = $item['uri'] . '::' . $this->getInput('url');
            $hash = hash('sha256', $data, true);
            // S = unsigned 16-bit int
            $pseudoRandomInteger = unpack('S', $hash)[1] % 100;

            if ($pseudoRandomInteger < $intPercentage) {
                $filteredItems[] = $item;
            }
        }

        return $filteredItems;
    }

    public function getName()
    {
        $trimmedPercentage = preg_replace('/[^0-9]/', '', $this->getInput('percentage') ?? '');
        return parent::getName() . ' [' . $trimmedPercentage . '%]';
    }
}
