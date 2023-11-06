<?php

class BleepingComputerBridge extends FeedExpander
{
    const MAINTAINER = 'csisoap';
    const NAME = 'Bleeping Computer';
    const URI = 'https://www.bleepingcomputer.com/';
    const DESCRIPTION = 'Returns the newest articles.';

    public function collectData()
    {
        $feed = static::URI . 'feed/';
        $this->collectExpandableDatas($feed);
    }

    protected function parseItem(array $item)
    {
        $article_html = getSimpleHTMLDOMCached($item['uri']);
        if (!$article_html) {
            $item['content'] .= '<p><em>Could not request ' . $this->getName() . ': ' . $item['uri'] . '</em></p>';
            return $item;
        }

        $article_content = $article_html->find('div.articleBody', 0)->innertext;
        $article_content = stripRecursiveHTMLSection($article_content, 'div', '<div class="cz-related-article-wrapp');
        $item['content'] = trim($article_content);

        return $item;
    }
}
