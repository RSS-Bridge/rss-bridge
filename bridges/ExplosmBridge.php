<?php

class ExplosmBridge extends BridgeAbstract
{
    const NAME = 'Explosm: Cyanide & Happiness';
    const URI = 'https://explosm.net/';
    const DESCRIPTION = 'A Webcomic by Kris Wilson, Rob DenBleyker, and Dave McElfatrick.';
    const MAINTAINER = 'sal0max, bockiii';
    const CACHE_TIMEOUT = 60 * 60 * 2; // 2 hours
    const PARAMETERS = [[
            'limit' => [
                'name' => 'Limit',
                'type' => 'number',
                'title' => 'The number of recent comics to get.',
                'defaultValue' => 5
            ]
        ]
    ];

    public function getIcon()
    {
        return self::URI . 'favicon-32x32.png';
    }

    public function getURI()
    {
        return self::URI . 'comics/latest#comic';
    }

    public function collectData()
    {
        $limit = $this->getInput('limit');
        $url = $this->getUri();

        for ($i = 0; $i < $limit; $i++) {
            $html = getSimpleHTMLDOM($url);

            $element = $html->find('[class*=ComicImage]', 0);
            $date    = $element->find('[class^=Author__Right] p', 0)->plaintext;
            $author  = str_replace('by ', '', $element->find('[class^=Author__Right] p', 1)->plaintext);
            $image   = $element->find('img', 0)->src;
            $link    = $html->find('[rel=canonical]', 0)->href;

            $item = [
                'uid'       => $link,
                'author'    => $author,
                'title'     => $date,
                'uri'       => $link . '#comic',
                'timestamp' => str_replace('.', '-', $date) . 'T00:00:00Z',
                'content'   => "<img src=\"$image\" />"
            ];
            $this->items[] = $item;

            // get next url
            $url = self::URI . $html->find('[class*=ComicSelector]>a', 0)->href;
        }
    }
}
