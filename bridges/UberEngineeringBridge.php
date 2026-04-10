<?php

class UberEngineeringBridge extends BridgeAbstract
{
    const NAME = 'Uber Engineering';
    const URI = 'https://www.uber.com/us/en/blog/engineering/';
    const DESCRIPTION = 'Returns posts from the Uber Engineering blog';
    const MAINTAINER = 'zj';
    const PARAMETERS = [];
    const CACHE_TIMEOUT = 3600;
    const ARTICLE_FEED_PATH = '/us/en/blog/engineering/';

    public function collectData()
    {
        $html = getContents(self::URI);
        $articles = self::extractArticleFeedFromHtml($html, self::ARTICLE_FEED_PATH);

        foreach ($articles as $article) {
            $title = trim($article['title'] ?? '');
            $uri = self::normalizeUrl($article['fullURL'] ?? '');

            if ($title === '' || $uri === '') {
                continue;
            }

            $item = [];
            $item['title'] = html_entity_decode($title, ENT_QUOTES | ENT_HTML5);
            $item['uri'] = $uri;

            if (!empty($article['publishedAt'])) {
                $timestamp = strtotime($article['publishedAt']);
                $item['timestamp'] = $timestamp !== false ? $timestamp : $article['publishedAt'];
            }

            $content = $this->buildItemContent($uri, $article['ogImageURL'] ?? '');
            if ($content !== '') {
                $item['content'] = $content;
            }

            if (!empty($article['ogImageURL'])) {
                $item['enclosures'][] = $article['ogImageURL'];
            }

            $this->items[] = $item;
        }
    }

    public static function extractArticleFeedFromHtml(string $html, string $path): array
    {
        $scriptId = '__LOCAL_REDUX_STATE_Newsroom_Article Feed Store_' . rawurlencode($path) . '__';
        $pattern = '#<script type="application/json" id="' . preg_quote($scriptId, '#') . '">\s*(.*?)\s*</script>#s';

        if (!preg_match($pattern, $html, $matches)) {
            throw new \Exception('Unable to find article feed data');
        }

        $payload = rawurldecode(html_entity_decode(trim($matches[1]), ENT_QUOTES | ENT_HTML5));
        $data = Json::decode($payload);
        $articles = $data['relatedPages']['relatedPages'] ?? null;

        if (!is_array($articles)) {
            throw new \Exception('Unable to parse article feed data');
        }

        return $articles;
    }

    public static function normalizeUrl(string $url): string
    {
        if ($url === '') {
            return '';
        }

        if (strpos($url, 'https://') === 0 || strpos($url, 'http://') === 0) {
            return $url;
        }

        if (strpos($url, '//') === 0) {
            return 'https:' . $url;
        }

        if (strpos($url, 'www.') === 0) {
            return 'https://' . $url;
        }

        if (strpos($url, '/') === 0) {
            return 'https://www.uber.com' . $url;
        }

        return 'https://www.uber.com/' . ltrim($url, '/');
    }

    private function buildItemContent(string $uri, string $imageUrl): string
    {
        $content = '';

        if ($imageUrl !== '') {
            $content .= '<p><img src="' . htmlspecialchars($imageUrl, ENT_QUOTES | ENT_HTML5) . '" /></p>';
        }

        $description = $this->fetchArticleDescription($uri);
        if ($description !== '') {
            $content .= '<p>' . htmlspecialchars($description, ENT_QUOTES | ENT_HTML5) . '</p>';
        }

        return $content;
    }

    private function fetchArticleDescription(string $uri): string
    {
        try {
            $html = getSimpleHTMLDOMCached($uri, self::CACHE_TIMEOUT);
        } catch (\Exception $e) {
            return '';
        }

        $description = $html->find('meta[name=description], meta[property=og:description]', 0);
        if ($description === null || !isset($description->content)) {
            return '';
        }

        return trim(html_entity_decode($description->content, ENT_QUOTES | ENT_HTML5));
    }
}
