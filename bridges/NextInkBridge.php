<?php

class NextInkBridge extends FeedExpander
{
    const MAINTAINER = 'ORelio';
    const NAME = 'Next.Ink Bridge';
    const URI = 'https://www.next.ink/';
    const DESCRIPTION = 'Returns the newest articles.';

    const PARAMETERS = [ [
        'feed' => [
            'name' => 'Feed',
            'type' => 'list',
            'values' => [
                'Publications' => [
                    'Toutes nos publications' => 'news',
                    'Droit' => 'news:3',
                    'Économie' => 'news:4',
                    'Flock' => 'news:13',
                    'Hardware' => 'news:9',
                    'IA et algorithmes' => 'news:6',
                    'Internet' => 'news:7',
                    'Logiciel' => 'news:8',
                    'Next' => 'news:14',
                    'Réseaux sociaux' => 'news:5',
                    'Sciences et escpace' => 'news:10',
                    'Sécurité' => 'news:12',
                    'Société numérique' => 'news:11',
                ],
                'Flux Gratuit' => [
                    'Publications en accès libre' => 'free',
                ],
            ],
            'title' => <<<EOT
                To obtain individual #LeBrief articles in your feed reader, generate two feeds:
                1. "Publications" with "Hide brief": Everything except #LeBrief
                2. "Flux Gratuit" with "Only Brief": Individual #LeBrief articles
                There may be a lot of #LeBrief entries at once, increase limit of "Flux Gratuit" to 20.
                EOT,
        ],
        'filter_premium' => [
            'name' => 'Premium',
            'type' => 'list',
            'values' => [
                'No filter' => '0',
                'Hide Premium' => '1',
                'Only Premium' => '2'
            ],
            'title' => 'Note: "Flux Gratuit" already excludes Premium articles.',
        ],
        'filter_brief' => [
            'name' => 'Brief',
            'type' => 'list',
            'values' => [
                'No filter' => '0',
                'Hide Brief' => '1',
                'Only Brief' => '2'
            ],
            'title' => 'Note: "Publications" has only one #LeBrief entry each day.',
        ],
        'limit' => self::LIMIT,
    ]];

    public function collectData()
    {
        $limit = $this->getInput('limit') ?? 10;

        $feed = explode(':', $this->getInput('feed'));
        $category = '';
        if (count($feed) > 1) {
            $category = $feed[1];
        }
        $feed = $feed[0];

        if ($feed === 'news') {
            // Scrap HTML listing to build list of articles
            $url = self::URI;
            if ($category !== '') {
                $url = $url . '?category=' . $category;
            }
            $this->collectArticlesFromHtmlListing($url, $limit);
        } else if ($feed === 'free') {
            // Expand Free RSS feed
            $url = self::URI . 'feed/free';
            $this->collectExpandableDatas($url, $limit);
        }
    }

    protected function collectArticlesFromHtmlListing($url, $limit)
    {
        $html = getSimpleHTMLDOM($url);
        $html = convertLazyLoading($html);
        foreach ($html->find('.block-article') as $article) {
            $author = $article->find('.author', 0);
            $subtitle = $article->find('h3', 0);
            $item = [
                'uri' => trim($article->find('a', 0)->href),
                'title' => trim($article->find('h2', 0)->plaintext),
                'author' => is_object($author) ? trim($author->plaintext) : '',
                'enclosures' => [ $article->find('img', 0)->src ],
                'content' => is_object($subtitle) ? trim($subtitle->plaintext) : '',
            ];
            $item = $this->parseItem($item);
            if ($item !== null) {
                $this->items[] = $item;
                if (--$limit == 0) {
                    break;
                }
            }
        }
    }

    protected function parseItem(array $item)
    {
        $html = getSimpleHTMLDOMCached($item['uri']);
        $html = convertLazyLoading($html);

        if (!is_object($html)) {
            $item['content'] = $item['content']
                . '<p><em>Failed to request Next.ink: ' . $item['uri'] . '</em></p>';
            return $item;
        }

        // Filter premium and brief articles?
        $paywall_selector = 'div#paywall';
        $brief_selector = 'div.brief-article';
        foreach (
            [
            'filter_premium' => $paywall_selector,
            'filter_brief' => $brief_selector,
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

        $article_content = $html->find('div.article-contenu, ' . $brief_selector, 0);
        if (is_object($article_content)) {
            // Clean article content
            foreach (
                [
                    'h1',
                    'div.author',
                    'p.brief-categories',
                    'div.thumbnail-mobile',
                    'div#share-bottom',
                    'div.author-info',
                    'div.other-article',
                    'script',
                ] as $item_to_remove
            ) {
                foreach ($article_content->find($item_to_remove) as $dom_node) {
                    $dom_node->outertext = '';
                }
            }
            // Image
            $postimg = $article_content->find('div.thumbnail', 0);
            if (empty($item['enclosures']) && is_object($postimg)) {
                $postimg = $postimg->find('img', 0);
                if (!empty($postimg->src)) {
                    $item['enclosures'] = [ $postimg->src ];
                }
            }
            // Timestamp
            $published_time = $html->find('meta[property=article:published_time]', 0);
            if (!isset($item['timestamp']) && is_object($published_time)) {
                $item['timestamp'] = strtotime($published_time->content);
            }
            // Paywall
            $paywall = $article_content->find($paywall_selector, 0);
            if (is_object($paywall) && is_object($paywall->find('h3', 0))) {
                $paywall->outertext = '<p><em>' . $paywall->find('h3', 0)->innertext . '</em></p>';
            }
            // Content
            $item['content'] = $article_content->outertext;
        } else {
            $item['content'] = $item['content'] . '<p><em>Failed to retrieve full article content</em></p>';
        }

        return $item;
    }
}
