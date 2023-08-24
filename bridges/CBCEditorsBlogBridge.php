<?php

class CBCEditorsBlogBridge extends BridgeAbstract
{
    const MAINTAINER = 'quickwick';
    const NAME = 'CBC Editors Blog';
    const URI = 'https://www.cbc.ca/news/editorsblog';
    const DESCRIPTION = 'Recent CBC Editor\'s Blog posts';

    public function collectData()
    {
        $html = getSimpleHTMLDOM(self::URI);

        // Loop on each blog post entry
        foreach ($html->find('div.contentListCards', 0)->find('a[data-test=type-story]') as $element) {
            $headline = ($element->find('.headline', 0))->innertext;
            $timestamp = ($element->find('time', 0))->datetime;
            $articleUri = 'https://www.cbc.ca' . $element->href;
            $summary = ($element->find('div.description', 0))->innertext;
            $thumbnailUris = ($element->find('img[loading=lazy]', 0))->srcset;
            $thumbnailUri = rtrim(explode(',', $thumbnailUris)[0], ' 300w');

            // Fill item
            $item = [];
            $item['uri'] = $articleUri;
            $item['id'] = $item['uri'];
            $item['timestamp'] = $timestamp;
            $item['title'] = $headline;
            $item['content'] = '<img src="'
            . $thumbnailUri . '" /><br>' . $summary;
            $item['author'] = 'Editor\'s Blog';

            if (isset($item['title'])) {
                $this->items[] = $item;
            }
        }
    }
}
