<?php

class StripeAPIChangeLogBridge extends BridgeAbstract
{
    const MAINTAINER = 'Pierre Mazière';
    const NAME = 'Stripe API Changelog';
    const URI = 'https://stripe.com/docs/upgrades';
    const CACHE_TIMEOUT = 86400; // 24h
    const DESCRIPTION = 'Returns the changes made to the stripe.com API';

    public function collectData()
    {
        $html = getSimpleHTMLDOM(self::URI);

        foreach ($html->find('h3') as $change) {
            $item = [];
            $item['title'] = trim($change->plaintext);
            $item['uri'] = self::URI . '#' . $item['title'];
            $item['author'] = 'stripe';
            $item['content'] = $change->nextSibling()->outertext;
            $item['timestamp'] = strtotime($item['title']);
            $this->items[] = $item;
        }
    }
}
