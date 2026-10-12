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
        $json = getContents('https://go.tmlmobilidade.pt/hub/api/v1/alerts');

        $data = Json::decode($json);

        foreach ($data['data'] as $entry) {
            $item = [];

            $item['uri'] = self::URI . '/alerts/' . $entry['_id'];
            $item['title'] = $entry['title'];
            $item['timestamp'] = (int) ($entry['active_period_start_date'] / 1000);
            $item['content'] = $entry['description'];

            $this->items[] = $item;
        }
    }
}
