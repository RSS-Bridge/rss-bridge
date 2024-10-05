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
        $item_html = getSimpleHTMLDOMCached($item['uri']);
        $parsely = $item_html->find('[name="parsely-page"]', 0)->content;
        $parsely_json = Json::decode(html_entity_decode($parsely));

        $item['categories'] = $parsely_json['tags'];
        $item['comments'] = $item_html->find('#comments a', 0)->href;
        $item['content'] = '';
        foreach ($item_html->find('.post-content') as $content) {
            $item['content'] .= $content;
        }

        $item['content'] = backgroundToImg($item['content']);

        // remove various ars advertising
        $sel = '#social-left, .ars-component-buy-box, .ad_wrapper, .sidebar, .toc-container, .ars-gallery-caption-arrow';
        foreach ($item['content']->find($sel) as $ad) {
            $ad->remove();
        }

        return $item;
    }
}
