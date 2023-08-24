<?php

class YorushikaBridge extends BridgeAbstract
{
    const NAME = 'Yorushika';
    const URI = 'https://yorushika.com';
    const DESCRIPTION = 'Return news from Yorushika\'s offical website';
    const MAINTAINER = 'Miicat_47';
    const PARAMETERS = [
        'All categories' => [
        ],
        'Only selected categories' => [
            'yorushika' => [
                'name' => 'Yorushika',
                'type' => 'checkbox',
            ],
            'suis' => [
                'name' => 'suis',
                'type' => 'checkbox',
            ],
            'n-buna' => [
                'name' => 'n-buna',
                'type' => 'checkbox',
            ],
        ]
    ];

    public function collectData()
    {
        $categories = [];
        if ($this->queriedContext == 'All categories') {
            array_push($categories, 'all');
        } else if ($this->queriedContext == 'Only selected categories') {
            if ($this->getInput('yorushika')) {
                array_push($categories, 'ヨルシカ');
            }
            if ($this->getInput('suis')) {
                array_push($categories, 'suis');
            }
            if ($this->getInput('n-buna')) {
                array_push($categories, 'n-buna');
            }
        }

        $html = getSimpleHTMLDOM('https://yorushika.com/news/5/')->find('.list--news', 0);
        $html = defaultLinkTo($html, $this->getURI());

        foreach ($html->find('.inview') as $art) {
            $item = [];

            // Get article category and check the filters
            $art_category = $art->find('.category', 0)->plaintext;
            if (!in_array('all', $categories) && !in_array($art_category, $categories)) {
                // Filtering is enabled and the category is not selected, skipping
                continue;
            }

            // Get article title
            $title = $art->find('.tit', 0)->plaintext;

            // Get article url
            $url = $art->find('a.clearfix', 0)->href;

            // Get article date
            $exp_date = '/\d+\.\d+\.\d+/';
            $date = $art->find('.date', 0)->plaintext;
            preg_match($exp_date, $date, $matches);
            $date = date_create_from_format('Y.m.d', $matches[0]);
            $date = date_format($date, 'd.m.Y');

            // Get article info
            $art_html = getSimpleHTMLDOMCached($url)->find('.text.inview', 0);
            $art_html = defaultLinkTo($art_html, $this->getURI());

            // Check if article contains a embed YouTube video
            $exp_youtube = '/https:\/\/[w\.]+youtube\.com\/embed\/([\w]+)/m';
            if (preg_match($exp_youtube, $art_html, $matches)) {
                // Replace the YouTube embed with a YouTube link
                $yt_embed = $art_html->find('iframe[src*="youtube.com"]', 0);
                $yt_link = sprintf('<a href="https://youtube.com/watch?v=%1$s">https://youtube.com/watch?v=%1$s</a>', $matches[1]);
                $art_html = str_replace($yt_embed, $yt_link, $art_html);
            }


            $item['uri'] = $url;
            $item['title'] = $title . ' (' . $art_category . ')';
            $item['content'] = $art_html;
            $item['timestamp'] = $date;

            $this->items[] = $item;
        }
    }
}
