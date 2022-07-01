<?php

class VixenBridge extends BridgeAbstract
{
    const NAME = 'Vixen Network Bridge';
    const URI = 'https://www.vixen.com';
    const DESCRIPTION = 'Latest videos from Vixen Network sites';
    const MAINTAINER = 'pubak42';

    /**
     * The pictures on the pages are referenced with temporary links with
     * limited validity. Greater cache timeout results in invalid links in
     * the feed
     */
    const CACHE_TIMEOUT = 60;

    const PARAMETERS = [
        [
            'site' => [
                'type' => 'list',
                'name' => 'Site',
                'title' => 'Choose site of interest',
                'values' => [
                    'Blacked' => 'Blacked',
                    'BlackedRaw' => 'BlackedRaw',
                    'Tushy' => 'Tushy',
                    'TushyRaw' => 'TushyRaw',
                    'Vixen' => 'Vixen',
                    'Slayed' => 'Slayed',
                    'Deeper' => 'Deeper'
                ],
            ]
        ]
    ];

    public function collectData()
    {
        $videosURL = $this->getURI() . '/videos';

        $website = getSimpleHTMLDOM($videosURL);
        $json = $website->getElementById('__NEXT_DATA__');
        $data = json_decode($json->innertext(), true);
        $nodes = array_column($data['props']['pageProps']['edges'], 'node');

        foreach ($nodes as $n) {
            $imageURL = $n['images']['listing'][2]['highdpi']['triple'];

            $item = [
                'title' => $n['title'],
                'uri' => "$videosURL/$n[slug]",
                'uid' => $n['videoId'],
                'timestamp' => strtotime($n['releaseDate']),
                'enclosures' => [ $imageURL ],
                'author' => implode(' & ', array_column($n['modelsSlugged'], 'name')),
            ];

            /*
             * No images retrieved from here. Should be cached for as long as
             * possible to avoid rate throttling
             */
            $target = getSimpleHtmlDOMCached($item['uri'], 86400);
            $item['content'] = $this->generateContent(
                $imageURL,
                $target->find('meta[name=description]', 0)->content,
                $n['modelsSlugged']
            );

            $item['categories'] = array_map(
                'ucwords',
                explode(',', $target->find('meta[name=keywords]', 0)->content)
            );

            $this->items[] = $item;
        }
    }

    public function getURI()
    {
        $param = $this->getInput('site');
        return $param ? "https://www.$param.com" : self::URI;
    }

    /**
     * Return name of the bridge. Default is needed for bridge index list
     */
    public function getName()
    {
        $param = $this->getInput('site');
        return $param ? "$param Bridge" : self::NAME;
    }

    private static function makeLink($URI, $text)
    {
        return "<a href=\"$URI\">$text</a>";
    }

    private function generateContent($imageURI, $description, $models)
    {
        $content = "<img src=\"$imageURI\" referrerpolicy=\"no-referrer\"/><p>$description</p>";
        $modelLinks = array_map(
            function ($model) {
                return self::makeLink(
                    $this->getURI() . "/models/$model[slugged]",
                    $model['name']
                );
            },
            $models
        );
        return $content . '<p>Starring: ' . implode(' & ', $modelLinks) . '</p>';
    }
}
