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
        $types = ['books', 'games', 'software'];
        $types = $this->getInput('type') === 'bundles' ? $types : [$this->getInput('type')];
        foreach ($types as $type) {
            $products = array_merge($products, $json[$type]['mosaic'][0]['products']);
        }

        foreach ($products as $element) {
            $dom = new simple_html_dom();
            $body = $dom->createElement('div');
            $item = [
                'author' => $element['author'],
                'categories' => $element['hover_highlights'],
                'content' => $body,
                'timestamp' => $element['start_date|datetime'],
                'title' => $element['tile_short_name'],
                'uid' => $element['machine_name'],
                'uri' => parent::getURI() . $element['product_url'],
            ];

            array_unshift($item['categories'], explode(':', $element['tile_name'])[0]);
            array_unshift($item['categories'], $element['tile_stamp']);

            $this->createChild($dom, $body, 'img', null, ['src' => $element['tile_logo']]);
            $this->createChild($dom, $body, 'img', null, ['src' => $element['high_res_tile_image']]);
            $this->createChild($dom, $body, 'h2', $element['short_marketing_blurb']);
            $this->createChild($dom, $body, 'p', $element['detailed_marketing_blurb']);

            $this->items[] = $this->processBundle($item, $dom, $body);
        }
    }

    private function createChild($dom, $body, $name = null, $val = null, $args = [])
    {
        if ($name == null) {
            $elem = $dom->createTextNode($val);
        } else {
            $elem = $dom->createElement($name, $val);
        }
        foreach ($args as $arg => $val) {
            $elem->setAttribute($arg, $val);
        }
        $body->appendChild($elem);
        return $elem;
    }

    private function processBundle($item, $dom, $body)
    {
        $page = getSimpleHTMLDOMCached($item['uri']);
        $json_text = $page->find('#webpack-bundle-page-data', 0)->innertext;
        $json = json_decode(html_entity_decode($json_text), true)['bundleData'];
        $tiers = $json['tier_display_data'];
        ksort($tiers, SORT_NATURAL);
        # `initial` element gets sorted to the end as bt# (bundle tiers) precede it alphabetically
        array_unshift($tiers, array_pop($tiers));

        $seen = [];
        $toc = $this->createChild($dom, $body, 'ul');
        foreach ($tiers as $tiername => $tier) {
            $this->createChild($dom, $body, 'h2', $tier['header'], ['id' => $tiername]);
            $li = $this->createChild($dom, $toc, 'li');
            $this->createChild($dom, $li, 'a', $tier['header'], ['href' => "#$tiername"]);
            $toc_tier = $this->createChild($dom, $toc, 'ul');
            foreach ($tier['tier_item_machine_names'] as $name) {
                if (in_array($name, $seen)) {
                    continue;
                }
                array_push($seen, $name);

                $element = $json['tier_item_data'][$name];
                $head = $this->createChild($dom, $body, 'h3', null, ['id' => $name]);
                $head_link = $this->createChild($dom, $head, 'a', $element['human_name'], ['id' => $name]);
                $li = $this->createChild($dom, $toc_tier, 'li');
                $this->createChild($dom, $li, 'a', $element['human_name'], ['href' => "#$name"]);
                $this->createChild($dom, $body, 'img', null, ['src' => $element['resolved_paths']['featured_image']]);
                $this->createChild($dom, $body, 'img', null, ['src' => $element['resolved_paths']['preview_image']]);
                $this->createChild($dom, $body, 'br');
                if ($element['description_text']) {
                    $body->appendChild(str_get_html($element['description_text'])->root);
                }
                if ($element['youtube_link']) {
                    $head_link->href = 'https://youtu.be/' . $element['youtube_link'];
                }
                if ($element['book_preview']) {
                    $head_link->href = $element['book_preview']['preview_file_link'];
                }
            }
        }

        return $item;
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
