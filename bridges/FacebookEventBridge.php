<?php

class FacebookEventBridge extends BridgeAbstract
{
    const NAME = 'Facebook events';
    const URI = 'https://www.facebook.com/';
    const CACHE_TIMEOUT = 60 * 60 * 12; // 12h
    const DESCRIPTION = 'Fetches the events for a facebook page.';
    const MAINTAINER = 'dvikan';
    const PARAMETERS = [
        [
            'url' => [
                'name' => 'Url (or page name)',
                'type' => 'text',
                'defaultValue' => 'https://www.facebook.com/meta',
            ],
        ],
    ];

    public function collectData()
    {
        $url = trim($this->getInput('url'));
        if (preg_match('#https?://.*\.facebook\.com/([\w.]+)#i', $url, $m)) {
            $url = $m[1];
        }
        $url = sprintf('https://m.facebook.com/%s/events/', $url);
        $dom = getSimpleHTMLDOMCached($url);

        $d = $dom->find('#pages_msite_body_contents', 0);
        if (!$d) {
            throw new \Exception('Unable to find #pages_msite_body_contents');
        }
        $eventTitles = $d->find('h3');
        if (!$eventTitles) {
            throw new \Exception('Unable to find event titles');
        }
        foreach ($eventTitles as $eventTitle) {
            $eventDom = $eventTitle->parent();
            $eventUrl = $eventDom->find('a', 0);
            $eventUrl->innertext = 'Browse to event';

            $this->items[] = [
                'title' => $eventTitle->plaintext,
                'uri' => urljoin(self::URI, $eventUrl->href),
                'content' => defaultLinkTo($eventDom->innertext, self::URI),
            ];
        }
    }
}
