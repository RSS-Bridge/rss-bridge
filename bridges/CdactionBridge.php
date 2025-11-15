<?php

class CdactionBridge extends BridgeAbstract {
    const NAME = 'CD-ACTION bridge';
    const URI = 'https://cdaction.pl';
    const DESCRIPTION = 'Fetches the latest posts from given category.';
    const MAINTAINER = 'tomaszkane';
    const PARAMETERS = [[
        'category' => [
            'name' => 'Kategoria',
            'type' => 'list',
            'values' => [
                'Najnowsze (wszystkie)' => 'feed',
                'Newsy' => 'newsy',
                'Recenzje' => 'recenzje',
                'Teksty' => 'teksty',
                'Kultura' => 'kultura',
                'Retro' => 'retro',
                'Technologie' => 'technologie',
                'Na luzie' => 'na-luzie',
            ],
        ]]
    ];

    public function collectData() {
        $feedUrl = $this->getURI() . '/' . $this->getInput('category');

        if ($this->getInput('category') === 'feed') {
            $xml = simplexml_load_file($feedUrl);
            $namespaces = $xml->getNamespaces(true);
            foreach ($xml->channel->item as $child) {
                $item = [];
                $item['uri'] = (string) $child->link;
                $item['title'] = (string) $child->title;
                $item['timestamp'] = (string) $child->pubDate;
                $item['content'] = (string) $child->description;
                $item['author'] = (string) $child->children($namespaces['dc'])->creator;
                foreach ($child->category as $cat) {
                    $item['categories'] = (string) $cat;
                }
                $this->items[] = $item;
            }
            return;
        }

        $dom = getSimpleHTMLDOM($feedUrl);
        /** @var simple_html_dom_node[] $nodes */
        $nodes = $dom->find('a.article-link');
        foreach ($nodes as $node) {
            $item = [];
            $item['uri'] = $node->attr['href'];
            $item['title'] = trim($node->find('h3', 0)?->plaintext);
            $item['timestamp'] = trim($node->find('.meta .date', 0)->plaintext) ? : null;
            $item['author'] = trim($node->find('.author-name', 0)?->plaintext ?? '', " \n\r\t\v\0\"") ? : null;
            $item['enclosures'][] = $node->find('.image img', 0)?->attr['src'] ?: null;
            if ($category = trim($node->find('.category')?->plaintext)) {
                $item['categories'][] = $category;
            }
            $this->items[] = $item;
        }
    }
}
