<?php

class GolemBridge extends FeedExpander
{
    const MAINTAINER = 'Mynacol';
    const NAME = 'Golem Bridge';
    const URI = 'https://www.golem.de/';
    const CACHE_TIMEOUT = 1800; // 30min
    const DESCRIPTION = 'Returns the full articles instead of only the intro';
    const PARAMETERS = [[
        'category' => [
            'name' => 'Category',
            'type' => 'list',
            'values' => [
                'Alle News'
                => 'https://rss.golem.de/rss.php?feed=ATOM1.0',
                'Audio/Video'
                => 'https://rss.golem.de/rss.php?ms=audio-video&feed=ATOM1.0',
                'Auto'
                => 'https://rss.golem.de/rss.php?ms=auto&feed=ATOM1.0',
                'Foto'
                => 'https://rss.golem.de/rss.php?ms=foto&feed=ATOM1.0',
                'Games'
                => 'https://rss.golem.de/rss.php?ms=games&feed=ATOM1.0',
                'Handy'
                => 'https://rss.golem.de/rss.php?ms=handy&feed=ATOM1.0',
                'Internet'
                => 'https://rss.golem.de/rss.php?ms=internet&feed=ATOM1.0',
                'Mobil'
                => 'https://rss.golem.de/rss.php?ms=mobil&feed=ATOM1.0',
                'Open Source'
                => 'https://rss.golem.de/rss.php?ms=open-source&feed=ATOM1.0',
                'Politik/Recht'
                => 'https://rss.golem.de/rss.php?ms=politik-recht&feed=ATOM1.0',
                'Security'
                => 'https://rss.golem.de/rss.php?ms=security&feed=ATOM1.0',
                'Desktop-Applikationen'
                => 'https://rss.golem.de/rss.php?ms=desktop-applikationen&feed=ATOM1.0',
                'Software-Entwicklung'
                => 'https://rss.golem.de/rss.php?ms=softwareentwicklung&feed=ATOM1.0',
                'Wirtschaft'
                => 'https://rss.golem.de/rss.php?ms=wirtschaft&feed=ATOM1.0',
                'Wissenschaft'
                => 'https://rss.golem.de/rss.php?ms=wissenschaft&feed=ATOM1.0'
            ]
        ],
        'limit' => [
            'name' => 'Limit',
            'type' => 'number',
            'required' => false,
            'title' => 'Specify number of full articles to return',
            'defaultValue' => 5
        ]
    ]];
    const LIMIT = 5;
    const HEADERS = ['Cookie: golem_consent20=simple|250101;'];

    public function collectData()
    {
        $this->collectExpandableDatas(
            $this->getInput('category'),
            $this->getInput('limit') ?: static::LIMIT
        );
    }

    protected function parseItem(array $item)
    {
        $item['content'] ??= '';
        $uri = $item['uri'];

        $urls = [];

        while ($uri) {
            if (isset($urls[$uri])) {
                // Prevent loop in navigation links
                break;
            }
            $urls[$uri] = true;

            $articlePage = getSimpleHTMLDOMCached($uri, static::CACHE_TIMEOUT, static::HEADERS);
            $articlePage = defaultLinkTo($articlePage, $uri);

            // URI without RSS feed reference
            $item['uri'] = $articlePage->find('head meta[name="twitter:url"]', 0)->content;

            // extract categories
            if (!array_key_exists('categories', $item)) {
                $categories = $articlePage->find('div.go-tag-list__tags a.go-tag');
                foreach ($categories as $category) {
                    $trimmedcategories[] = trim(html_entity_decode($category->plaintext));
                }
                if (isset($trimmedcategories)) {
                    $item['categories'] = array_unique($trimmedcategories);
                }
            }

            // next page
            $nextUri = $articlePage->find('li.go-pagination__item--next a', 0);
            if ($nextUri) {
                $uri = $nextUri->href;
            } else {
                $uri = null;
            }

            // Only extract the content (and remove content) after all pre-processing is done
            $item['content'] .= $this->extractContent($articlePage, $item['content']);
        }

        return $item;
    }

    private function extractContent($page, $prevcontent)
    {
        $item = '';

        $article = $page->find('article', 0);

        //built youtube iframes
        foreach ($article->find('.go-embed-container') as &$embedcontent) {
            foreach ($page->find('script') as $ytscript) {
                if (preg_match('/(www.youtube.com.*?)\"/', $ytscript->innertext, $link)) {
                    $link = 'https://' . str_replace('\\', '', $link[1]);
                    $embedcontent->innertext .= <<<EOT
                        <iframe width="560" height="315" src="$link" title="YouTube video player" frameborder="0"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                        referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>';
                    EOT;
                    break;
                }
            }
        }

        //built golem videos
        foreach ($article->find('.gvideofig') as &$embedcontent) {
            if (preg_match('/gvideo_(.*)/', $embedcontent->id, $videoid)) {
                $embedcontent->innertext .= <<<EOT
                    <video class="rmp-object-fit-contain rmp-video" x-webkit-airplay="allow" controlslist="nodownload" tabindex="-1"
                    preload="metadata" src="https://video.golem.de/download/$videoid[1]"></video>                                                                      
                EOT;
            }
        }

        // delete known bad elements and unwanted gallery images
        foreach (
            $article->find('div[id*="adtile"], #job-market, #seminars, iframe, .go-article-header__title, .go-article-header__kicker, .go-label--sponsored,
                        .gbox_affiliate, div.toc, .go-button-bar, .go-alink-list, .go-teaser-block, .go-vh, .go-paywall, .go-index, .go-pagination__list,
                        .go-gallery .[data-active="false"], .go-article-header__series') as $bad
        ) {
            $bad->remove();
        }
        // reload html, as remove() is buggy
        $article = str_get_html($article->outertext);

        // Add multipage headers, but only if they are different to the article header
        $firstHeader = $page->find('.table-jtoc td', 0);
        if (isset($firstHeader)) {
            $firstHeader = html_entity_decode($firstHeader->title);
        }
        $multipageHeader = $article->find('header.paged-cluster-header h1', 0);
        if (isset($multipageHeader) && $multipageHeader->plaintext !== $firstHeader) {
            $item .= $multipageHeader;
        }

        $header = $article->find('header', 0);
        if (isset($header)) {
            foreach ($header->find('p, figure') as $element) {
                $item .= $element;
            }
        }

        foreach (
            $article->find('div.go-article-header__intro, p, h1, h2, h3, pre, ul, ol, .go-media img[src*="."], .go-media figcaption,
                    table, iframe, video') as $element
        ) {
            if (!str_contains($prevcontent, $element)) {
                $item .= $element;
            }
        }

        return $item;
    }
}
