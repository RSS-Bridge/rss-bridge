<?php

class DeutscheWelleBridge extends FeedExpander
{
    const MAINTAINER = 'No maintainer';
    const NAME = 'Deutsche Welle Bridge';
    const URI = 'https://www.dw.com';
    const DESCRIPTION = 'Returns the full articles instead of only the intro';
    const CACHE_TIMEOUT = 3600;
    const PARAMETERS = [[
        'feed' => [
            'name' => 'feed',
            'type' => 'list',
            'values' => [
                'All Top Stories and News Updates'
                => 'http://rss.dw.com/atom/rss-en-all',
                'Top Stories'
                => 'http://rss.dw.com/atom/rss-en-top',
                'Germany'
                => 'http://rss.dw.com/atom/rss-en-ger',
                'World'
                => 'http://rss.dw.com/atom/rss-en-world',
                'Europe'
                => 'http://rss.dw.com/atom/rss-en-eu',
                'Business'
                => 'http://rss.dw.com/atom/rss-en-bus',
                'Science'
                => 'http://rss.dw.com/atom/rss_en_science',
                'Environment'
                => 'http://rss.dw.com/atom/rss_en_environment',
                'Culture & Lifestyle'
                => 'http://rss.dw.com/atom/rss-en-cul',
                'Sports'
                => 'http://rss.dw.de/atom/rss-en-sports',
                'Visit Germany'
                => 'http://rss.dw.com/atom/rss-en-visitgermany',
                'Asia'
                => 'http://rss.dw.com/atom/rss-en-asia',
                'Deutsche Welle Gesamt'
                => 'http://rss.dw.com/atom/rss-de-all',
                'Themen des Tages'
                => 'http://rss.dw.com/atom/rss-de-top',
                'Nachrichten'
                => 'http://rss.dw.com/atom/rss-de-news',
                'Wissenschaft'
                => 'http://rss.dw.com/atom/rss-de-wissenschaft',
                'Sport'
                => 'http://rss.dw.com/atom/rss-de-sport',
                'Deutschland entdecken'
                => 'http://rss.dw.com/atom/rss-de-deutschlandentdecken',
                'Presse'
                => 'http://rss.dw.com/atom/presse',
                'Politik'
                => 'http://rss.dw.com/atom/rss_de_politik',
                'Wirtschaft'
                => 'http://rss.dw.com/atom/rss-de-eco',
                'Kultur & Leben'
                => 'http://rss.dw.com/atom/rss-de-cul',
                'Kultur & Leben: Buch'
                => 'http://rss.dw.com/atom/rss-de-cul-buch',
                'Kultur & Leben: Film'
                => 'http://rss.dw.com/atom/rss-de-cul-film',
                'Kultur & Leben: Musik'
                => 'http://rss.dw.com/atom/rss-de-cul-musik',
            ]
        ]
    ]];

    public function collectData()
    {
        $this->collectExpandableDatas($this->getInput('feed'));
    }

    protected function parseItem(array $item)
    {
        $parsedUri = parse_url($item['uri']);
        unset($parsedUri['query']);
        $item['uri'] = $this->unparseUrl($parsedUri);

        $page = getSimpleHTMLDOM($item['uri']);
        $page = defaultLinkTo($page, $item['uri']);

        $article = $page->find('article', 0);

        // author
        $author = $article->find('.author-link > span', 0);
        if ($author) {
            $item['author'] = $author->text();
        }

        $teaser = $article->find('.teaser-text', 0);
        if (!is_null($teaser)) {
            $item['content'] = $teaser->outertext();
        } else {
            $item['content'] = '';
        }

        // remove unneeded elements
        foreach (
            $article->find(
                'header, .advertisement, [data-tracking-name="sharing-icons-inline"], a.external-link > svg, picture > source, .vjs-wrapper, .dw-widget, footer'
            ) as $bad
        ) {
            $bad->remove();
        }
        // reload html as remove() is buggy
        $article = str_get_html($article->outertext());

        // remove width and height values from img tags
        foreach ($article->find('img') as $img) {
            $img->width = null;
            $img->height = null;
        }

        // remove bad img src's added by defaultLinkTo() above
        // these images should have src="" and will then use
        // the srcset attribute to load the best image for the displayed size
        foreach ($article->find('figure > picture > img') as $img) {
            $img->src = '';
        }

        // replace lazy-loaded images
        foreach ($article->find('figure.placeholder-image') as $figure) {
            $img = $figure->find('img', 0);
            $img->src = str_replace('${formatId}', '906', $img->getAttribute('data-url'));
            $img->style = null;
        }

        $item['content'] .= $article->save();

        return $item;
    }

    // https://www.php.net/manual/en/function.parse-url.php#106731
    private function unparseUrl($parsed_url)
    {
        $scheme = isset($parsed_url['scheme']) ? $parsed_url['scheme'] . '://' : '';
        $host = isset($parsed_url['host']) ? $parsed_url['host'] : '';
        $port = isset($parsed_url['port']) ? ':' . $parsed_url['port'] : '';
        $user = isset($parsed_url['user']) ? $parsed_url['user'] : '';
        $pass = isset($parsed_url['pass']) ? $parsed_url['pass'] : '';
        $pass = ($user || $pass) ? "$pass@" : '';
        $path = isset($parsed_url['path']) ? $parsed_url['path'] : '';
        $query = isset($parsed_url['query']) ? '?' . $parsed_url['query'] : '';
        $fragment = isset($parsed_url['fragment']) ? '#' . $parsed_url['fragment'] : '';
        return "$scheme$user$pass$host$port$path$query$fragment";
    }
}
