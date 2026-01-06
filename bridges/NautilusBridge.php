<?php

class NautilusBridge extends FeedExpander
{
    const MAINTAINER = 'sqrtminusone';
    const NAME = 'Nautilus Bridge';
    const URI = 'https://nautil.us/';
    const DESCRIPTION = 'Returns the latest articles from Nautilus.';

    const PARAMETERS = [
        '' => [
            'limit' => [
                'name' => 'Feed Item Limit',
                'required' => true,
                'type' => 'number',
                'defaultValue' => 10,
                'title' => 'Maximum number of returned feed items. Default 10'
            ],
        ],
    ];

    public function collectData()
    {
        $this->collectExpandableDatas(self::URI . 'feed', (int)$this->getInput('limit'));
    }

    protected function parseItem(array $item)
    {
        $content = '';

        $dom = getSimpleHTMLDOMCached($item['uri'], 7 * 24 * 60 * 60);
        $feature_image = $dom->find('img.article-banner-img', 0);
        if ($feature_image) {
            $src = $feature_image->getAttribute('src');
            $content .= '<figure><img src="' . $src . '"></figure>';
        }

        $article_main = $dom->find('div.article-content', 0);

        // Mostly YouTube videos
        $iframes = $article_main->find('iframe');
        foreach ($iframes as $iframe) {
            $iframe->outertext = '<a href="' . $iframe->src . '">' . $iframe->src . '</a>';
        }

        $article_main = defaultLinkTo($article_main, self::URI);

        $ads = $article_main->find('div.article-ad');
        foreach ($ads as $ad) {
            $ad->parent->removeChild($ad);
        }
        $ads = $article_main->find('div.primis-ad');
        foreach ($ads as $ad) {
            $ad->parent->removeChild($ad);
        }
        $blocks = $article_main->find('div.article-collection_box');
        foreach ($blocks as $block) {
            $block->parent->removeChild($block);
        }
        $content .= $article_main->innertext;

        $item['content'] = $content;
        return $item;
    }
}
