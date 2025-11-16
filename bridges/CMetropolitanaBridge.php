<?php

declare(strict_types=1);

class CMetropolitanaBridge extends BridgeAbstract
{
    const NAME = 'CMetropolitana';
    const URI = 'https://carrismetropolitana.pt';
    const DESCRIPTION = 'CMetropolitana | Alertas';
    const MAINTAINER = 'FJSFerreira';

    public function collectData()
    {
        $json = getContents('https://api.carrismetropolitana.pt/v2/alerts');

        $data = Json::decode($json);

        foreach ($data as $entry) {
            $item = [];

            $item['uri'] = self::URI . '/alerts/' . $entry['alert_id'];
            $item['title'] = $entry['header_text']['translation'][0]['text'];
            $item['timestamp'] = $entry['active_period'][0]['start'];
            $item['content'] = $entry['description_text']['translation'][0]['text'];

            $this->items[] = $item;
        }
    }
}
