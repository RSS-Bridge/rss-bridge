<?php

class GovTrackBridge extends BridgeAbstract
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
                'Posts from Us' => 'posts'
                ]
            ],
            'limit' => self::LIMIT
    ]];

    public function collectData()
    {
        $html = getSimpleHTMLDOMCached($this->getURI());
        if ($this->getInput('feed') != 'posts') {
            $this->collectEvent($html);
            return;
        }

        $html = defaultLinkTo($html, parent::getURI());
        $limit = $this->getInput('limit') ?? 10;
        $count = 0;
        foreach ($html->find('section') as $element) {
            $item = [];

            $item['title'] = $element->find('a', 0)->innertext;
            $item['uri'] = $element->find('a', 0)->href;
            $item['content'] = $element->find('p', 1);

            $info = explode(' ', $element->find('p', 0)->innertext);
            $item['timestamp'] = strtotime(implode(' ', array_slice($info, 0, 3)));
            $item['categories'][] = implode(' ', array_slice($info, 4));

            $html = getSimpleHTMLDOMCached($item['uri']);
            $html = defaultLinkTo($html, parent::getURI());

            $content = $html->find('#content .col-md', 1);
            $info = explode(' by ', $content->find('p', 0)->plaintext);
            $content->removeChild($content->firstChild());

            $item['author'] = implode(' ', array_slice($info, 1));
            $item['content'] = $content->innertext;

            if ($count++ == $limit) {
                break;
            }

            $this->items[] = $item;
        }
    }

    private function collectEvent($html)
    {
        $opt = [];
        preg_match('/"csrfmiddlewaretoken" value="(.*)"/', $html, $opt);
        $header = [
            "cookie: csrftoken=$opt[1]",
            "x-csrftoken: $opt[1]",
            'referer: ' . parent::getURI(),
        ];
        preg_match('/var selected_feed = "(.*)";/', $html, $opt);
        $post = [
            'count' => $this->getInput('limit') ?? 20,
            'feed' => $opt[1]
        ];
        $opt = [ CURLOPT_POSTFIELDS => $post ];

        $html = getContents(parent::getURI() . 'events/_load_events', $header, $opt);
        $html = defaultLinkTo(str_get_html($html), parent::getURI());

        foreach ($html->find('.tracked_event') as $event) {
            $item = [];

            $bill = $event->find('.event_title a, .event_body a', 0);
            preg_match('/Sponsor:(.*)\n/', $event->plaintext, $opt);
            $date = explode(' ', $event->find('.event_date', 0)->plaintext);

            $item['author'] = $opt[1] ?? '';
            $item['content'] = $event->find('td', 1)->innertext;
            $item['enclosures'][] = $event->find('img', 0)->src;
            $item['timestamp'] = strtotime(implode(' ', array_slice($date, 2)));
            $item['title'] = explode(': ', $bill->innertext)[0];
            $item['uri'] = $bill->href;

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
        if ($this->getInput('feed') != 'posts') {
            $url = parent::getURI() . 'events/' . $this->getInput('feed');
        } else {
            $url = parent::getURI() . $this->getInput('feed');
        }
        return $url;
    }
}
