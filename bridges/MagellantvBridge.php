<?php

class MagellantvBridge extends BridgeAbstract
{
    const NAME = 'Magellantv articles';
    const URI = 'https://www.magellantv.com/articles';
    const DESCRIPTION = 'Articles of the documentery streaming service Magellantv';
    const MAINTAINER = 'Vincentvd';
    const CACHE_TIMEOUT = 60; // 15 minutes
    const PARAMETERS = [
        [
            'topic' => [
                'type' => 'list',
                'name' => 'Article topic',
                'values' => [
                    'All topics' => 'all',
                    'Ancient history' => 'ancient-history',
                    'Art & culture' => 'art-culture',
                    'Biography' => 'biography',
                    'Current history' => 'current-history',
                    'Early modern' => 'early-modern',
                    'Earth' => 'earth',
                    'Mind & body' => 'mind-body',
                    'Nature' => 'nature',
                    'Science & tech' => 'science-tech',
                    'Short takes' => 'short-takes',
                    'Space' => 'space',
                    'Travel & adventure' => 'travel-adventure',
                    'True crime' => 'true-crime',
                    'War & military' => 'war-military'
                ],
            ]
        ]
    ];

    public function getIcon()
    {
        return 'https://www.magellantv.com/favicon-32x32.png';
    }

    private function retrieveTags($article)
    {
        // Retrieve all tags from an article and store in array
        $article_tags_list = $article->find('div.articleCategory_article-category-tag__uEAXz > a');
        $tags = [];
        foreach ($article_tags_list as $tag) {
            array_push($tags, $tag->plaintext);
        }

        return $tags;
    }

    public function collectData()
    {
        // Determine URL based on topic
        $topic = $this->getInput('topic');
        if ($topic == 'all') {
            $url = 'https://www.magellantv.com/articles';
        } else {
            $url = sprintf('https://www.magellantv.com/articles/category/%s', $topic);
        }
        $dom = getSimpleHTMLDOM($url);

        // Check whether items exists
        $article_list = $dom->find('div.articlePreview_preview-card__mLMOm');
        if (count($article_list) == 0) {
            throw new Exception(sprintf('Unable to find css selector on `%s`', $url));
        }

        // Loop over each article and store article information
        foreach ($article_list as $article) {
            $article = defaultLinkTo($article, $this->getURI());
            $meta_information = $article->find('div.articlePreview_article-metas__kD1i7', 0);
            $title = $article->find('div.articlePreview_article-title___Ci5V > h2 > a', 0);
            $tags_list = $this->retrieveTags($article);

            $item = [
                'title' => $title->plaintext,
                'uri' => $title->href,
                'timestamp' => strtotime($meta_information->find('div.articlePreview_article-date__8Jyfn', 0)->plaintext),
                'author' => $meta_information->find('div.articlePreview_article-author__Ie0_u > span', 1)->plaintext,
                'categories' => $tags_list
            ];

            $this->items[] = $item;
        }
    }
}
