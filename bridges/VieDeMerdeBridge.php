<?php

class VieDeMerdeBridge extends BridgeAbstract
{
    const MAINTAINER = 'floviolleau';
    const NAME = 'VieDeMerde Bridge';
    const URI = 'https://www.viedemerde.fr';
    const DESCRIPTION = 'Returns latest quotes from VieDeMerde.';
    const CACHE_TIMEOUT = 7200;

    const PARAMETERS = [[
            'item_limit' => [
            'name' => 'Limit number of returned items',
            'type' => 'number',
            'defaultValue' => 20
            ]
    ]];

    public function collectData()
    {
        $limit = $this->getInput('item_limit');

        if ($limit < 1) {
            $limit = 20;
        }

        $html = getSimpleHTMLDOM(self::URI, []);
        $quotes = $html->find('article.bg-white');
        if (count($quotes) === 0) {
            return;
        }

        foreach ($quotes as $quote) {
            $item = [];
            $item['uri'] = self::URI . $quote->find('a', 0)->href;
            $titleContent = $quote->find('h2', 0);

            if ($titleContent) {
                $item['title'] = html_entity_decode($titleContent->plaintext, ENT_QUOTES);
            } else {
                continue;
            }

            $quoteText = $quote->find('a', 1)->plaintext;
            $isAVDM = $quote->find('.vote-btn', 0)->plaintext;
            $isNotAVDM = $quote->find('.vote-btn', 1)->plaintext;
            $item['content'] = $quoteText . '<br>' . $isAVDM . '<br>' . $isNotAVDM;
            $item['author'] = $quote->find('p', 0)->plaintext;
            $item['uid'] = hash('sha256', $item['title']);

            $this->items[] = $item;

            if (count($this->items) >= $limit) {
                break;
            }
        }
    }
}
