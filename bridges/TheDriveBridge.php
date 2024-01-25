<?php

class TheDriveBridge extends FeedExpander
{
    const NAME = 'The Drive Bridge';
    const URI = 'https://www.thedrive.com/';
    const DESCRIPTION = 'Car news from thedrive.com';
    const MAINTAINER = 't0stiman';

    public function collectData()
    {
        $this->collectExpandableDatas('https://www.thedrive.com/feed', 20);
    }

    protected function parseItem($feedItem)
    {
        $item = parent::parseItem($feedItem);

        //remove warzone articles
        if (str_contains($item['uri'], 'the-war-zone')) {
            return null;
        }

        return $item;
    }
}
