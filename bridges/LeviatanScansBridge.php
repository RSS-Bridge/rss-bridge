<?php

class LeviatanScansBridge extends BridgeAbstract {

    const MAINTAINER = 'tgkenney';
    const NAME = 'Leviatan Scans';
    const URI = 'https://leviatanscans.com';
    const DESCRIPTION = 'Gets the latest chapters from the Leviatan Scans website';

    const PARAMETERS = array(
        'Options' => array(
            'comic' => array(
                'type' => 'text',
                'name' => 'Comic ID (e.g. 68254-legend-of-the-northern-blade)',
                'title' => 'This is everything after /comics/ of the URL',
            )
        )
    );

    public function collectData()
    {
        $uri = self::URI
            . '/comics/'
            . $this->getInput('comic');

        $html = getSimpleHTMLDOM($uri) or returnServerError('Could not contact Leviatan Scans');

        $chapters = $html->find('div[class=list list-row row]');

        foreach ($chapters->find('div[class=list-item') as $chapter) {
            $item = array();

            $item['uri'] = $chapter->find('a')->href;
            $item['title'] = $html->find('h5[text-highlight]') . ' ' . $chapter->find('a')->plaintext;
            $item['timestamp'] = date_create();
            $item['author'] = self::NAME;
            $item['content'] = getSimpleHTMLDOM($item['uri']);

            $this->items[] = $item;
        }
    }
}