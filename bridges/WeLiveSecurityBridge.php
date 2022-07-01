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

    protected function parseItem($item)
    {
        $item = parent::parseItem($item);

        $article_html = getSimpleHTMLDOMCached($item['uri']);
        if (!$article_html) {
            $item['content'] .= '<p><em>Could not request ' . $this->getName() . ': ' . $item['uri'] . '</em></p>';
            return $item;
        }

        $article_content = $article_html->find('div.formatted', 0)->innertext;
        $article_content = stripWithDelimiters($article_content, '<script', '</script>');
        $article_content = stripRecursiveHTMLSection($article_content, 'div', '<div class="comments');
        $article_content = stripRecursiveHTMLSection($article_content, 'div', '<div class="similar-articles');
        $article_content = stripRecursiveHTMLSection($article_content, 'span', '<span class="meta');
        $item['content'] = trim($article_content);

        return $item;
    }

    public function collectData()
    {
        $feed = static::URI . 'feed/';
        $limit = $this->getInput('limit') ?? 10;
        $this->collectExpandableDatas($feed, $limit);
    }
}
