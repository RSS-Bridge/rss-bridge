<?php

class CNETBridge extends BridgeAbstract
{
    const MAINTAINER = 'ORelio';
    const NAME = 'CNET News';
    const URI = 'https://www.cnet.com/';
    const CACHE_TIMEOUT = 3600; // 1h
    const DESCRIPTION = 'Returns the newest articles.';
    const PARAMETERS = [
        [
            'topic' => [
                'name' => 'Topic',
                'type' => 'list',
                'values' => [
                    'All articles' => '',
                    'Apple' => 'apple',
                    'Google' => 'google',
                    'Microsoft' => 'tags-microsoft',
                    'Computers' => 'topics-computers',
                    'Mobile' => 'topics-mobile',
                    'Sci-Tech' => 'topics-sci-tech',
                    'Security' => 'topics-security',
                    'Internet' => 'topics-internet',
                    'Tech Industry' => 'topics-tech-industry'
                ]
            ]
        ]
    ];

    private function cleanArticle($article_html)
    {
        $offset_p = strpos($article_html, '<p>');
        $offset_figure = strpos($article_html, '<figure');
        $offset = ($offset_figure < $offset_p ? $offset_figure : $offset_p);
        $article_html = substr($article_html, $offset);
        $article_html = str_replace('href="/', 'href="' . self::URI, $article_html);
        $article_html = str_replace(' height="0"', '', $article_html);
        $article_html = str_replace('<noscript>', '', $article_html);
        $article_html = str_replace('</noscript>', '', $article_html);
        $article_html = StripWithDelimiters($article_html, '<a class="clickToEnlarge', '</a>');
        $article_html = stripWithDelimiters($article_html, '<span class="nowPlaying', '</span>');
        $article_html = stripWithDelimiters($article_html, '<span class="duration', '</span>');
        $article_html = stripWithDelimiters($article_html, '<script', '</script>');
        $article_html = stripWithDelimiters($article_html, '<svg', '</svg>');
        return $article_html;
    }

    public function collectData()
    {
        // Retrieve and check user input
        $topic = str_replace('-', '/', $this->getInput('topic'));
        if (!empty($topic) && (substr_count($topic, '/') > 1 || !ctype_alpha(str_replace('/', '', $topic)))) {
            returnClientError('Invalid topic: ' . $topic);
        }

        // Retrieve webpage
        $pageUrl = self::URI . (empty($topic) ? 'news/' : $topic . '/');
        $html = getSimpleHTMLDOM($pageUrl);

        // Process articles
        foreach ($html->find('div.assetBody, div.riverPost') as $element) {
            if (count($this->items) >= 10) {
                break;
            }

            $article_title = trim($element->find('h2, h3', 0)->plaintext);
            $article_uri = self::URI . substr($element->find('a', 0)->href, 1);
            $article_thumbnail = $element->parent()->find('img[src]', 0)->src;
            $article_timestamp = strtotime($element->find('time.assetTime, div.timeAgo', 0)->plaintext);
            $article_author = trim($element->find('a[rel=author], a.name', 0)->plaintext);
            $article_content = '<p><b>' . trim($element->find('p.dek', 0)->plaintext) . '</b></p>';

            if (is_null($article_thumbnail)) {
                $article_thumbnail = extractFromDelimiters($element->innertext, '<img src="', '"');
            }

            if (!empty($article_title) && !empty($article_uri) && strpos($article_uri, self::URI . 'news/') !== false) {
                $article_html = getSimpleHTMLDOMCached($article_uri) or $article_html = null;

                if (!is_null($article_html)) {
                    if (empty($article_thumbnail)) {
                        $article_thumbnail = $article_html->find('div.originalImage', 0);
                    }
                    if (empty($article_thumbnail)) {
                        $article_thumbnail = $article_html->find('span.imageContainer', 0);
                    }
                    if (is_object($article_thumbnail)) {
                        $article_thumbnail = $article_thumbnail->find('img', 0)->src;
                    }

                    $article_content .= trim(
                        $this->cleanArticle(
                            extractFromDelimiters(
                                $article_html,
                                '<article',
                                '<footer'
                            )
                        )
                    );
                }

                $item = [];
                $item['uri'] = $article_uri;
                $item['title'] = $article_title;
                $item['author'] = $article_author;
                $item['timestamp'] = $article_timestamp;
                $item['enclosures'] = [$article_thumbnail];
                $item['content'] = $article_content;
                $this->items[] = $item;
            }
        }
    }
}
