<?php

class RobinhoodSnacksBridge extends BridgeAbstract
{
    const MAINTAINER = 'johnpc';
    const NAME = 'Robinhood Snacks Newsletter';
    const URI = 'https://snacks.robinhood.com/newsletters/';
    const CACHE_TIMEOUT = 86400; // 24h
    const DESCRIPTION = 'Returns newsletters from Robinhood Snacks';

    // Work around 403 by pretending to be a legit browser
    const FAKE_HEADERS = array(
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:100.0) Gecko/20100101 Firefox/100.0',
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
        'Accept-Language: es-ES,en-US;q=0.7,en;q=0.3',
        'Accept-Encoding: gzip, deflate, br',
        'Connection: keep-alive',
        'Upgrade-Insecure-Requests: 1',
        'Sec-Fetch-Dest: document',
        'Sec-Fetch-Mode: navigate',
        'Sec-Fetch-Site: none',
        'Sec-Fetch-User: ?1',
        'Pragma: no-cache',
        'Cache-Control: no-cache',
        'TE: trailers'
    );

    public function collectData()
    {
        $html = getSimpleHTMLDOM(self::URI, self::FAKE_HEADERS);
        $html = defaultLinkTo($html, $this->getURI());

        $elements = $html->find('#__next > div > div > div > div > a');

        foreach ($elements as $element) {
            if ($element->href === 'https://snacks.robinhood.com/newsletters/page/2/') {
                continue;
            }

            $content = $element->find('div > div', 2);

            // Remove element that is not parsed (span with weekly tag)
            $unwanted_selector = 'span';
            foreach ($content->find($unwanted_selector) as $found) {
                $found->outertext = '';
            }

            $title = $content->find('div', 0)->innertext;
            $timestamp = strtotime($content->find('div', 1)->innertext);
            $uri = $element->href;

            $this->items[] = array(
                'uri' => $uri,
                'title' => $title,
                'timestamp' => $timestamp,
                'content' => self::getArticleContent($uri)
            );
        }
    }

    private function getArticleContent($uri)
    {
        $article_html = getSimpleHTMLDOMCached($uri, self::CACHE_TIMEOUT, self::FAKE_HEADERS);
        if (!$article_html) {
            return '';
        }

        $content = $article_html->find('#__next > div > div > div > span', 0);
        $content->removeChild($content->find('div', 0));
        $content->removeChild($content->find('h1', 0));
        $content->removeChild($content->find('img', 1));

        // Remove elements that are not part of article content
        $unwanted_selector = 'style';
        foreach ($content->find($unwanted_selector) as $found) {
            $found->outertext = '';
        }

        // Images cleanup
        $already_displayed_pictures = array();
        foreach ($content->find('img') as $found) {
            // Skip loader images
            if (str_contains($found->src, 'data:image/gif;base64')) {
                $found->outertext = '';
                continue;
            }

            // Skip multiple images with same src
            // and remove duplicated image description
            if (in_array($found->src, $already_displayed_pictures)) {
                $found->parent->parent->parent->outertext = '';
                $found->parent->parent->parent->nextSibling()->nextSibling()->outertext = '';
                continue;
            }

            // Remove srcset attribute
            $found->removeAttribute('srcset');

            // If relative img, fix path
            if (str_starts_with($found->src, '/_next')) {
                $found->setAttribute('src', 'https://snacks.robinhood.com' . $found->getAttribute('src'));
            }

            $already_displayed_pictures[] = $found->src;
        }

        $content_text = $content->innertext;

        // Remove noscript tag to display images
        $content_text = str_replace('<noscript>', '', $content_text);

        return $content_text;
    }
}
