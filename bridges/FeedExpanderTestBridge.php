<?php

declare(strict_types=1);

class FeedExpanderTestBridge extends FeedExpander
{
    const MAINTAINER = 'No maintainer';
    const NAME = 'Unnamed bridge';
    const URI = 'https://esdf.com/';
    const DESCRIPTION = 'No description provided';
    const PARAMETERS = [];
    const CACHE_TIMEOUT = 3600;

    public function collectData()
    {
        $url = 'http://static.userland.com/gems/backend/sampleRss.xml'; // rss 0.91
        $url = 'http://feeds.nature.com/nature/rss/current?format=xml'; // rss 1.0
        $url = 'https://dvikan.no/feed.xml'; // rss 2.0
        $url = 'https://nedlasting.geonorge.no/geonorge/Tjenestefeed.xml'; // atom

        $this->collectExpandableDatas($url);
    }
}
