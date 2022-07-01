<?php

class PcGamerBridge extends BridgeAbstract
{
    const NAME = 'PC Gamer';
    const URI = 'https://www.pcgamer.com/';
    const DESCRIPTION = 'PC Gamer is your source for exclusive reviews, demos, 
		updates and news on all your favorite PC gaming franchises.';
    const MAINTAINER = 'IceWreck, mdemoss';

    const PARAMETERS = [
        [
            'limit' => self::LIMIT,
        ]
    ];

    public function collectData()
    {
        $html = getSimpleHTMLDOMCached($this->getURI(), 300);
        $stories = $html->find('a.article-link');
        $limit = $this->getInput('limit') ?? 10;
        foreach (array_slice($stories, 0, $limit) as $element) {
            $item = [];
            $item['uri'] = $element->href;
            $articleHtml = getSimpleHTMLDOMCached($item['uri']);

            // Relying on meta tags ought to be more reliable.
            $item['title'] = $articleHtml->find('meta[name=parsely-title]', 0)->content;
            $item['content'] = html_entity_decode($articleHtml->find('meta[name=description]', 0)->content);
            $item['author'] = $articleHtml->find('meta[name=parsely-author]', 0)->content;
            $item['enclosures'][] = $articleHtml->find('meta[name=parsely-image-url]', 0)->content;
            /* I don't know why every article has two extra tags, but because
            one matches another common tag, "guide," it needs to be removed. */
            $item['categories'] = array_diff(
                explode(',', $articleHtml->find('meta[name=parsely-tags]', 0)->content),
                ['van_buying_guide_progressive', 'serversidehawk']
            );
            $item['timestamp'] = strtotime($articleHtml->find('meta[name=pub_date]', 0)->content);
            $this->items[] = $item;
        }
    }
}
