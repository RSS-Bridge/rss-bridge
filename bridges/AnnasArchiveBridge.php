<?php

class AnnasArchiveBridge extends BridgeAbstract
{
    const NAME = 'Anna\'s Archive';
    const MAINTAINER = 'phantop';
    const URI = 'https://annas-archive.org/';
    const DESCRIPTION = 'Returns books from Anna\'s Archive';
    const PARAMETERS = [
        [
            'q' => [
                'name' => 'Query',
                'exampleValue' => 'apothecary diaries',
                'required' => true,
            ],
            'ext' => [
                'name' => 'Extension',
                'type' => 'list',
                'values' => [
                    'Any' => null,
                    'azw3' => 'azw3',
                    'cbr' => 'cbr',
                    'cbz' => 'cbz',
                    'djvu' => 'djvu',
                    'epub' => 'epub',
                    'fb2' => 'fb2',
                    'fb2.zip' => 'fb2.zip',
                    'mobi' => 'mobi',
                    'pdf' => 'pdf',
                ]
            ],
            'lang' => [
                'name' => 'Language',
                'type' => 'list',
                'values' => [
                    'Any' => null,
                    'Afrikaans [af]' => 'af',
                    'Arabic [ar]' => 'ar',
                    'Bangla [bn]' => 'bn',
                    'Belarusian [be]' => 'be',
                    'Bulgarian [bg]' => 'bg',
                    'Catalan [ca]' => 'ca',
                    'Chinese [zh]' => 'zh',
                    'Church Slavic [cu]' => 'cu',
                    'Croatian [hr]' => 'hr',
                    'Czech [cs]' => 'cs',
                    'Danish [da]' => 'da',
                    'Dongxiang [sce]' => 'sce',
                    'Dutch [nl]' => 'nl',
                    'English [en]' => 'en',
                    'French [fr]' => 'fr',
                    'German [de]' => 'de',
                    'Greek [el]' => 'el',
                    'Hebrew [he]' => 'he',
                    'Hindi [hi]' => 'hi',
                    'Hungarian [hu]' => 'hu',
                    'Indonesian [id]' => 'id',
                    'Irish [ga]' => 'ga',
                    'Italian [it]' => 'it',
                    'Japanese [ja]' => 'ja',
                    'Kazakh [kk]' => 'kk',
                    'Korean [ko]' => 'ko',
                    'Latin [la]' => 'la',
                    'Latvian [lv]' => 'lv',
                    'Lithuanian [lt]' => 'lt',
                    'Luxembourgish [lb]' => 'lb',
                    'Ndolo [ndl]' => 'ndl',
                    'Norwegian [no]' => 'no',
                    'Persian [fa]' => 'fa',
                    'Polish [pl]' => 'pl',
                    'Portuguese [pt]' => 'pt',
                    'Romanian [ro]' => 'ro',
                    'Russian [ru]' => 'ru',
                    'Serbian [sr]' => 'sr',
                    'Spanish [es]' => 'es',
                    'Swedish [sv]' => 'sv',
                    'Tamil [ta]' => 'ta',
                    'Traditional Chinese [zh‑Hant]' => 'zh‑Hant',
                    'Turkish [tr]' => 'tr',
                    'Ukrainian [uk]' => 'uk',
                    'Unknown language' => '_empty',
                    'Unknown language [und]' => 'und',
                    'Unknown language [urdu]' => 'urdu',
                    'Urdu [ur]' => 'ur',
                    'Vietnamese [vi]' => 'vi',
                    'Welsh [cy]' => 'cy',
                ]
            ],
            'content' => [
                'name' => 'Type',
                'type' => 'list',
                'values' => [
                    'Any' => null,
                    'Book (fiction)' => 'book_fiction',
                    'Book (non‑fiction)' => 'book_nonfiction',
                    'Book (unknown)' => 'book_unknown',
                    'Comic book' => 'book_comic',
                    'Journal article' => 'journal_article',
                    'Magazine' => 'magazine',
                    'Standards document' => 'standards_document',
                ]
            ],
            'src' => [
                'name' => 'Source',
                'type' => 'list',
                'values' => [
                    'Any' => null,
                    'Internet Archive' => 'ia',
                    'Libgen.li' => 'lgli',
                    'Libgen.rs' => 'lgrs',
                    'Sci‑Hub' => 'scihub',
                    'Z‑Library' => 'zlib',
                ]
            ],
        ]
    ];

    public function collectData()
    {
        $url = $this->getURI();
        $list = getSimpleHTMLDOMCached($url);
        $list = defaultLinkTo($list, self::URI);

        // Don't attempt to do anything if not found message is given
        if ($list->find('.js-not-found-additional')) {
            return;
        }

        $elements = $list->find('#aarecord-list > div');
        foreach ($elements as $element) {
            // stop added entries once partial match list starts
            if (str_contains($element->innertext, 'partial match')) {
                break;
            }
            if ($element = $element->find('a', 0)) {
                $item = [];
                $item['title'] = $element->find('h3', 0)->plaintext;
                $item['author'] = $element->find('div.italic', 0)->plaintext;
                $item['uri'] = $element->href;
                $item['content'] = $element->plaintext;
                $item['uid'] = $item['uri'];

                $item_html = getSimpleHTMLDOMCached($item['uri'], 86400 * 20);
                if ($item_html) {
                    $item_html = defaultLinkTo($item_html, self::URI);
                    $item['content'] .= $item_html->find('main img', 0);
                    $item['content'] .= $item_html->find('main .mt-4', 0); // Summary
                    foreach ($item_html->find('main ul.mb-4 > li > a.js-download-link') as $file) {
                        if (!str_contains($file->href, 'fast_download')) {
                            $item['enclosures'][] = $file->href;
                        }
                    }
                    // Remove bulk torrents from enclosures list
                    $item['enclosures'] = array_diff($item['enclosures'], [self::URI . 'datasets']);
                }

                $this->items[] = $item;
            }
        }
    }

    public function getName()
    {
        $name = parent::getName();
        if ($this->getInput('q') != null) {
            $name .= ' - ' . $this->getInput('q');
        }
        return $name;
    }

    public function getURI()
    {
        $params = array_filter([ // Filter to remove non-provided parameters
            'q' => $this->getInput('q'),
            'ext' => $this->getInput('ext'),
            'lang' => $this->getInput('lang'),
            'src' => $this->getInput('src'),
            'content' => $this->getInput('content'),
        ]);
        $url = parent::getURI() . 'search?sort=newest&' . http_build_query($params);
        return $url;
    }
}
