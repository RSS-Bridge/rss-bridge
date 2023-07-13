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

    public function collectData()
    {
        $uri = self::URI . 'groups/' . $this->getInput('g') . '/rss';
        $this->collectExpandableDatas($uri, 10);
    }
}
