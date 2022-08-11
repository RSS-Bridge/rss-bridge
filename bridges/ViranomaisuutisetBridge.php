<?php

class ViranomaisuutisetBridge extends BridgeAbstract
{
    const NAME = 'Viranomaisuutiset';
    const URI = 'https://viranomaisuutiset.fi/';
    const DESCRIPTION = 'Return latest news from viranomaisuutiset';
    const MAINTAINER = 'Miicat_47';

    const PARAMETERS = [
        'All' => [],
        'Tag' => [
            'tag' => [
                'name' => 'Tag',
                'type' => 'text',
                'required' => true,
                'title' => 'You can find tags from bottom of some articles. Click the tag and copy the tag from the url',
                'exampleValue' => 'vahingontorjuntatehtava'
            ],
        ],
        'Category' => [
            'category' => [
                'name' => 'Category',
                'type' => 'text',
                'required' => true,
        'title' => 'You can find this under the "Uutiset maakunnittain" -menu. Open the category and copy the category from the url',
                'exampleValue' => 'pohjois-pohjanmaa'
            ]
        ]
    ];

    public function collectData()
    {
        $url = 'https://viranomaisuutiset.fi/';

        if ($this->getInput('tag')) {
            $url .= 'tag/' . $this->getInput('tag');
        } elseif ($this->getInput('category')) {
            $url .= 'category/uutiset-maakunnittain/' . $this->getInput('category');
        }

        $html = getSimpleHTMLDOM($url);
        $html = defaultLinkTo($html, $this->getURI());

        foreach ($html->find('#tdi_67 .td_module_flex.td_module_flex_1.td_module_wrap.td-animation-stack') as $article) {
            $item = [];

            $item['uri'] = $article->find('.entry-title a', 0)->href;
            $item['title'] = $article->find('.entry-title', 0)->plaintext;
            $item['timestamp'] = strtotime($article->find('.td-post-date time', 0)->datetime);
            $item['content'] = sprintf('<img src="%s">', $article->find('.entry-thumb', 0)->getAttribute('data-img-url'));

            $this->items[] = $item;
        }
    }
}
