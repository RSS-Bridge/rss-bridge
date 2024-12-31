<?php

class BlizzardNewsBridge extends BridgeAbstract
{
    const NAME = 'Blizzard News';
    const URI = 'https://news.blizzard.com';
    const DESCRIPTION = 'Blizzard (game company) newsfeed';
    const MAINTAINER = 'Niehztog';
    const PARAMETERS = [
        '' => [
            'locale' => [
                'name' => 'Language',
                'type' => 'list',
                'values' => [
                    'Deutsch' => 'de-de',
                    'English (EU)' => 'en-gb',
                    'English (US)' => 'en-us',
                    'Español (EU)' => 'es-es',
                    'Español (AL)' => 'es-mx',
                    'Français' => 'fr-fr',
                    'Italiano' => 'it-it',
                    '日本語' => 'ja-jp',
                    '한국어' => 'ko-kr',
                    'Polski' => 'pl-pl',
                    'Português (AL)' => 'pt-br',
                    'Русский' => 'ru-ru',
                    'ภาษาไทย' => 'th-th',
                    '简体中文' => 'zh-cn',
                    '繁體中文' => 'zh-tw'
                ],
                'defaultValue' => 'en-us',
                'title' => 'Select your language'
            ]
        ]
    ];
    const CACHE_TIMEOUT = 3600;

    private const PRODUCT_IDS = [
        'blt525c436e4a1b0a97',
        'blt54fbd3787a705054',
        'blt2031aef34200656d',
        'blt795c314400d7ded9',
        'blt5cfc6affa3ca0638',
        'blt2e50e1521bb84dc6',
        'blt376fb94931906b6f',
        'blt81d46fcb05ab8811',
        'bltede2389c0a8885aa',
        'blt24859ba8086fb294',
        'blte27d02816a8ff3e1',
        'blt2caca37e42f19839',
        'blt90855744d00cd378',
        'bltec70ad0ea4fd6d1d',
        'blt500c1f8b5470bfdb'
    ];

    private const API_PATH = '/api/news/blizzard?';

    /**
     * Source Web page URL (should provide either HTML or XML content)
     * @return string
     */
    private function getSourceUrl(): string
    {
        $locale = $this->getInput('locale');
        if ('zh-cn' === $locale) {
            $baseUrl = 'https://cn.news.blizzard.com' . self::API_PATH;
        } else {
            $baseUrl = 'https://news.blizzard.com/' . $locale . self::API_PATH;
        }
        return $baseUrl .= http_build_query([
            'feedCxpProductIds' => self::PRODUCT_IDS
        ]);
    }

    public function collectData()
    {
        $feedContent = json_decode(getContents($this->getSourceUrl()), true);

        foreach ($feedContent['feed']['contentItems'] as $entry) {
            $properties = $entry['properties'];

            $item = [];

            $item['title'] = $this->filterChars($properties['title']);
            $item['content'] = $this->filterChars($properties['summary']);
            $item['uri'] = $properties['newsUrl'];
            $item['author'] = $this->filterChars($properties['author']);
            $item['timestamp'] = strtotime($properties['lastUpdated']);
            $item['enclosures'] = [$properties['staticAsset']['imageUrl']];
            $item['categories'] = [$this->filterChars($properties['cxpProduct']['title'])];

            $this->items[] = $item;
        }
    }

    private function filterChars($content)
    {
        return htmlspecialchars($content, ENT_XML1);
    }

    public function getIcon()
    {
        return <<<icon
https://dfbmfbnnydoln.cloudfront.net/production/images/favicons/favicon.ba01bb119359d74970b02902472fd82e96b5aba7.ico
icon;
    }
}
