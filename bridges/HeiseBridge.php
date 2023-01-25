<?php

class HeiseBridge extends FeedExpander
{
    const MAINTAINER = 'Dreckiger-Dan';
    const NAME = 'Heise Online Bridge';
    const URI = 'https://heise.de/';
    const CACHE_TIMEOUT = 1800; // 30min
    const DESCRIPTION = 'Returns the full articles instead of only the intro';
    const PARAMETERS = [[
        'category' => [
            'name' => 'Category',
            'type' => 'list',
            'values' => [
                // source: https://www.heise.de/news-extern/news.html
                'heise online News'
                => 'https://www.heise.de/rss/heise-atom.xml',
                'heise online IT'
                => 'https://www.heise.de/rss/heise-Rubrik-IT-atom.xml',
                'heise online Wissen'
                => 'https://www.heise.de/rss/heise-Rubrik-Wissen-atom.xml',
                'heise online Mobiles'
                => 'https://www.heise.de/rss/heise-Rubrik-Mobiles-atom.xml',
                'heise online Entertainment'
                => 'https://www.heise.de/rss/heise-Rubrik-Entertainment-atom.xml',
                'heise online Netzpolitik'
                => 'https://www.heise.de/rss/heise-Rubrik-Netzpolitik-atom.xml',
                'heise online Wirtschaft'
                => 'https://www.heise.de/rss/heise-Rubrik-Wirtschaft-atom.xml',
                'heise online Journal'
                => 'https://www.heise.de/rss/heise-Rubrik-Journal-atom.xml',
                'heise online Top-News'
                => 'https://www.heise.de/rss/heise-top-atom.xml',
                //'iMonitor – Internet-Störungen'
                //=> 'https://www.heise.de/netze/netzwerk-tools/imonitor-internet-stoerungen/feed/aktuelle-meldungen/',
                //'heise tipps+tricks 🦄💻📱'
                //=> 'https://www.heise.de/rss/tipps-und-tricks-atom.xml',
                'Alle Inhalte von heise+'
                => 'https://www.heise.de/rss/heiseplus-atom.xml',
                'heise Autos News'
                => 'https://www.heise.de/autos/rss/news-atom.xml',
                'heise Developer - Neueste Meldungen'
                => 'https://www.heise.de/developer/rss/news-atom.xml',
                'Der Dotnet-Doktor'
                => 'https://www.heise.de/developer/rss/dotnet-doktor-blog-atom.xml',
                'the next big thing'
                => 'https://www.heise.de/developer/rss/next-big-thing-blog-atom.xml',
                'Tales from the Web side'
                => 'https://www.heise.de/developer/rss/tales-from-the-web-side-blog-atom.xml',
                'Continuous Architecture'
                => 'https://www.heise.de/developer/rss/continuous-architecture-blog-atom.xml',
                'Der Pragmatische Architekt'
                => 'https://www.heise.de/developer/rss/der-pragmatische-architekt-blog-atom.xml',
                'Modernes C++'
                => 'https://www.heise.de/developer/rss/modernes-cplusplus-blog-atom.xml',
                'colspan'
                => 'https://www.heise.de/developer/rss/colspan-dev-blog-atom.xml',
                '"Ich roll\' dann mal aus"'
                => 'https://www.heise.de/developer/rss/ich-roll-dann-mal-aus-atom.xml',
                'Well Organized'
                => 'https://www.heise.de/developer/rss/well-organized-blog-atom.xml',
                'Neuigkeiten von der Insel'
                => 'https://www.heise.de/developer/rss/neuigkeiten-von-der-insel-blog-atom.xml',
                'Von Menschen und Maschinen'
                => 'https://www.heise.de/developer/rss/von-menschen-und-maschinen-blog-atom.xml',
                'heise Foto'
                => 'https://www.heise.de/foto/rss/news-atom.xml',
                //'Top-Programme bei heise Download'
                //=> 'https://www.heise.de/download/feed/top',
                'heise Security'
                => 'https://www.heise.de/security/rss/news-atom.xml',
                'Security-Alert Meldungen'
                => 'https://www.heise.de/security/rss/alert-news-atom.xml',
                'c\'t-Blog'
                => 'https://www.heise.de/ct/blog/blog-atom.xml',
                'c\'t-Blog Labs'
                => 'https://www.heise.de/ct/blog/blog-ctlabs-atom.xml',
                'c\'t-Blog Fair & Green IT'
                => 'https://www.heise.de/ct/blog/blog-fgit-atom.xml',
                'c\'t-Blog RTFM'
                => 'https://www.heise.de/ct/blog/blog-rtfm-atom.xml',
                'c\'t-Themen'
                => 'https://www.heise.de/ct/rss/artikel-atom.xml',
                'Make - Neueste Meldungen'
                => 'https://www.heise.de/make/rss/hardware-hacks-atom.xml',
                'iX News'
                => 'https://www.heise.de/ix/rss/news-atom.xml',
                'Mac & i'
                => 'https://www.heise.de/mac-and-i/news-atom.xml',
                'MIT Technology Review'
                => 'https://www.heise.de/tr/rss/news-atom.xml',
                'MIT Technology Review Blog'
                => 'https://www.heise.de/tr/rss/blog-atom.xml',
                //'Telepolis'
                //=> 'https://www.heise.de/tp/news-atom.xml',
                //'Aktuelle News von TechStage'
                //=> 'https://www.techstage.de/rss.xml',
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

    public function collectData()
    {
        $this->collectExpandableDatas(
            $this->getInput('category'),
            $this->getInput('limit') ?: static::LIMIT
        );
    }

    protected function parseItem($feedItem)
    {
        $item = parent::parseItem($feedItem);
        $item['uri'] = explode('?', $item['uri'])[0] . '?seite=all';

        if (strpos($item['uri'], 'https://www.heise.de') !== 0) {
            return $item;
        }

        $article = getSimpleHTMLDOMCached($item['uri']);

        if ($article) {
            $article = defaultLinkTo($article, $item['uri']);
            $item = $this->addArticleToItem($item, $article);
        }

        return $item;
    }

    private function addArticleToItem($item, $article)
    {
        // relink URIs, as the previous a-img tags weren't recognized by this function
        $article = defaultLinkTo($article, $item['uri']);

        // remove unwanted stuff
        foreach ($article->find('figure.branding, a-ad, div.ho-text, a-img, .opt-in__content-container, .a-toc__list') as $element) {
            $element->remove();
        }
        // reload html, as remove() is buggy
        $article = str_get_html($article->outertext);

        $header = $article->find('header.a-article-header', 0);
        if ($header) {
            $headerElements = $header->find('p, figure img, noscript img');
            $item['content'] = implode('', $headerElements);

            $authors = $header->find('.a-creator__names .a-creator__name');
            if ($authors) {
                $item['author'] = implode(', ', array_map(function ($e) {
                    return $e->plaintext;
                }, $authors));
            }
        }

        $content = $article->find('.article-content', 0);
        if ($content) {
            $contentElements = $content->find(
                'p, h3, ul, table, pre, noscript img, a-bilderstrecke h2, a-bilderstrecke figure, a-bilderstrecke figcaption'
            );
            $item['content'] .= implode('', $contentElements);
        }

        return $item;
    }
}
