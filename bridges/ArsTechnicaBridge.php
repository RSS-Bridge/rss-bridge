<?php

class ArsTechnicaBridge extends FeedExpander
{
    const MAINTAINER = 'phantop';
    const NAME = 'Ars Technica';
    const URI = 'https://arstechnica.com/';
    const DESCRIPTION = 'Returns the latest articles from Ars Technica';
    const PARAMETERS = [[
            'section' => [
                'name' => 'Site section',
                'type' => 'list',
                'defaultValue' => 'index',
                'values' => [
                    'All' => 'index',
                    'Apple' => 'apple',
                    'Board Games' => 'cardboard',
                    'Cars' => 'cars',
                    'Features' => 'features',
                    'Gaming' => 'gaming',
                    'Information Technology' => 'technology-lab',
                    'Science' => 'science',
                    'Staff Blogs' => 'staff-blogs',
                    'Tech Policy' => 'tech-policy',
                    'Tech' => 'gadgets',
                    ]
            ]
    ]];

    public function collectData()
    {
        $url = 'https://feeds.arstechnica.com/arstechnica/' . $this->getInput('section');
        $this->collectExpandableDatas($url, 10);
    }

    protected function parseItem(array $item)
    {
        $item_html = getSimpleHTMLDOMCached($item['uri'] . '&amp');
        $item_html = defaultLinkTo($item_html, self::URI);
        $item['content'] = $item_html->find('.amp-wp-article-content', 0);

        // remove various ars advertising
        $item['content']->find('#social-left', 0)->remove();
        foreach ($item['content']->find('.ars-component-buy-box') as $ad) {
            $ad->remove();
        }
        foreach ($item['content']->find('i-amphtml-sizer') as $ad) {
            $ad->remove();
        }
        foreach ($item['content']->find('.sidebar') as $ad) {
            $ad->remove();
        }

        foreach ($item['content']->find('a') as $link) { //remove amp redirect links
            $url = $link->getAttribute('href');
            if (str_contains($url, 'go.redirectingat.com')) {
                $url = extractFromDelimiters($url, 'url=', '&amp');
                $url = urldecode($url);
                $link->setAttribute('href', $url);
            }
        }

        $item['content'] = backgroundToImg(str_replace('data-amp-original-style="background-image', 'style="background-image', $item['content']));

        $item['uid'] = explode('=', $item['uri'])[1];

        return $item;
    }
}
