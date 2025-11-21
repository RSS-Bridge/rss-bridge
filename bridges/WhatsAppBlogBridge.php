<?php

declare(strict_types=1);

class WhatsAppBlogBridge extends BridgeAbstract
{
    const NAME = 'WhatsApp Blog';
    const URI = 'https://blog.whatsapp.com/';
    const DESCRIPTION = 'WhatsApp Blog';
    const MAINTAINER = 'latz';
    const PARAMETERS = [[
        'language' => [
            'name' => 'Language',
            'title' => 'ISO 639 language code',
            'type' => 'text',
            'required' => false,
            'defaultValue' => 'en',
        ],
    ]];
    const CACHE_TIMEOUT = 3600; // 1h

    public function collectData()
    {
        $html = getSimpleHTMLDOMCached('https://blog.whatsapp.com/?lang=' . $this->getInput('language'));

        // extract React HTML snippets from JavaScript
        foreach ($html->find('script') as $script) {
            $htmlSnippetPattern = '/\{"__html":".*"\}/U';
            if (preg_match_all($htmlSnippetPattern, $script->innertext, $htmlSnippets)) {
                foreach ($htmlSnippets[0] as $snippet) {
                    $decoded = json_decode($snippet, false)->__html;
                    $parsed = str_get_html($decoded); // this is the parsed HTML snippet

                    $content = $parsed->find('section', 0);
                    if ($content) {
                        // remove share buttons
                        $content->find('._9wj7', 0)->remove();
                        // remove "learn more" link
                        $content->find('a._ajcm', 0)->remove();

                        $item = [];

                        $timestampStr = $content->find('._aof4 p', 0);
                        if ($timestampStr) {
                            $timestamp = strtotime($timestampStr->plaintext);
                            $item['timestamp'] = $timestamp;
                        }

                        $title = $content->find('h2', 0);
                        if ($title) {
                            $item['title'] = $title->plaintext;
                        }

                        $links = $content->find('a');
                        $uri = end($links);
                        if ($uri) {
                            $item['uri'] = $uri->href;
                        }

                        $item['content'] = implode('', array_map(fn($e) => $e->outertext, $content->find('._aofe, picture')));

                        $this->items[] = $item;
                    }
                }
            }
        }
    }
}
