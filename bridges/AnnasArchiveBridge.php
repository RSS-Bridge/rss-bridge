<?php

class AnnasArchiveBridge extends BridgeAbstract
{
    const NAME = 'Anna\'s Archive';
    const MAINTAINER = 'APT37';
    const URI = 'https://annas-archive.gl/';
    const DESCRIPTION = 'Returns books from Anna\'s Archive';
    const PARAMETERS = [
        [
            'q' => [
                'name' => 'Query',
                'exampleValue' => 'Against Intellectual Monopoly',
                'required' => true,
            ],
            'ext' => [
                'name' => 'Extension',
                'type' => 'list',
                'values' => [
                    'Any' => null,
                    'pdf' => 'pdf',
                    'epub' => 'epub',
                    'zip' => 'zip',
                    'mobi' => 'mobi',
                    'cbr' => 'cbr',
                    'cbz' => 'cbz',
                    'txt' => 'txt',
                    'djvu' => 'djvu',
                    'doc' => 'doc',
                    'lit' => 'lit',
                    'rtf' => 'rtf',
                    'rar' => 'rar',
                    'htm' => 'htm',
                    'html' => 'html',
                    'docx' => 'docx',
                    'mht' => 'mht',
                    'lrf' => 'lrf',
                    'jpg' => 'jpg',
                    'chm' => 'chm',
                    'azw' => 'azw',
                    'pdb' => 'pdb',
                    'odt' => 'odt',
                    'ppt' => 'ppt',
                    'kfx' => 'kfx',
                    'prc' => 'prc',
                    'xls' => 'xls',
                    'xlsx' => 'xlsx',
                    'json' => 'json',
                    'tar' => 'tar',
                    'tif' => 'tif',
                    'snb' => 'snb',
                    'updb' => 'updb',
                    'htmlz' => 'htmlz',
                    'gz' => 'gz',
                    'pptx' => 'pptx',
                    'png' => 'png',
                    'exe' => 'exe',
                    'ai' => 'ai',
                ]
            ],
            'lang' => [
                'name' => 'Language',
                'type' => 'list',
                'values' => [
                    'Any' => null,
                    'Afrikaans' => 'af',
                    'Albanian' => 'sq',
                    'Amharic' => 'am',
                    'Arabic' => 'ar',
                    'Armenian' => 'hy',
                    'Azerbaijani' => 'az',
                    'Bangla' => 'bn',
                    'Bashkir' => 'ba',
                    'Basque' => 'eu',
                    'Belarusian' => 'be',
                    'Bosnian' => 'bs',
                    'Bulgarian' => 'bg',
                    'Burmese' => 'my',
                    'Catalan' => 'ca',
                    'Central Kurdish' => 'ckb',
                    'Chinese' => 'zh',
                    'Croatian' => 'hr',
                    'Czech' => 'cs',
                    'Danish' => 'da',
                    'Dutch' => 'nl',
                    'English' => 'en',
                    'Esperanto' => 'eo',
                    'Estonian' => 'et',
                    'Filipino' => 'fil',
                    'Finnish' => 'fi',
                    'French' => 'fr',
                    'Galician' => 'gl',
                    'Georgian' => 'ka',
                    'German' => 'de',
                    'Greek' => 'el',
                    'Gujarati' => 'gu',
                    'Haitian Creole' => 'ht',
                    'Hausa' => 'ha',
                    'Hebrew' => 'he',
                    'Hindi' => 'hi',
                    'Hungarian' => 'hu',
                    'Indonesian' => 'id',
                    'Irish' => 'ga',
                    'Italian' => 'it',
                    'Japanese' => 'ja',
                    'Javanese' => 'jv',
                    'Kannada' => 'kn',
                    'Kazakh' => 'kk',
                    'Kinyarwanda' => 'rw',
                    'Korean' => 'ko',
                    'Kurdish' => 'ku',
                    'Kyrgyz' => 'ky',
                    'Lao' => 'lo',
                    'Latin' => 'la',
                    'Latvian' => 'lv',
                    'Lithuanian' => 'lt',
                    'Macedonian' => 'mk',
                    'Malagasy' => 'mg',
                    'Malayalam' => 'ml',
                    'Malay' => 'ms',
                    'Marathi' => 'mr',
                    'Mongolian' => 'mn',
                    'Nepali' => 'ne',
                    'Norwegian Bokmål' => 'nb',
                    'Norwegian' => 'no',
                    'Nyanja' => 'ny',
                    'Oromo' => 'om',
                    'Pashto' => 'ps',
                    'Persian' => 'fa',
                    'Polish' => 'pl',
                    'Portuguese' => 'pt',
                    'Punjabi' => 'pa',
                    'Quechua' => 'qu',
                    'Romanian' => 'ro',
                    'Russian' => 'ru',
                    'Serbian' => 'sr',
                    'Shan' => 'shn',
                    'Shona' => 'sn',
                    'Sindhi' => 'sd',
                    'Sinhala' => 'si',
                    'Slovak' => 'sk',
                    'Slovenian' => 'sl',
                    'Somali' => 'so',
                    'Spanish' => 'es',
                    'Sundanese' => 'su',
                    'Swahili' => 'sw',
                    'Swedish' => 'sv',
                    'Tajik' => 'tg',
                    'Tamil' => 'ta',
                    'Tatar' => 'tt',
                    'Telugu' => 'te',
                    'Thai' => 'th',
                    'Tibetan' => 'bo',
                    'Traditional Chinese' => 'zh‑Hant',
                    'Turkish' => 'tr',
                    'Ukrainian' => 'uk',
                    'Urdu' => 'ur',
                    'Uyghur' => 'ug',
                    'Uzbek' => 'uz',
                    'Vietnamese' => 'vi',
                    'Welsh' => 'cy',
                    'Western Frisian' => 'fy',
                    'Xhosa' => 'xh',
                    'Yiddish' => 'yi',
                    'Zulu' => 'zu',
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
                    'Other' => 'other',
                ]
            ],
            'src' => [
                'name' => 'Source',
                'type' => 'list',
                'values' => [
                    'Any' => null,
                    'DuXiu 读秀' => 'duxiu',
                    'HathiTrust' => 'hathi',
                    'Internet Archive' => 'ia',
                    'Libgen.li' => 'lgli',
                    'Libgen.rs' => 'lgrs',
                    'MagzDB' => 'magzdb',
                    'Nexus/STC' => 'nexusstc',
                    'Sci‑Hub' => 'scihub',
                    'Upload to AA' => 'upload',
                    'Z‑Library' => 'zlib',
                    'Z‑Library Chinese' => 'zlibzh',
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
