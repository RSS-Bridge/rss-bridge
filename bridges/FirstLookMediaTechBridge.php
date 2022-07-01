<?php

class FirstLookMediaTechBridge extends BridgeAbstract
{
    const NAME = 'First Look Media - Technology';
    const URI = 'https://tech.firstlook.media';
    const DESCRIPTION = 'First Look Media Technology page';
    const MAINTAINER = 'somini';
    const PARAMETERS = [
        [
            'projects' => [
                'type' => 'checkbox',
                'name' => 'Include Projects?',
            ]
        ]
    ];

    public function collectData()
    {
        $html = getSimpleHTMLDOM(self::URI);

        if ($this->getInput('projects')) {
            $top_projects = $html->find('.PromoList-ul', 0);
            foreach ($top_projects->find('li.PromoList-item') as $element) {
                $item = [];

                $item_uri = $element->find('a', 0);
                $item['uri'] = $item_uri->href;
                $item['title'] = strip_tags($item_uri->innertext);
                $item['content'] = $element->find('div > div', 0);

                $this->items[] = $item;
            }
        }

        $top_articles = $html->find('.PromoList-ul', 1);
        foreach ($top_articles->find('li.PromoList-item') as $element) {
            $item = [];

            $item_left = $element->find('div > div', 0);
            $item_date = $element->find('.PromoList-date', 0);
            $item['timestamp'] = strtotime($item_date->innertext);
            $item_date->outertext = '';  /* Remove */
            $item['author'] = $item_left->innertext;
            $item_uri = $element->find('a', 0);
            $item['uri'] = self::URI . $item_uri->href;
            $item['title'] = strip_tags($item_uri);

            $this->items[] = $item;
        }
    }
}
