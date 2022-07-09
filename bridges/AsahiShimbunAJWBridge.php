<?php

class AsahiShimbunAJWBridge extends BridgeAbstract
{
    const NAME = 'Asahi Shimbun AJW';
    const BASE_URI = 'http://www.asahi.com';
    const URI = self::BASE_URI . '/ajw/';
    const DESCRIPTION = 'Asahi Shimbun - Asia & Japan Watch';
    const MAINTAINER = 'somini';
    const PARAMETERS = [
        [
            'section' => [
                'type' => 'list',
                'name' => 'Section',
                'values' => [
                    'Japan » Social Affairs' => 'japan/social',
                    'Japan » People' => 'japan/people',
                    'Japan » 3/11 Disaster' => 'japan/0311disaster',
                    'Japan » Sci & Tech' => 'japan/sci_tech',
                    'Politics' => 'politics',
                    'Business' => 'business',
                    'Culture » Style' => 'culture/style',
                    'Culture » Movies' => 'culture/movies',
                    'Culture » Manga & Anime' => 'culture/manga_anime',
                    'Asia » China' => 'asia_world/china',
                    'Asia » Korean Peninsula' => 'asia_world/korean_peninsula',
                    'Asia » Around Asia' => 'asia_world/around_asia',
                    'Asia » World' => 'asia_world/world',
                    'Opinion » Editorial' => 'opinion/editorial',
                    'Opinion » Vox Populi' => 'opinion/vox',
                ],
                'defaultValue' => 'politics',
            ]
        ]
    ];

    private function getSectionURI($section)
    {
        return $this->getURI() . $section . '/';
    }

    public function collectData()
    {
        $html = getSimpleHTMLDOM($this->getSectionURI($this->getInput('section')));

        foreach ($html->find('#MainInner li a') as $element) {
            if ($element->parent()->class == 'HeadlineTopImage-S') {
                Debug::log('Skip Headline, it is repeated below');
                continue;
            }
            $item = [];

            $item['uri'] = self::BASE_URI . $element->href;
            $e_lead = $element->find('span.Lead', 0);
            if ($e_lead) {
                $item['content'] = $e_lead->innertext;
                $e_lead->outertext = '';
            } else {
                $item['content'] = $element->innertext;
            }
            $e_date = $element->find('span.EnDate', 0);
            if ($e_date) {
                $item['timestamp'] = strtotime($e_date->innertext);
                $e_date->outertext = '';
            }
            $e_video = $element->find('span.EnVideo', 0);
            if ($e_video) {
                $e_video->outertext = '';
                $element->innertext = "VIDEO: $element->innertext";
            }
            $item['title'] = $element->innertext;

            $this->items[] = $item;
        }
    }
}
