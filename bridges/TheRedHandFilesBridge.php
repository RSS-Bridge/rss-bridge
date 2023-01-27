<?php

class TheRedHandFilesBridge extends BridgeAbstract
{
    const NAME = 'The Red Hand Files';
    const URI = 'https://www.theredhandfiles.com';
    const DESCRIPTION = 'The Red Hand Files, a Q&A blog by Nick Cave';
    const MAINTAINER = 'somini';
    /* The feed was available here: 'https://www.theredhandfiles.com/feed/'; */

    public function collectData()
    {
        $html = getSimpleHTMLDOM($this->getURI());

        foreach ($html->find('#main article.posts__article') as $element) {
            $item = [];

            $html_title = $element->find('h2', 0);
            $html_subtitle = $element->find('h3', 0);
            $html_image = $element->find('.posts__article-img', 0);

            $item['title'] = $html_subtitle->plaintext;
            $item['uri'] = $html_title->find('a', 0)->href;
            $item['content'] = $html_image->innertext . $html_title->plaintext;

            $this->items[] = $item;
        }
    }
}
