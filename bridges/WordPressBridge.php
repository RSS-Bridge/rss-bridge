<?php

class WordPressBridge extends FeedExpander
{
    const NAME = 'Wordpress Bridge';
    const URI = 'https://wordpress.org/';
    const DESCRIPTION = 'Returns the newest full posts of a WordPress powered website';

    const PARAMETERS = [ [
        'url' => [
            'name' => 'Blog URL',
            'exampleValue' => 'https://www.wpbeginner.com/',
            'required' => true
        ]
    ]];

    private function cleanContent($content)
    {
        $content = stripWithDelimiters($content, '<script', '</script>');
        $content = preg_replace('/<div class="wpa".*/', '', $content);
        $content = preg_replace('/<form.*\/form>/', '', $content);
        return $content;
    }

    protected function parseItem($newItem)
    {
        $item = parent::parseItem($newItem);

        $article_html = getSimpleHTMLDOMCached($item['uri']);

        $article = null;
        switch (true) {
        // Custom fix for theme in https://jungefreiheit.de/politik/deutschland/2022/wahl-im-saarland/
            case !is_null($article_html->find('div[data-widget_type="theme-post-content.default"]', 0)):
                $article = $article_html->find('div[data-widget_type="theme-post-content.default"]', 0);
                break;
            case !is_null($article_html->find('[itemprop=articleBody]', 0)):
                // highest priority content div
                $article = $article_html->find('[itemprop=articleBody]', 0);
                break;
            case !is_null($article_html->find('article', 0)):
                // most common content div
                $article = $article_html->find('article', 0);
                break;
            case !is_null($article_html->find('.single-content', 0)):
                // another common content div
                $article = $article_html->find('.single-content', 0);
                break;
            case !is_null($article_html->find('.post-content', 0)):
                // another common content div
                $article = $article_html->find('.post-content', 0);
                break;
            case !is_null($article_html->find('.post', 0)):
                // for old WordPress themes without HTML5
                $article = $article_html->find('.post', 0);
                break;
        }

        foreach ($article->find('h1.entry-title') as $title) {
            if ($title->plaintext == $item['title']) {
                $title->outertext = '';
            }
        }

        $article_image = $article_html->find('img.wp-post-image', 0);
        if (!empty($item['content']) && (!is_object($article_image) || empty($article_image->src))) {
            $article_image = str_get_html($item['content'])->find('img.wp-post-image', 0);
        }
        if (is_object($article_image) && !empty($article_image->src)) {
            if (empty($article_image->getAttribute('data-lazy-src'))) {
                $article_image = $article_image->src;
            } else {
                $article_image = $article_image->getAttribute('data-lazy-src');
            }
            $mime_type = parse_mime_type($article_image);
            if (strpos($mime_type, 'image') === false) {
                $article_image .= '#.image'; // force image
            }
            if (empty($item['enclosures'])) {
                $item['enclosures'] = [$article_image];
            } else {
                $item['enclosures'] = array_merge($item['enclosures'], (array) $article_image);
            }
        }

        if (!is_null($article)) {
            $item['content'] = $this->cleanContent($article->innertext);
            $item['content'] = defaultLinkTo($item['content'], $item['uri']);
        }

        return $item;
    }

    public function getURI()
    {
        $url = $this->getInput('url');
        if (empty($url)) {
            $url = parent::getURI();
        }
        return $url;
    }

    public function collectData()
    {
        if ($this->getInput('url') && substr($this->getInput('url'), 0, strlen('http')) !== 'http') {
            // just in case someone find a way to access local files by playing with the url
            returnClientError('The url parameter must either refer to http or https protocol.');
        }
        try {
            $this->collectExpandableDatas($this->getURI() . '/feed/atom/', 20);
        } catch (Exception $e) {
            $this->collectExpandableDatas($this->getURI() . '/?feed=atom', 20);
        }
    }
}
