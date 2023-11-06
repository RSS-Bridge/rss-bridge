<?php

class RaceDepartmentBridge extends FeedExpander
{
    const NAME = 'RaceDepartment News';
    const URI = 'https://racedepartment.com/';
    const DESCRIPTION = 'Get the latest (sim)racing news from RaceDepartment.';
    const MAINTAINER = 't0stiman';

    public function collectData()
    {
        $this->collectExpandableDatas('https://www.racedepartment.com/ams/index.rss', 10);
    }

    protected function parseItem(array $item)
    {
        $articlePage = getSimpleHTMLDOMCached($item['uri']);

        $coverImage = $articlePage->find('img.js-articleCoverImage', 0);
        #relative url -> absolute url
        $coverImage = str_replace('src="/', 'src="' . $this->getURI() . '/', $coverImage);
        $article = $articlePage->find('article.articleBody-main > div.bbWrapper', 0);
        $item['content'] = str_get_html($coverImage . $article);

        //convert iframes to links. meant for embedded videos.
        foreach ($item['content']->find('iframe') as $found) {
            $iframeUrl = $found->getAttribute('src');

            if ($iframeUrl) {
                $found->outertext = '<a href="' . $iframeUrl . '">' . $iframeUrl . '</a>';
            }
        }

        $item['categories'] = [];
        foreach ($articlePage->find('a.tagItem') as $tag) {
            array_push($item['categories'], $tag->innertext);
        }

        return $item;
    }
}
