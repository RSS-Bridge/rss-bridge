<?php

class FeedExpanderExampleBridge extends FeedExpander
{
    const MAINTAINER = 'logmanoriginal';
    const NAME = 'FeedExpander Example';
    const URI = 'http://github.com/RSS-Bridge/rss-bridge/';
    const DESCRIPTION = 'Example bridge to test FeedExpander';

    const PARAMETERS = [
        'Feed' => [
            'version' => [
                'name' => 'Version',
                'type' => 'list',
                'title' => 'Select your feed format/version',
                'defaultValue' => 'RSS 2.0',
                'values' => [
                    'RSS 0.91' => 'rss_0_9_1',
                    'RSS 1.0' => 'rss_1_0',
                    'RSS 2.0' => 'rss_2_0',
                    'ATOM 1.0' => 'atom_1_0'
                ]
            ]
        ]
    ];

    public function collectData()
    {
        switch ($this->getInput('version')) {
            case 'rss_0_9_1':
                parent::collectExpandableDatas('http://static.userland.com/gems/backend/sampleRss.xml');
                break;
            case 'rss_1_0':
                parent::collectExpandableDatas('http://feeds.nature.com/nature/rss/current?format=xml');
                break;
            case 'rss_2_0':
                parent::collectExpandableDatas('http://feeds.rssboard.org/rssboard?format=xml');
                break;
            case 'atom_1_0':
                parent::collectExpandableDatas('http://segfault.linuxmint.com/feed/atom/');
                break;
            default:
                returnClientError('Unknown version ' . $this->getInput('version') . '!');
        }
    }
}
