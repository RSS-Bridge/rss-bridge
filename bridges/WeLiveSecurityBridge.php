<?php

class WeLiveSecurityBridge extends FeedExpander
{
    const MAINTAINER = 'ORelio';
    const NAME = 'We Live Security';
    const URI = 'https://www.welivesecurity.com/';
    const DESCRIPTION = 'Returns the newest articles.';
    const PARAMETERS = [
        [
            'limit' => self::LIMIT,
        ],
    ];

    protected function parseItem(array $item)
    {
        $html = getSimpleHTMLDOMCached($item['uri']);
        if (!$html) {
            $item['content'] .= '<br /><p><em>Could not request ' . $this->getName() . ': ' . $item['uri'] . '</em></p>';
            return $item;
        }

        $html = $html->find('.article-page', 0);
        $content_html = $html->find('.article-body', 0);

        // Remove social media footer
        foreach ($content_html->find('blockquote') as $blockquote) {
            if (str_starts_with(trim($blockquote->plaintext), 'Connect with us on')) {
                $blockquote->outertext = '';
            }
        }

        // Headline subtitle
        $content = $content_html->innertext;
        $subtitle = $html->find('.sub-title', 0);
        if ($subtitle) {
            $content = '<p><b>' . $subtitle->plaintext . '</b></p>' . $content;
        }

        // Author
        $author = $html->find('.article-author', 0);
        if ($author && !isset($item['author'])) {
            $item['author'] = trim($author->plaintext);
        }

        $item['content'] = trim($content);
        return $item;
    }

    public function collectData()
    {
        $feed = static::URI . 'feed/';
        $limit = $this->getInput('limit') ?? 10;
        $this->collectExpandableDatas($feed, $limit);
    }
}
