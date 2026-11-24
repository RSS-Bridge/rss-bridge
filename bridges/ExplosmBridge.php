<?php

class ExplosmBridge extends BridgeAbstract
{
    const NAME = 'Explosm: Cyanide & Happiness';
    const URI = 'https://explosm.net';
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
        return self::URI . '/favicon-32x32.png';
    }

    public function getURI()
    {
        return self::URI;
    }

    public function collectData()
    {
        $limit = $this->getInput('limit');
        $url = self::URI . '/comics/' . $this->getLatestSlug();

        for ($i = 0; $i < $limit; $i++) {
            $html = getSimpleHTMLDOM($url);

            $element = $html->find('[class*=ComicImage]', 0);
            if (!$element) {
                break; // skip, if element was not found
            }
            $date    = $element->find('[class^=Author__Right] p', 0)->plaintext;
            $author  = str_replace('by ', '', $element->find('[class^=Author__Right] p', 1)->plaintext);
            $image   = $element->find('img', 0)->src;
            $link    = explode('#', $url)[0];

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

    private function getLatestSlug(): string
    {
        $html = getSimpleHTMLDOM(self::URI);
        $script = $html->find('script#__NEXT_DATA__', 0);
        if (!$script) {
            throwServerException('Could not find latest Explosm comic');
        }
        $data = Json::decode($script->innertext);
        foreach ($data['props']['pageProps']['urqlState'] ?? [] as $entry) {
            if (!is_string($entry['data'] ?? null)) {
                continue;
            }
            $entryData = Json::decode($entry['data']);
            $slug = $entryData['latest'][0]['slug'] ?? null;
            if (is_string($slug) && $slug !== '') {
                return $slug;
            }
        }
        throwServerException('Could not find latest Explosm comic');
    }
}
