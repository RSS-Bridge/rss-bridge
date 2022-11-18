<?php

/**
 * Get the bitcoin price daily
 */
class CryptoCurrencyBridge extends SimpleBridge
{
    public function __invoke(): array
    {
        $data = Json::decode(getContents('https://api.coindesk.com/v1/bpi/currentprice.json'));
        $btc_price = $data['bpi']['USD']['rate'];

        return [
            'name' => 'Daily Bitcoin price',
            'uri' => 'https://www.coindesk.com/',
            'items' => [
                [
                    'title' => sprintf('Daily Bitcoin price: %s USD', $btc_price),
                    'content' => sprintf('Details: <pre>%s</pre>', Json::encode($data)),
                    // Unique daily guid
                    'uid' => 'rssbridge_crypto_' . ((int)(time() / 86400)),
                ],
            ]
        ];
    }
}
