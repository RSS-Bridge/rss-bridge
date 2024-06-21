<?php

class HumbleBundleBridge extends BridgeAbstract
{
    const NAME = 'Humble Bundle';
    const MAINTAINER = 'phantop';
    const URI = 'https://humblebundle.com/';
    const DESCRIPTION = 'Returns bundles from Humble Bundle.';
    const PARAMETERS = [[
        'type' => [
            'name' => 'Bundle type',
            'type' => 'list',
            'defaultValue' => 'bundles',
            'values' => [
                'All' => 'bundles',
                'Books' => 'books',
                'Games' => 'games',
                'Software' => 'software',
                ]
            ]
    ]];

    public function collectData()
    {
        $page = getSimpleHTMLDOMCached($this->getURI());
        $json_text = $page->find('#landingPage-json-data', 0)->innertext;
        $json = json_decode(html_entity_decode($json_text), true)['data'];

        $products = [];
        if ($this->getInput('type') === 'bundles') {
            $types = ['books', 'games', 'software'];
            foreach ($types as $type) {
                $products = array_merge($products, $json[$type]['mosaic'][0]['products']);
            }
        } else {
            $products = $json[$this->getInput('type')]['mosaic'][0]['products'];
        }

        foreach ($products as $element) {
            $item = [];
            $item['author'] = $element['author'];
            $item['timestamp'] = $element['start_date|datetime'];
            $item['title'] = $element['tile_short_name'];
            $item['uid'] = $element['machine_name'];
            $item['uri'] = parent::getURI() . $element['product_url'];

            $item['content'] = $element['marketing_blurb'];
            $item['content'] .= '<br>' . $element['detailed_marketing_blurb'];

            $type = explode(':', $element['tile_name'])[0];
            $item['categories'] = $element['hover_highlights'];
            array_unshift($item['categories'], $type);
            array_unshift($item['categories'], $element['tile_stamp']);

            $logo = $element['tile_logo'];
            $tile = $element['high_res_tile_image'];
            $item['enclosures'] = [$logo, $tile];
            $this->items[] = $item;
        }
    }

    public function getName()
    {
        $name = parent::getName();
        $name .= $this->getInput('type') ? ' - ' . $this->getInput('type') : '';
        return $name;
    }

    public function getURI()
    {
        $uri = parent::getURI() . $this->getInput('type');
        return $uri;
    }
}
