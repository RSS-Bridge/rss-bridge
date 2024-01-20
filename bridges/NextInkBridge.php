<?php

class NextInpactBridge extends FeedExpander
{
    const MAINTAINER = 'qwertygc and ORelio';
    const NAME = 'NextInpact Bridge';
    const URI = 'https://www.nextinpact.com/';
    const URI_HARDWARE = 'https://www.inpact-hardware.com/';
    const DESCRIPTION = 'Returns the newest articles.';

    const PARAMETERS = [ [
        'feed' => [
            'name' => 'Feed',
            'type' => 'list',
            'values' => [
                'Nos actualités' => [
                    'Toutes nos publications' => 'news',
                    'Toutes nos publications sauf #LeBrief' => 'nobrief',
                    'Toutes nos publications sauf INpact Hardware' => 'noih',
                    'Seulement les publications INpact Hardware' => 'hardware:news',
                    'Seulement les publications Next INpact' => 'nobrief-noih',
                    'Seulement les publications #LeBrief' => 'lebrief',
                ],
                'Flux spécifiques' => [
                    'Le blog' => 'blog',
                    'Les bons plans' => 'bonsplans',
                    'Publications INpact Hardware en accès libre' => 'hardware:acces-libre',
                    'Publications Next INpact en accès libre' => 'acces-libre',
                ],
                'Flux thématiques' => [
                    'Tech' => 'category:1',
                    'Logiciel' => 'category:2',
                    'Internet' => 'category:3',
                    'Mobilité' => 'category:4',
                    'Droit' => 'category:5',
                    'Économie' => 'category:6',
                    'Culture numérique' => 'category:7',
                    'Next INpact' => 'category:8',
                ]
            ]
        ],
        'filter_premium' => [
            'name' => 'Premium',
            'type' => 'list',
            'values' => [
                'No filter' => '0',
                'Hide Premium' => '1',
                'Only Premium' => '2'
            ]
        ],
        'filter_brief' => [
            'name' => 'Brief',
            'type' => 'list',
            'values' => [
                'No filter' => '0',
                'Hide Brief' => '1',
                'Only Brief' => '2'
            ]
        ],
        'limit' => self::LIMIT,
    ]];

    public function collectData()
    {
        $feed = $this->getInput('feed');
        $base_uri = self::URI;
        $args = '';

        if (empty($feed)) {
            // Default to All articles
            $feed = 'news';
        }

        if (strpos($feed, 'hardware:') === 0) {
            // Feed hosted on Hardware domain
            $base_uri = self::URI_HARDWARE;
            $feed = str_replace('hardware:', '', $feed);
        }

        if (strpos($feed, 'category:') === 0) {
            // Feed with specific category parameter
            $args = '?CategoryIds=' . str_replace('category:', '', $feed);
            $feed = 'params';
        }

        $url = sprintf('%srss/%s.xml%s', $base_uri, $feed, $args);
        $limit = $this->getInput('limit') ?? 10;
        $this->collectExpandableDatas($url, $limit);
    }

    protected function parseItem(array $item)
    {
        $item['content'] = $this->extractContent($item, $item['uri']);
        if (is_null($item['content'])) {
            return null; //Filtered article
        }
        return $item;
    }

    private function extractContent($item, $url)
    {
        $html = getSimpleHTMLDOMCached($url);
        if (!is_object($html)) {
            return 'Failed to request NextInpact: ' . $url;
        }

        // Filter premium and brief articles?
        $brief_selector = 'div.brief-container';
        foreach (
            [
            'filter_premium' => 'p.red-msg',
            'filter_brief' => $brief_selector
            ] as $param_name => $selector
        ) {
            $param_val = intval($this->getInput($param_name));
            if ($param_val != 0) {
                $element_present = is_object($html->find($selector, 0));
                $element_wanted = ($param_val == 2);
                if ($element_present != $element_wanted) {
                    return null; //Filter article
                }
            }
        }

        $article_content = $html->find('div.article-content', 0);
        if (!is_object($article_content)) {
            $article_content = $html->find('div.content', 0);
        }
        if (is_object($article_content)) {
            // Subtitle
            $subtitle = $html->find('small.subtitle', 0);
            if (!is_object($subtitle) && !is_object($html->find($brief_selector, 0))) {
                $subtitle = $html->find('small', 0);
            }
            if (!is_object($subtitle)) {
                $content_wrapper = $html->find('div.content-wrapper', 0);
                if (is_object($content_wrapper)) {
                    $subtitle = $content_wrapper->find('h2.title', 0);
                }
            }
            if (is_object($subtitle) && (!isset($item['title']) || $subtitle->plaintext != $item['title'])) {
                $subtitle = '<p><em>' . trim($subtitle->plaintext) . '</em></p>';
            } else {
                $subtitle = '';
            }

            // Image
            $postimg = $html->find('div.article-image, div.image-container', 0);
            if (is_object($postimg)) {
                $postimg = $postimg->find('img', 0);
                if (!empty($postimg->src)) {
                    $postimg = $postimg->src;
                } else {
                    $postimg = $postimg->srcset; //"url 355w, url 1003w, url 748w"
                    $postimg = explode(', ', $postimg); //split by ', ' to get each url separately
                    $postimg = end($postimg); //Get last item: "url 748w" which is of largest size
                    $postimg = explode(' ', $postimg); //split by ' ' to separate url from res
                    $postimg = array_reverse($postimg); //reverse array content to have url last
                    $postimg = end($postimg); //Get last item of array: "url"
                }
                $postimg = '<p><img src="' . $postimg . '" alt="-" /></p>';
            } else {
                $postimg = '';
            }

            // Paywall
            $paywall = $html->find('div.paywall-restriction', 0);
            if (is_object($paywall) && is_object($paywall->find('p.red-msg', 0))) {
                $paywall = '<p><em>' . $paywall->find('span.head-mention', 0)->innertext . '</em></p>';
            } else {
                $paywall = '';
            }

            // Content
            $article_content = $article_content->outertext;
            $article_content = str_replace('>Signaler une erreur</span>', '></span>', $article_content);

            // Result
            $text = $subtitle
                . $postimg
                . $article_content
                . $paywall;
        } else {
            $text = '<p><em>Failed to retrieve full article content</em></p>';
            if (isset($item['content'])) {
                $text = $item['content'] . $text;
            }
        }

        return $text;
    }
}
