<?php

class GatesNotesBridge extends FeedExpander
{
    const MAINTAINER = 'corenting';
    const NAME = 'Gates Notes';
    const URI = 'https://www.gatesnotes.com';
    const DESCRIPTION = 'Returns the newest articles.';
    const CACHE_TIMEOUT = 21600; // 6h

    protected function parseItem($item)
    {
        $item = parent::parseItem($item);

        $article_html = getSimpleHTMLDOMCached($item['uri']);
        if (!$article_html) {
            $item['content'] .= '<p><em>Could not request ' . $this->getName() . ': ' . $item['uri'] . '</em></p>';
            return $item;
        }
        $article_html = defaultLinkTo($article_html, $this->getURI());

        $top_description = '<p>' . $article_html->find('div.article_top_description', 0)->innertext . '</p>';
        $hero_image = '<img src=' . $article_html->find('img.article_top_DMT_Image', 0)->getAttribute('data-src') . '>';

        $article_body = $article_html->find('div.TGN_Article_ReadTimeSection', 0);
        // Convert iframe of Youtube videos to link
        foreach ($article_body->find('iframe') as $found) {
            $iframeUrl = $found->getAttribute('src');

            if ($iframeUrl) {
                $text = 'Embedded Youtube video, click here to watch on Youtube.com';
                $found->outertext = '<p><a href="' . $iframeUrl . '">' . $text . '</a></p>';
            }
        }
        // Remove <link> CSS ressources
        foreach ($article_body->find('link') as $found) {
            $linkedRessourceUrl = $found->getAttribute('href');

            if (str_ends_with($linkedRessourceUrl, '.css')) {
                $found->outertext = '';
            }
        }
        $article_body = sanitize($article_body->innertext);

        $item['content'] = $top_description . $hero_image . $article_body;

        return $item;
    }

    public function collectData()
    {
        $feed = static::URI . '/rss';
        $this->collectExpandableDatas($feed);
    }
}
