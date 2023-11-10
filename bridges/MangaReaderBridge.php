<?php

class MangaReaderBridge extends BridgeAbstract
{
    const NAME = 'MangaReader Bridge';
    const URI = 'https://mangareader.to';
    const DESCRIPTION = 'Fetches the latest chapters from MangaReader.to.';
    const MAINTAINER = 'cubethethird';
    const PARAMETERS = [
        [
            'url' => [
                'name' => 'Manga URL',
                'type' => 'text',
                'required' => true,
                'title' => 'The URL of the manga on MangaReader',
                'pattern' => '^https:\/\/mangareader\.to\/[^\/]+$',
                'exampleValue' => 'https://mangareader.to/bleach-1623',
            ],
            'lang' => [
                'name' => 'Chapter Language',
                'title' => 'two-letter language code (example "en", "jp", "fr")',
                'exampleValue' => 'en',
                'required' => true,
                'pattern' => '^[a-z][a-z]$',
            ]
        ]
    ];

    public function collectData()
    {
        $url = $this->getInput('url');
        $lang = $this->getInput('lang');
        $dom = getSimpleHTMLDOM($url);
        $chapters = $dom->getElementById($lang . '-chapters');

        foreach ($chapters->getElementsByTagName('li') as $chapter) {
            $a = $chapter->getElementsByTagName('a')[0];
            $item = [];
            $item['title'] = $a->getAttribute('title');
            $item['uri'] = self::URI . $a->getAttribute('href');
            $this->items[] = $item;
        }
    }
}
