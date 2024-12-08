<?php

class HarvardHealthBlogBridge extends BridgeAbstract
{
    const NAME = 'Harvard Health Blog';
    const URI = 'https://www.health.harvard.edu/blog';
    const DESCRIPTION = 'Retrieve articles from health.harvard.edu';
    const MAINTAINER = 'tillcash';
    const MAX_ARTICLES = 10;
    const PARAMETERS = [
        [
            'image' => [
                'name' => 'Article Image',
                'type' => 'checkbox',
                'defaultValue' => 'checked',
            ],
        ],
    ];

    public function collectData()
    {
        $dom = getSimpleHTMLDOM(self::URI);
        $count = 0;

        foreach ($dom->find('div[class="mb-16 md:flex"]') as $element) {
            if ($count >= self::MAX_ARTICLES) {
                break;
            }

            $data = $element->find('a[class="hover:text-red transition-colors duration-200"]', 0);
            if (!$data) {
                continue;
            }

            $url = $data->href;

            $this->items[] = [
                'content'   => $this->constructContent($url),
                'timestamp' => $element->find('time', 0)->datetime,
                'title'     => $data->plaintext,
                'uid'       => $url,
                'uri'       => $url,
            ];

            $count++;
        }
    }

    private function constructContent($url)
    {
        $dom = getSimpleHTMLDOMCached($url);

        $article = $dom->find('div[class*="content-repository-content"]', 0);
        if (!$article) {
            return 'Content Not Found';
        }

        // remove article image
        if (!$this->getInput('image')) {
            $image = $article->find('p', 0);
            $image->remove();
        }

        // remove ads
        foreach ($article->find('.inline-ad') as $ad) {
            $ad->outertext = '';
        }

        return $article->innertext;
    }
}
