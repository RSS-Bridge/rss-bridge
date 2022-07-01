<?php

class IdenticaBridge extends BridgeAbstract
{
    const MAINTAINER = 'mitsukarenai';
    const NAME = 'Identica Bridge';
    const URI = 'https://identi.ca/';
    const CACHE_TIMEOUT = 300; // 5min
    const DESCRIPTION = 'Returns user timelines';

    const PARAMETERS = [ [
        'u' => [
            'name' => 'username',
            'exampleValue' => 'jxself',
            'required' => true
        ]
    ]];

    public function collectData()
    {
        $html = getSimpleHTMLDOM($this->getURI());

        foreach ($html->find('li.major') as $dent) {
            $item = [];

            // get dent link
            $item['uri'] = html_entity_decode($dent->find('a', 0)->href);

            // extract dent timestamp
            $item['timestamp'] = strtotime($dent->find('abbr.easydate', 0)->plaintext);

            // extract dent text
            $item['content'] = trim($dent->find('div.activity-content', 0)->innertext);
            $item['title'] = $this->getInput('u') . ' | ' . $item['content'];
            $this->items[] = $item;
        }
    }

    public function getName()
    {
        if (!is_null($this->getInput('u'))) {
            return $this->getInput('u') . ' - Identica Bridge';
        }

        return parent::getName();
    }

    public function getURI()
    {
        if (!is_null($this->getInput('u'))) {
            return self::URI . urlencode($this->getInput('u'));
        }

        return parent::getURI();
    }
}
