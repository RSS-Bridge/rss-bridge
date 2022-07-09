<?php

class EuronewsBridge extends BridgeAbstract
{
    const MAINTAINER = 'sqrtminusone';
    const NAME = 'Euronews Bridge';
    const URI = 'https://www.euronews.com/';
    const CACHE_TIMEOUT = 600; // 10 minutes
    const DESCRIPTION = 'Return articles from the "Just In" feed of Euronews.';

    const PARAMETERS = [
        '' => [
            'lang' => [
                'name' => 'Language',
                'type' => 'list',
                'defaultValue' => 'www.euronews.com',
                'values' => [
                    'English' => 'www.euronews.com',
                    'French' => 'fr.euronews.com',
                    'German' => 'de.euronews.com',
                    'Italian' => 'it.euronews.com',
                    'Spanish' => 'es.euronews.com',
                    'Portuguese' => 'pt.euronews.com',
                    'Russian' => 'ru.euronews.com',
                    'Turkish' => 'tr.euronews.com',
                    'Greek' => 'gr.euronews.com',
                    'Hungarian' => 'hu.euronews.com',
                    'Persian' => 'per.euronews.com',
                    'Arabic' => 'arabic.euronews.com',
                    /* These versions don't have timeline.json */
                    // 'Albanian' => 'euronews.al',
                    // 'Romanian' => 'euronews.ro',
                    // 'Georigian' => 'euronewsgeorgia.com',
                    // 'Bulgarian' => 'euronewsbulgaria.com'
                    // 'Serbian' => 'euronews.rs'
                ]
            ],
            'limit' => [
                'name' => 'Limit of items per feed',
                'required' => true,
                'type' => 'number',
                'defaultValue' => 10,
                'title' => 'Maximum number of returned feed items. Maximum 50, default 10'
            ],
        ]
    ];

    public function collectData()
    {
        $limit = $this->getInput('limit');
        $lang = $this->getInput('lang');
        if ($lang === 'euronews.com') {
            $lang = 'www.euronews.com';
        }
        $root_url = 'https://' . $lang;
        $url = $root_url . '/api/timeline.json?limit=' . $limit;
        $json = getContents($url);
        $data = json_decode($json, true);

        foreach ($data as $datum) {
            $datum_uri = $root_url . $datum['fullUrl'];
            $url_datum = $this->getItemContent($datum_uri);
            $categories = [];
            if (array_key_exists('program', $datum)) {
                if (array_key_exists('title', $datum['program'])) {
                    $categories[] = $datum['program']['title'];
                }
            }
            if (array_key_exists('themes', $datum)) {
                foreach ($datum['themes'] as $theme) {
                    $categories[] = $theme['title'];
                }
            }
            $item = [
                'uri' => $datum_uri,
                'title' => $datum['title'],
                'uid' => strval($datum['id']),
                'timestamp' => $datum['publishedAt'],
                'content' => $url_datum['content'],
                'author' => $url_datum['author'],
                'enclosures' => $url_datum['enclosures'],
                'categories' => array_unique($categories)
            ];
            $this->items[] = $item;
        }
    }

    private function getItemContent($url)
    {
        try {
            $html = getSimpleHTMLDOMCached($url);
        } catch (Exception $e) {
            // Every once in a while it fails with too many redirects
            return ['author' => null, 'content' => null, 'enclosures' => null];
        }
        $data = $html->find('script[type="application/ld+json"]', 0)->innertext;
        $json = json_decode($data, true);
        $author = 'Euronews';
        $content = '';
        $enclosures = [];
        if (array_key_exists('@graph', $json)) {
            foreach ($json['@graph'] as $item) {
                if ($item['@type'] == 'NewsArticle') {
                    if (array_key_exists('author', $item)) {
                        $author = $item['author']['name'];
                    }
                    if (array_key_exists('image', $item)) {
                        $content .= '<figure>';
                        $content .= '<img src="' . $item['image']['url'] . '">';
                        $content .= '<figcaption>' . $item['image']['caption'] . '</figcaption>';
                        $content .= '</figure><br>';
                    }
                    if (array_key_exists('video', $item)) {
                        $enclosures[] = $item['video']['contentUrl'];
                    }
                }
            }
        }

        // Normal article
        $article_content = $html->find('.c-article-content', 0);
        if ($article_content) {
            // Usually the .c-article-content is the root of the
            // content, but once in a blue moon the root is the second
            // div
            if (
                (count($article_content->children()) == 2)
                && ($article_content->children(1)->tag == 'div')
            ) {
                $article_content = $article_content->children(1);
            }
            // The content is interspersed with links and stuff, so we
            // iterate over the children
            foreach ($article_content->children() as $element) {
                if ($element->tag == 'p') {
                    $scribble_live = $element->find('#scribblelive-items', 0);
                    if (is_null($scribble_live)) {
                        // A normal paragraph
                        $content .= '<p>' . $element->innertext . '</p>';
                    } else {
                        // LIVE mode
                        foreach ($scribble_live->children() as $child) {
                            if ($child->tag == 'div') {
                                $content .= '<div>' . $child->innertext . '</div>';
                            }
                        }
                    }
                } elseif (preg_match('/h[1-6]/', $element->tag)) {
                    // Header
                    $content .= '<h' . $element->tag[1] . '>' . $element->innertext . '</h' . $element->tag[1] . '>';
                } elseif ($element->tag == 'div') {
                    if (preg_match('/.*widget--type-image.*/', $element->class)) {
                        // Image
                        $content .= '<figure>';
                        $content .= '<img src="' . $element->find('img', 0)->src . '">';
                        $caption = $element->find('figcaption', 0);
                        if ($caption) {
                            $content .= '<figcaption>' . $element->plaintext . '</figcaption>';
                        }
                        $content .= '</figure><br>';
                    } elseif (preg_match('/.*widget--type-quotation.*/', $element->class)) {
                        // Quotation
                        $quote = $element->find('.widget__quoteText', 0);
                        $author = $element->find('.widget__author', 0);
                        $content .= '<figure>';
                        $content .= '<blockquote>' . $quote->plaintext . '</blockquote>';
                        if ($author) {
                            $content .= '<figcaption>' . $author->plaintext . '</figcaption>';
                        }
                        $content .= '</figure><br>';
                    }
                }
            }
        }

        // Video article
        if (is_null($article_content)) {
            $image = $html->find('.c-article-media__img', 0);
            if ($image) {
                $content .= '<figure>';
                $content .= '<img src="' . $image->src . '">';
                $content .= '</figure><br>';
            }

            $description = $html->find('.m-object__description', 0);
            if ($description) {
                // In some editions the description is a link to the
                // current page
                $content .= '<div>' . $description->plaintext . '</div>';
            }

            // Euronews usually hosts videos on dailymotion...
            $player_div = $html->find('.dmPlayer', 0);
            if ($player_div) {
                $video_id = $player_div->getAttribute('data-video-id');
                $video_url = 'https://www.dailymotion.com/video/' . $video_id;
                $content .= '<a href="' . $video_url . '">' . $video_url . '</a>';
            }

            // ...or on YouTube
            $player_div = $html->find('.js-player-pfp', 0);
            if ($player_div) {
                $video_id = $player_div->getAttribute('data-video-id');
                $video_url = 'https://www.youtube.com/watch?v=' . $video_id;
                $content .= '<a href="' . $video_url . '">' . $video_url . '</a>';
            }
        }

        return [
            'author' => $author,
            'content' => $content,
            'enclosures' => $enclosures
        ];
    }
}
