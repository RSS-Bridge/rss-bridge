<?php

class AlternativesEconomiquesBlogsBridge extends BridgeAbstract
{
    const MAINTAINER = 'simounet';
    const NAME = "Les blogs d'Alternatives Economiques";
    const URI = 'https://blogs.alternatives-economiques.fr/';
    const FAVICON = 'https://www.alternatives-economiques.fr/themes/custom/front/favicon.ico';
    const CACHE_TIMEOUT = 4800; //2hours
    const DESCRIPTION = 'Returns the newest articles';

    const BLOG_NAME_PARAMETER = 'blogname';

    const PARAMETERS = [
        [
            self::BLOG_NAME_PARAMETER => [
                'name' => self::BLOG_NAME_PARAMETER,
                'exampleValue' => 'eco-presidentielle',
                'required' => true
            ]
        ]
    ];

    public function getName(): string
    {
        if (!is_null($this->getInput(self::BLOG_NAME_PARAMETER))) {
            return $this->getInput(self::BLOG_NAME_PARAMETER) . ' - ' . self::NAME;
        }

        return parent::getName();
    }

    public function collectData(): void
    {
        $blogname = $this->getInput(self::BLOG_NAME_PARAMETER);
        $baseUrl = self::URI . $blogname;
        $html = getSimpleHTMLDOM($baseUrl);

        foreach ($html->find('article') as $article) {
            $titleElement = $article->find('h2', 0);
            if (!$titleElement) {
                continue;
            }
            $publishedDate = DateTime::createFromFormat('d/m/Y', trim($article->find('time', 0)->plaintext));
            $publishedDate->setTime(0, 0, 0, 0);
            $url = urljoin($baseUrl, $article->find('a', 0)->href);
            $content = getSimpleHTMLDOM($url)->find('.c-blogs-post__body', 0);


            $item = [];
            $item['uri'] = $url;
            $item['title'] = trim($titleElement->plaintext);
            $item['author'] = trim($article->find('.o-infos__author', 0)->plaintext);
            $item['timestamp'] = $publishedDate->getTimestamp();
            $item['content'] = $content;

            $this->items[] = $item;
        }
    }

    public function getIcon(): string
    {
        return self::FAVICON;
    }

    public function getURI(): string
    {
        if (!is_null($this->getInput(self::BLOG_NAME_PARAMETER))) {
            return self::URI . urlencode($this->getInput(self::BLOG_NAME_PARAMETER)) . '/';
        }

        return self::URI;
    }
}
