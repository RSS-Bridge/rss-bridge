<?php

class PanacheDigitalGamesBridge extends BridgeAbstract
{
    const NAME = 'Panache Digital Games';
    const URI = 'https://www.panachedigitalgames.com';
    const DESCRIPTION = 'Panache Digital Games News Blog';
    const MAINTAINER = 'somini';
    const PARAMETERS = [
    ];

    public function getIcon()
    {
        return 'https://www.panachedigitalgames.com/favicon-32x32.png';
    }

    public function getURI()
    {
        return self::URI . '/en/news/';
    }

    public function collectData()
    {
        $articles = $this->getURI();
        $html = getSimpleHTMLDOMCached($articles);

        foreach ($html->find('.news-item') as $element) {
            $item = [];

            $title = $element->find('.news-item-texts-title', 0);
            $link = $element->find('.news-item-texts a', 0);
            $timestamp = $element->find('.news-item-texts-date', 0);

            $item['title'] = $title->plaintext;
            $item['uri'] = self::URI . $link->href;
            $item['timestamp'] = strtotime($timestamp->plaintext);

            $image_html = $element->find('.news-item-thumbnail-image', 0);
            if ($image_html) {
                $image_strings = explode('\'', $image_html);
                /* Debug::log('S: ' . count($image_strings) . '||' . implode('_ _', $image_strings)); */
                if (count($image_strings) == 4) {
                    $item['content'] = '<img src="' . $image_strings[1] . '" />';
                }
            }

            $this->items[] = $item;
        }
    }
}
