<?php

class SteamGroupAnnouncementsBridge extends FeedExpander
{
    const MAINTAINER = 'Jisagi';
    const NAME = 'Steam Group Announcements';
    const URI = 'https://steamcommunity.com/';
    const DESCRIPTION = 'Returns latest announcements from a steam group.';
    const PARAMETERS = [
        [
            'g' => [
                'name' => 'Group name',
                'exampleValue' => 'freegamesfinders',
                'required' => true
            ]
        ]
    ];

    public function getURI()
    {
        return self::URI . 'groups/' . $this->getInput('g') . '/rss';
    }

    public function collectData()
    {
        $this->collectExpandableDatas($this->getURI(), 10);
    }

    public function parseItem($newsItem)
    {
        return parent::parseItem($newsItem);
    }
}
