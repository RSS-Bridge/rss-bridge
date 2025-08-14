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
            $item['title'] = $articleHtml->find('meta[property=og:title]', 0)->content;
            $item['content'] = html_entity_decode($articleHtml->find('meta[name=description]', 0)->content);

            // TODO: parsely-author is no longer available, but it is in the application/ld+json
            $item['author'] = $articleHtml->find('a[rel=author]', 0)->innertext;

            $imageUrl = $articleHtml->find('meta[property=og:image]', 0);
            if ($imageUrl) {
                $item['enclosures'][] = $imageUrl->content;
            }

            /*
            Tags in mrf:tags are semicolon-delimited and each begins with a label and a ':'
            Example:
                "region:US;articleType:News;channel:Gaming software;"
            Find the tag, replace ; with \n, remove the label prefixes, then explode by newline.
            */
            $item['categories'] = array_unique(
                explode(
                    PHP_EOL,
                    preg_replace(
                        '/^[^:]+:/m',
                        '',
                        preg_replace(
                            '/;/',
                            PHP_EOL,
                            $articleHtml->find('meta[property=mrf:tags]', 0)->content
                        )
                    )
                )
            );

            $item['timestamp'] = strtotime($articleHtml->find('meta[name=pub_date]', 0)->content);

            $this->items[] = $item;
        }
    }
}
