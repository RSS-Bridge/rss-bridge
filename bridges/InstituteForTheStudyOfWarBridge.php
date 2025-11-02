<?php

class InstituteForTheStudyOfWarBridge extends BridgeAbstract
{
    const MAINTAINER = 'sqrtminusone';
    const NAME = 'Institute for the Study of War';
    const URI = 'https://www.understandingwar.org';

    const CACHE_TIMEOUT = 3600; // 1 hour
    const DESCRIPTION = 'Recent publications of the ISW.';

    const PARAMETERS = [
        '' => [
            'searchURL' => [
                'name' => 'Filter URL',
                'required' => false,
                'title' => 'Set a filter on https://www.understandingwar.org/research and copy the URL parameters.'
            ],
            'limit' => [
                'name' => 'Limit',
                'type' => 'number',
                'required' => true,
                'defaultValue' => 5
            ]
        ]
    ];

    public function collectData()
    {
        $filter = $this->getInput('searchURL');
        $limit = $this->getInput('limit');
        $html = getSimpleHTMLDOM(self::URI . '/research/?' . $filter);
        // Yes, a typo
        $container = $html->find('div[data-name="reaserach_library"]', 0);
        $entries = $container->find('.research-card-loop-item-3colgrid');

        for ($i = 0; $i < min(count($entries), $limit); $i++) {
            $entry = $entries[$i];
            $this->items[] = $this->processEntry($entry);
        }
    }

    private function processEntry($entry)
    {
        $h3 = $entry->find('h3.research-card-title', 0);
        $title = $h3->plaintext;
        $uri = $h3->find('a', 0)->href;

        $date_p = $entry->find('p.research-card-post-date', 0);
        $date = DateTime::createFromFormat('F d, Y', trim($date_p->plaintext));

        $tags = array_map(
            fn($tag) => html_entity_decode($tag->plaintext, ENT_QUOTES | ENT_HTML5, 'UTF-8'),
            $entry->find('p.tag-cloud-on-cards')
        );

        $html = getSimpleHTMLDOMCached($uri, 60 * 60 * 24 * 14);
        $content = $html->find('div.dynamic-entry-content', 0);

        $scripts = $content->find('script');
        foreach ($scripts as $script) {
            $script->parent->removeChild($script);
        }

        $item = [
            'uri' => $uri,
            'title' => $title,
            'uid' => $uri,
            'timestamp' => $date->getTimestamp(),
            'categories' => $tags,
            'content' => $content->innertext
        ];

        return $item;
    }
}
