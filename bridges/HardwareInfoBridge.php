<?php

class HardwareInfoBridge extends FeedExpander
{
    const NAME = 'Hardware Info Bridge';
    const URI = 'https://nl.hardware.info/';
    const DESCRIPTION = 'Tech news from hardware.info (Dutch)';
    const MAINTAINER = 't0stiman';

    public function collectData()
    {
        $this->collectExpandableDatas('https://nl.hardware.info/updates/all.rss', 20);
    }

    protected function parseItem($feedItem)
    {
        $item = parent::parseItem($feedItem);

        //get full article
        $articlePage = getSimpleHTMLDOMCached($feedItem->link);

        $article = $articlePage->find('div.article__content', 0);

        //everything under the social bar is not part of the article, remove it
        $reachedEndOfArticle = false;

        foreach ($article->find('*') as $child) {
            if (
                !$reachedEndOfArticle && isset($child->attr['class'])
                && $child->attr['class'] == 'article__content__social-bar'
            ) {
                $reachedEndOfArticle = true;
            }

            if ($reachedEndOfArticle) {
                $child->outertext = '';
            }
        }

        //get rid of some more elements we don't need
        $to_remove_selectors = [
        'script',
        'div.incontent',
        'div.article__content__social-bar',
        'div#revealNewsTip',
        'div.article__previous_next'
        ];

        foreach ($to_remove_selectors as $selector) {
            foreach ($article->find($selector) as $found) {
                $found->outertext = '';
            }
        }

        // convert iframes to links. meant for embedded YouTube videos.
        foreach ($article->find('iframe') as $found) {
            $iframeUrl = $found->getAttribute('src');

            if ($iframeUrl) {
                $found->outertext = '<a href="' . $iframeUrl . '">' . $iframeUrl . '</a>';
            }
        }

        $item['content'] = $article;
        return $item;
    }
}
