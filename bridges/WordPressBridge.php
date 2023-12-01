<?php

class WordPressBridge extends FeedExpander
{
    const NAME = 'Wordpress Bridge';
    const URI = 'https://wordpress.org/';
    const DESCRIPTION = 'Returns the newest full posts of a WordPress powered website';
    const MAINTAINER = 'ORelio';

    const PARAMETERS = [ [
        'url' => [
            'name' => 'Blog URL',
            'exampleValue' => 'https://wordpress.org/',
            'required' => true
        ],
        'limit' => self::LIMIT,
        'content-selector' => [
            'name' => 'Content Selector (Optional - Advanced users)',
            'exampleValue' => '.custom-article-class',
        ],
    ]];

    public function collectData()
    {
        $limit = $this->getInput('limit') ?? 10;
        if ($this->getInput('url') && substr($this->getInput('url'), 0, strlen('http')) !== 'http') {
            // just in case someone find a way to access local files by playing with the url
            returnClientError('The url parameter must either refer to http or https protocol.');
        }
        try {
            $this->collectExpandableDatas($this->getURI() . '/feed/atom/', $limit);
        } catch (Exception $e) {
            $this->collectExpandableDatas($this->getURI() . '/?feed=atom', $limit);
        }
    }

    protected function parseItem(array $item)
    {
        $dom = getSimpleHTMLDOMCached($item['uri']);

        // Find article body
        $article = null;
        switch (true) {
            case !empty($this->getInput('content-selector')):
                // custom contect selector (manually specified by user)
                $article = $dom->find($this->getInput('content-selector'), 0);
                break;
            case !is_null($dom->find('[itemprop=articleBody]', 0)):
                // highest priority content div (used for SEO)
                $article = $dom->find('[itemprop=articleBody]', 0);
                break;
            case !is_null($dom->find('.article-content', 0)):
                // more precise than article when present
                $article = $dom->find('.article-content', 0);
                break;
            case !is_null($dom->find('article', 0)):
                // most common content div
                $article = $dom->find('article', 0);
                break;
            case !is_null($dom->find('.single-content', 0)):
                // another common content div
                $article = $dom->find('.single-content', 0);
                break;
            case !is_null($dom->find('.post-content', 0)):
                // another common content div
                $article = $dom->find('.post-content', 0);
                break;
            case !is_null($dom->find('.post', 0)):
                // for old WordPress themes without HTML5
                $article = $dom->find('.post', 0);
                break;
        }

        // Remove duplicate title from content
        foreach ($article->find('h1') as $title) {
            if (trim(html_entity_decode($title->plaintext) == $item['title'])) {
                $title->outertext = '';
            }
        }

        // Find article main image
        $article = convertLazyLoading($article);
        $article_image = $dom->find('img.wp-post-image', 0);
        if (!empty($item['content']) && (!is_object($article_image) || empty($article_image->src))) {
            $article_image = str_get_html($item['content'])->find('img.wp-post-image', 0);
        }
        if (is_object($article_image) && !empty($article_image->src)) {
            $article_image = $article_image->src;
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

        // Unwrap images figures
        foreach ($article->find('figure.wp-block-image') as $figure) {
            $figure->outertext = $figure->innertext;
        }

        if (!is_null($article)) {
            $item['content'] = $this->cleanContent($article->innertext);
            $item['content'] = defaultLinkTo($item['content'], $item['uri']);
        }

        return $item;
    }

    private function cleanContent($content)
    {
        $content = stripWithDelimiters($content, '<script', '</script>');
        $content = preg_replace('/<div class="wpa".*/', '', $content);
        $content = preg_replace('/<form.*\/form>/', '', $content);
        return $content;
    }

    public function getURI()
    {
        $url = $this->getInput('url');
        if (empty($url)) {
            $url = parent::getURI();
        }
        return $url;
    }
}
