<?php

class YorushikaBridge extends BridgeAbstract
{
    const NAME = 'Yorushika';
    const URI = 'https://yorushika.com';
    const DESCRIPTION = 'Return news from Yorushika\'s offical website';
    const MAINTAINER = 'Miicat_47';
    const PARAMETERS = [
        'global' => [
            'lang' => [
                'name' => 'Language',
                'defaultValue' => 'jp',
                'type' => 'list',
                'values' => [
                    '日本語' => 'jp',
                    'English' => 'en',
                    '한국어' => 'ko',
                    '中文(繁體字)' => 'zh-tw',
                    '中文(簡体字)' => 'zh-cn',
                ]
            ],
        ],
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
        switch ($this->getInput('lang')) {
            case 'jp':
                $url = 'https://yorushika.com/news/5/';
                break;
            case 'en':
                $url = 'https://yorushika.com/news/5/?lang=en';
                break;
            case 'ko':
                $url = 'https://yorushika.com/news/5/?lang=ko';
                break;
            case 'zh-tw':
                $url = 'https://yorushika.com/news/5/?lang=zh-tw';
                break;
            case 'zh-cn':
                $url = 'https://yorushika.com/news/5/?lang=zh-cn';
                break;
            default:
                $url = 'https://yorushika.com/news/5/';
                break;
        }

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

        $html = getSimpleHTMLDOM($url)->find('.list--news', 0);
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
            $date = $art->find('.date', 0)->plaintext;
            preg_match('/(\d+)[\.年](\d+)[\.月](\d+)/u', $date, $matches);
            $formattedDate = sprintf('%d.%02d.%02d', $matches[1], $matches[2], $matches[3]);
            $date = date_create_from_format('Y.m.d', $formattedDate);
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
