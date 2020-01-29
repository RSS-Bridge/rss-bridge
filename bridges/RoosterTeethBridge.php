<?php
class RoosterTeethBridge extends BridgeAbstract {

    const MAINTAINER = 'tgkenney';
    const NAME = 'Rooster Teeth';
    const URI = 'https://roosterteeth.com';
    const DESCRIPTION = 'Gets the latest channel videos from the Rooster Teeth website';
    const CACHE_TIMEOUT = 3600; // 1h

    const PARAMETERS = array(
        'channel' => array(
            'type' => 'list',
            'name' => 'Channel',
            'values' => array(
                'Achievement Hunter' => 'achievement-hunter',
                'Cow Chop' => 'cow-chop',
                'Death Battle' => 'death-battle',
                'Funhaus' => 'funhaus',
                'Inside Gaming' => 'inside-gaming',
                'JT Music' => 'jt-music',
                'Kinda Funny' => 'kinda-funny',
                'Rooster Teeth' => 'rooster-teeth',
                'Sugar Pine 7' => 'sugar-pine-7'
            ),
            'required' => true
        ),
        'sort' => array(
            'sort' => array(
                'type' => 'list',
                'name' => 'Sort',
                'values' => array(
                    'Newest - Oldest' => 'desc',
                    'Oldest - Newest' => 'asc'
                )
            )
        )
    );

    public function collectData()
    {
        $html = getSimpleHTMLDOM(self::URI . '/episodes?channel_id=' . $this->getInput('channel') . '&order=' . $this->getInput('sort'))
            or returnServerError('Could not contact Rooster Teeth: ' . $this->getURI());

        foreach($html->find('div.episode-card') as $element) {
            $item = array();
            $item['id'] = $element->find('a[class=episode-title', 0);
            $item['uri'] = self::URI .  $element->find('a[class=episode-title', 3);
            $item['title'] = $element->find('a[class=episode-title', 0);
            $item['timestamp'] = $element->find('a[class=episode-title', 0);
            $item['content'] = $element->find('a[class=episode-title', 0);

            $this->items[] = $item;
        }
    }
}