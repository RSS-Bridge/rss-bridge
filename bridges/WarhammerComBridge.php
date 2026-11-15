<?php

class WarhammerComBridge extends BridgeAbstract
{
    const NAME = 'Warhammer Community Blog';
    const URI = 'https://www.warhammer-community.com';
    const DESCRIPTION = 'Warhammer Community Blog';
    const MAINTAINER = 'thefranke';
    const CACHE_TIMEOUT = 86400;

    public function collectData()
    {
        $url = static::URI . '/api/search/news/';

        $header = [
            'Content-Type: application/json',
        ];

        $data = '{"sortBy":"date_desc","category":"","collections":["articles"],"game_systems":[],"index":"news","locale":"en-gb","page":0,"perPage":16,"topics":[]}';

        $opts = [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_RETURNTRANSFER => true
        ];

        $json = getContents($url, $header, $opts);
        $json = json_decode($json);

        foreach ($json->news as $article) {
            $articleurl = $this->getArticleUrl($article);

            $content = null;
            try {
                $fullarticle = getSimpleHTMLDOMCached($articleurl);
                $content = $fullarticle->find('.article-content', 0);
            } catch (\Exception $e) {
                // Fall back to the excerpt below if the article page can't be fetched
            }

            if (!$content && !empty($article->excerpt)) {
                $content = $article->excerpt;
            }

            $categories = [];
            foreach ($article->topics as $topic) {
                $categories[] = $topic->title;
            }

            $this->items[] = [
                'title' => $article->title,
                'uri' => $articleurl,
                'timestamp' => strtotime($article->date_universal ?? $article->date),
                'content' => $content,
                'uid' => $article->uuid,
                'categories' => $categories
            ];
        }
    }

    /**
     * The API returns locale-less URIs like /articles/{uuid}/{slug}, which the
     * site 308-redirects to /{locale}/articles/{uuid}/{slug}/. Build the final
     * URL directly so the article fetch doesn't fail on the redirect.
     */
    private function getArticleUrl($article)
    {
        $path = '/' . trim($article->uri, '/') . '/';
        $locale = $article->site ?? 'en-gb';

        if (!preg_match('#^/[a-z]{2}-[a-z]{2}/#', $path)) {
            $path = '/' . $locale . $path;
        }

        return static::URI . $path;
    }
}
