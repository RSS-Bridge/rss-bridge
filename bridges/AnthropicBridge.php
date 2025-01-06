<?php

class AnthropicBridge extends BridgeAbstract
{
    const MAINTAINER = 'sqrtminusone';
    const NAME = 'Anthropic Research Bridge';
    const URI = 'https://www.anthropic.com';

    const CACHE_TIMEOUT = 3600; // 1 hour
    const DESCRIPTION = 'Returns research publications from Anthropic';

    const PARAMETERS = [
        '' => [
            'limit' => [
                'name' => 'Limit',
                'type' => 'number',
                'required' => true,
                'defaultValue' => 10
            ],
        ]
    ];

    public function collectData()
    {
        // Anthropic sometimes returns 500 for no reason. The contents are still there.
        $html = $this->getHTMLIgnoreError(self::URI . '/research');
        $limit = $this->getInput('limit');

        $page_data = $this->extractPageData($html);
        $pages = $this->parsePageData($page_data);
        for ($i = 0; $i < min(count($pages), $limit); $i++) {
            $page = $pages[$i];
            $page['content'] = $this->parsePage($page['uri']);
            $this->items[] = $page;
        }
    }

    private function getHTMLIgnoreError($url, $ttl = null)
    {
        if ($ttl != null) {
            $cacheKey = 'pages_' . $url;
            $content = $this->cache->get($cacheKey);
            if ($content) {
                return str_get_html($content);
            }
        }

        try {
            $content = getContents($url);
        } catch (HttpException $e) {
            $content = $e->response->getBody();
        }
        if ($ttl != null) {
            $this->cache->set($cacheKey, $content, $ttl);
        }
        return str_get_html($content);
    }

    private function extractPageData($html)
    {
        foreach ($html->find('script') as $script) {
            $js_code = $script->innertext;
            if (!str_starts_with($js_code, 'self.__next_f.push(')) {
                continue;
            }
            $push_data = (string)json_decode(mb_substr($js_code, 22, mb_strlen($js_code) - 2 - 22));
            $square_bracket = mb_strpos($push_data, '[');
            $push_array = json_decode(mb_substr($push_data, $square_bracket), true);
            if ($push_array == null || count($push_array) < 4) {
                continue;
            }
            $page_data = $push_array[3];
            if ($page_data != null && array_key_exists('page', $page_data)) {
                return $page_data;
            }
        }
    }

    private function parsePageData($page_data)
    {
        $result = [];
        foreach ($page_data['page']['sections'] as $section) {
            if (
                !array_key_exists('internalName', $section) ||
                $section['internalName'] != 'Research Teams'
            ) {
                continue;
            }
            foreach ($section['tabPages'] as $tabPage) {
                if ($tabPage['label'] != 'Overview') {
                    continue;
                }
                foreach ($tabPage['sections'] as $section1) {
                    if (
                        !array_key_exists('title', $section1)
                        || $section1['title'] != 'Publications'
                    ) {
                        continue;
                    }
                    foreach ($section1['posts'] as $post) {
                        $enc = [];
                        if ($post['cta'] != null && array_key_exists('url', $post['cta'])) {
                            $enc = [$post['cta']['url']];
                        }
                        $result[] = [
                            'title' => $post['title'],
                            'timestamp' => $post['publishedOn'],
                            'uri' => self::URI . '/research/' . $post['slug']['current'],
                            'categories' => array_map(
                                fn($s) => $s['label'],
                                $post['subjects'],
                            ),
                            'enclosures' => $enc,
                        ];
                    }
                    break;
                }
                break;
            }
            break;
        }
        return $result;
    }

    private function parsePage($url)
    {
        // Again, 500 for no reason.
        $html = $this->getHTMLIgnoreError($url, 7 * 24 * 60 * 60);

        $content = '';

        // Main content
        $main = $html->find('div[class*="PostDetail_post-detail"] > article', 0);

        // Mostly YouTube videos
        $iframes = $main->find('iframe');
        foreach ($iframes as $iframe) {
            $iframe->parent->removeAttribute('style');
            $iframe->outertext = '<a href="' . $iframe->src . '">' . $iframe->src . '</a>';
        }

        $main = convertLazyLoading($main);
        $main = defaultLinkTo($main, self::URI);
        $content .= $main;
        return $content;
    }
}
