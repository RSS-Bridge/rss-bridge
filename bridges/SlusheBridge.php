<?php

class SlusheBridge extends BridgeAbstract
{
    const MAINTAINER = 'quickwick';
    const NAME = 'Slushe';
    const URI = 'https://slushe.com';
    const DESCRIPTION = 'Returns latest posts from Slushe';

    const PARAMETERS = [
        'Artist' => [
            'artist_name' => [
                'name' => 'Artist name',
                'required' => true,
                'exampleValue' => 'lexx228',
                'title' => 'Enter an artist name'
            ]
        ],
        'Category' => [
            'category' => [
                'name' => 'Category',
                'type' => 'list',
                'defaultValue' => 'Safe for Work',
                'title' => 'Choose a category',
                'values' => [
                    '2D' => '29',
                    '3DX' => '58',
                    'Animation' => '60',
                    'Anime Fan Art' => '46',
                    'BDSM' => '47',
                    'Big Butt' => '73',
                    'Big Dick' => '52',
                    'Bit Tits' => '49',
                    'Bisexual' => '69',
                    'Comic' => '51',
                    'Couple' => '3',
                    'Dickgirl/Futanari' => '56',
                    'Feet' => '75',
                    'Game Fan Art' => '63',
                    'Gay' => '36',
                    'GIF' => '42',
                    'Group Sex/ Orgy' => '62',
                    'Lesbian' => '67',
                    'Mature' => '72',
                    'Misc. Fan Art' => '68',
                    'Monster' => '64',
                    'Pin-Up' => '28',
                    'Safe for Work' => '71',
                    'SFM' => '70',
                    'Solo' => '66',
                    'Threesome' => '38',
                    'TV & Film Fan Art' => '34',
                    'Western Fan Art' => '33'
                ]
            ]
        ],
        'Search' => [
            'search_term' => [
                'name' => 'Search term(s)',
                'required' => true,
                'exampleValue' => 'pole dance',
                'title' => 'Enter one or more search terms, separated by spaces'
            ]
        ]
    ];

    public function getName()
    {
        switch ($this->queriedContext) {
            case 'Artist':
                return 'Slushe Artist: ' . $this->getInput('artist_name');
                break;
            case 'Category':
                return 'Slushe Category: ' . $this->getInput('category');
                break;
            case 'Search':
                return 'Slushe Search: ' . $this->getInput('search_term');
                break;
            default:
                return self::NAME;
        }
    }

    public function collectData()
    {
        switch ($this->queriedContext) {
            case 'Artist':
                $uri = self::URI . '/' . $this->getInput('artist_name');
                break;
            case 'Category':
                $uri = self::URI . '/search/posts/channels?niche=' .
                    $this->getInput('category');
                break;
            case 'Search':
                $uri = self::URI . '/search/posts/' . $this->getInput('search_term') .
                    '?s=1';
                break;
        }

        $headers = [
            'Authority : slushe.com',
            'Cookie: age-verify=1;',
            'sec-ch-ua: "Chromium";v="100", " Not A;Brand";v="99"',
            'sec-ch-ua-mobile: ?0',
            'sec-ch-ua-platform: "Windows"',
            'sec-fetch-dest: document',
            'sec-fetch-mode: navigate',
            'sec-fetch-site: same-origin',
            'sec-fetch-user: ?1',
            'upgrade-insecure-requests: 1'
        ];
        // Add user-agent string to headers with implode, due to line length limit
        $user_agent_string = [
            'user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/',
            '537.36(KHTML, like Gecko) Chrome/100.0.4896.147 Safari/537.36'
        ];
        $headers[] = implode('', $user_agent_string);

        $html = getSimpleHTMLDOM($uri, $headers);

        //Loop on each entry
        foreach ($html->find('div.blog-item') as $element) {
            $title = $element->find('h3.title', 0)->first_child()->innertext;
            $article_uri = $element->find('h3.title', 0)->first_child()->href;
            $timestamp = $element->find('div.publication-date', 0)->innertext;
            $author = $element->find('div.artist', 0)->
                first_child()->first_child()->innertext;

            // Create & populate item
            $item = [];
            $item['uri'] = $article_uri;
            $item['id'] = $item['uri'];
            $item['timestamp'] = $timestamp;
            $item['title'] = $title;
            $item['author'] = $author;

            $media_html = '';

            // Look for image thumbnails
            $media_uris = $element->find('div.thumb', 0);
            if (isset($media_uris)) {
                // Add gallery image count, if it exists
                $gallery_count = $media_uris->find('span.count', 0);
                if (isset($gallery_count)) {
                    $media_html .= '<p>Gallery count: ' .
                        $gallery_count->first_child()->innertext . '</p>';
                }
                // Add image thumbnail(s)
                foreach ($media_uris->find('img') as $media_uri) {
                    $media_html .= '<a href="' . $article_uri . '">' . $media_uri . '</a>';
                    $item['enclosures'][] = str_replace(' ', '%20', $media_uri->src);
                }
            }

            // Look for video thumbnails
            $media_uris = $element->find('div.thumb-holder', 0);
            // Add video thumbnail(s)
            if (isset($media_uris)) {
                foreach ($media_uris->find('img') as $media_uri) {
                    $media_html .= '<p>Video:</p><a href="' .
                        $article_uri . '">' . $media_uri . '</a>';

                    $item['enclosures'][] = $media_uri->src;
                }
            }
            $item['content'] = $media_html;

            if (isset($item['title'])) {
                $this->items[] = $item;
            }
        }
    }
}
