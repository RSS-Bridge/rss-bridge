<?php
class VideoCardzBridge extends BridgeAbstract
{
    const NAME = 'VideoCardz';
    const URI = 'https://videocardz.com/';
    const DESCRIPTION = 'Returns news from VideoCardz.com';
    const MAINTAINER = 'rmscoelho';
    const CACHE_TIMEOUT = 3600; // 5 minutes
    const PARAMETERS = [
        [
            'feed' => [
                'name' => 'News Feed',
                'type' => 'list',
                'title' => 'Feeds from VideoCardz.com',
                'values' => [
                    'News' => 'sections/news',
                    'Featured' => 'sections/featured',
                    'Leak' => 'sections/leak',
                    'Press Releases' => 'sections/press-releases',
                    'Preview Roundup' => 'sections/review-roundup',
                    'Rumour' => 'sections/rumor',
                ]
            ]
        ]
    ];

    public function getIcon()
    {
        return 'https://videocardz.com/favicon-32x32.png?x66580';
    }

    public function getURI()
    {
        switch ($this->queriedContext) {
            case 'feed':
                $url = self::URI . $this->getInput('feed')[0] . '.html';
                break;
            default:
                $url = self::URI;
        }
        return $url;
    }

    public function collectData()
    {
        $url = sprintf('https://videocardz.com/%s', $this->getInput('feed'));
        $dom = getSimpleHTMLDOM($url);
        $dom = $dom->find('.subcategory-news', 0);
        if (!$dom) {
            throw new \Exception(sprintf('Unable to find css selector on `%s`', $url));
        }
        $dom = defaultLinkTo($dom, $this->getURI());

        foreach ($dom->find('article') as $article) {
            $a = $article->find('div.main-index-article-datetitle', 0);

            var_dump($a);

            $this->items[] = [
                'title' => $a->find('a.main-index-article-datetitle-title > h2',0)->plaintext,
                'uri' => $a->find('a.main-index-article-datetitle-title',0)->href,
            ];
        }
    }
}
