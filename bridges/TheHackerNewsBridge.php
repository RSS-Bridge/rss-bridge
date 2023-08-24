<?php

class TheHackerNewsBridge extends BridgeAbstract
{
    const MAINTAINER = 'ORelio';
    const NAME = 'The Hacker News Bridge';
    const URI = 'https://thehackernews.com/';
    const DESCRIPTION = 'Cyber Security, Hacking, Technology News.';

    public function collectData()
    {
        $html = getSimpleHTMLDOM($this->getURI());
        $html = convertLazyLoading($html);
        $html = defaultLinkTo($html, $this->getURI());
        $limit = 0;

        foreach ($html->find('div.body-post') as $element) {
            if ($limit >= 5) {
                break;
            }

            // Author (not present on home page)
            $article_author = null;

            // Title
            $article_title = $element->find('h2.home-title', 0)->plaintext;

            // Date
            $article_timestamp = time();
            $calendar = $element->find('i.icon-calendar', 0);
            if ($calendar) {
                $article_timestamp = strtotime(
                    extractFromDelimiters(
                        $calendar->parent()->outertext,
                        '</i>',
                        '</span>'
                    )
                );
            }

            // Thumbnail
            $article_thumbnail = [];
            if (is_object($element->find('img', 0))) {
                $article_thumbnail = [ $element->find('img', 0)->src ];
            }

            // Content (truncated)
            $article_content = $element->find('div.home-desc', 0)->plaintext;

            // Now try expanding article
            $article_url = $element->find('a.story-link', 0)->href;
            $article_html = getSimpleHTMLDOMCached($article_url);
            if ($article_html) {
                // Content (expanded and cleaned)
                $article_body = $article_html->find('div.articlebody', 0);
                if ($article_body) {
                    $article_body = convertLazyLoading($article_body);
                    $article_body = defaultLinkTo($article_body, $article_url);
                    $header_img = $article_body->find('img', 0);
                    if ($header_img) {
                        $header_img->parent->style = '';
                    }
                    foreach ($article_body->find('center.cf') as $center_ad) {
                        $center_ad->outertext = '';
                    }
                    $article_content = $article_body->innertext;
                }
                // Author
                $spans_author = $article_html->find('span.author');
                if (count($spans_author) > 0) {
                    $article_author = $spans_author[array_key_last($spans_author)]->plaintext;
                }
            }

            $item = [];
            $item['uri'] = $article_url;
            $item['title'] = $article_title;
            if (!empty($article_author)) {
                $item['author'] = $article_author;
            }
            $item['enclosures'] = $article_thumbnail;
            $item['timestamp'] = $article_timestamp;
            $item['content'] = trim($article_content);
            $this->items[] = $item;
            $limit++;
        }
    }
}
