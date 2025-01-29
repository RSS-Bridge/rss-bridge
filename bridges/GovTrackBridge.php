<?php

class GovTrackBridge extends FeedExpander
{
    const NAME = 'GovTrack';
    const MAINTAINER = 'phantop';
    const URI = 'https://www.govtrack.us/';
    const DESCRIPTION = 'Returns posts and bills from GovTrack.us';
    const PARAMETERS = [[
        'feed' => [
            'name' => 'Feed to track',
            'type' => 'list',
            'defaultValue' => 'posts',
            'values' => [
                'All Legislative Activity' => 'bill-activity',
                'Bill Summaries' => 'bill-summaries',
                'Legislation Coming Up' => 'coming-up',
                'Major Legislative Activity' => 'major-bill-activity',
                'New Bills and Resolutions' => 'introduced-bills',
                'New Laws' => 'enacted-bills',
                'News from Us' => 'posts'
            ]
        ],
        'limit' => self::LIMIT
    ]];

    public function collectData()
    {
        $limit = $this->getInput('limit') ?? 15;
        if ($this->getInput('feed') == 'posts') {
            $this->collectExpandableDatas($this->getURI() . '.rss', $limit);
        } else {
            $this->collectEvent($this->getURI(), $limit);
        }
    }

    protected function parseItem(array $item)
    {
        $html = getSimpleHTMLDOMCached($item['uri']);
        $html = defaultLinkTo($html, parent::getURI());

        $item['categories'] = [$html->find('.breadcrumb-item', 1)->plaintext];
        $content = $html->find('#content .col-md', 1);
        $item['author'] = explode(' by ', $content->firstChild()->plaintext)[1];
        $content->removeChild($content->firstChild());
        $item['content'] = $content->innertext;

        return $item;
    }

    private function collectEvent($uri, $limit)
    {
        $html = getSimpleHTMLDOMCached($uri);
        preg_match('/"csrfmiddlewaretoken" value="(.*)"/', $html, $preg);
        $header = [
            "cookie: csrftoken=$preg[1]",
            "x-csrftoken: $preg[1]",
            'referer: ' . parent::getURI(),
        ];
        preg_match('/var selected_feed = "(.*)";/', $html, $preg);
        $opt = [ CURLOPT_POSTFIELDS => [
            'count' => $limit,
            'feed' => $preg[1]
        ]];

        $html = getContents(parent::getURI() . 'events/_load_events', $header, $opt);
        $html = defaultLinkTo(str_get_html($html), parent::getURI());

        foreach ($html->find('.tracked_event') as $event) {
            $bill = $event->find('.event_title a, .event_body a', 0);
            $date = explode(' ', $event->find('.event_date', 0)->plaintext);
            preg_match('/Sponsor:(.*)\n/', $event->plaintext, $preg);

            $item = [
                'author' => $preg[1] ?? '',
                'content' => $event->find('td', 1)->innertext,
                'enclosures' => [$event->find('img', 0)->src],
                'timestamp' => strtotime(implode(' ', array_slice($date, 2))),
                'title' => explode(': ', $bill->innertext)[0],
                'uri' => $bill->href,
            ];

            foreach ($event->find('.event_title, .event_type span') as $tag) {
                if (!$tag->find('a', 0)) {
                    $item['categories'][] = $tag->plaintext;
                }
            }

            $this->items[] = $item;
        }
    }

    public function getName()
    {
        $name = parent::getName();
        if ($this->getInput('feed') != null) {
            $name .= ' - ' . $this->getKey('feed');
        }
        return $name;
    }

    public function getURI()
    {
        if ($this->getInput('feed') == 'posts') {
            $url = parent::getURI() . $this->getInput('feed');
        } else {
            $url = parent::getURI() . 'events/' . $this->getInput('feed');
        }
        return $url;
    }
}
