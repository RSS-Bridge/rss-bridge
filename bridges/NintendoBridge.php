<?php

class NintendoBridge extends XPathAbstract
{
    const NAME = 'Nintendo Software Updates';
    const URI = 'https://www.nintendo.co.uk/Support/Welcome-to-Nintendo-Support-11593.html';
    const DONATION_URI = '';
    const DESCRIPTION = self::NAME;
    const MAINTAINER = 'Niehztog';
    const PARAMETERS = [
        '' => [
            'category' => [
                'name' => 'Category',
                'type' => 'list',
                'values' => [
                    'All' => 'all',
                    'Mario Kart 8 Deluxe' => 'mk8d',
                    'Splatoon 2' => 's2',
                    'Super Mario 3D All-Stars' => 'sm3as',
                    'Super Mario 3D World + Bowser’s Fury' => 'sm3wbf',
                    'Super Mario Bros. Wonder' => 'smbw',
                    'Super Mario Maker 2' => 'smm2',
                    'Super Mario Odyssey' => 'smo',
                    'Super Smash Bros. Ultimate' => 'ssbu',
                    'Switch Firmware' => 'sf',
                    'The Legend of Zelda: Link’s Awakening' => 'tlozla',
                    'The Legend of Zelda: Skyward Sword HD' => 'tlozss',
                    'The Legend of Zelda: Tears of the Kingdom' => 'tloztotk',
                    'Xenoblade Chronicles 2' => 'xc2',
                ],
                'defaultValue' => 'mk8d',
                'title' => 'Select category'
            ],
            'country' => [
                'name' => 'Country',
                'type' => 'list',
                'values' => [
                    'België' => 'be/nl',
                    'Belgique' => 'be/fr',
                    'Deutschland' => 'de',
                    'España' => 'es',
                    'France' => 'fr',
                    'Italia' => 'it',
                    'Nederland' => 'nl',
                    'Österreich' => 'at',
                    'Portugal' => 'pt',
                    'Schweiz' => 'ch/de',
                    'Suisse' => 'ch/fr',
                    'Svizzera' => 'ch/it',
                    'UK & Ireland' => 'co.uk',
                    'South Africa' => 'co.za'
                ],
                'defaultValue' => 'co.uk',
                'title' => 'Select your country'
            ]
        ]
    ];

    const CACHE_TIMEOUT = 3600;

    const FEED_SOURCE_URL = [
        'mk8d' => 'https://www.nintendo.co.uk/Support/Nintendo-Switch/Game-Updates/How-to-Update-Mario-Kart-8-Deluxe-1482895.html',
        's2' => 'https://www.nintendo.co.uk/Support/Nintendo-Switch/Game-Updates/How-to-Update-Splatoon-2-1482897.html',
        'sm3as' => 'https://www.nintendo.co.uk/Support/Nintendo-Switch/Game-Updates/How-to-Update-Super-Mario-3D-All-Stars-1844226.html',
        'sm3wbf' => 'https://www.nintendo.co.uk/Support/Nintendo-Switch/Game-Updates/How-to-Update-Super-Mario-3D-World-Bowser-s-Fury-1920668.html',
        'smbw' => 'https://www.nintendo.co.uk/Support/Nintendo-Switch/Game-Updates/How-to-Update-Super-Mario-Bros-Wonder-2485410.html',
        'smm2' => 'https://www.nintendo.co.uk/Support/Nintendo-Switch/Game-Updates/How-to-Update-Super-Mario-Maker-2-1586745.html',
        'smo' => 'https://www.nintendo.co.uk/Support/Nintendo-Switch/Game-Updates/How-to-Update-Super-Mario-Odyssey-1482901.html',
        'ssbu' => 'https://www.nintendo.co.uk/Support/Nintendo-Switch/Game-Updates/How-to-Update-Super-Smash-Bros-Ultimate-1484130.html',
        'sf' => 'https://www.nintendo.co.uk/Support/Nintendo-Switch/System-Updates/Nintendo-Switch-System-Updates-and-Change-History-1445507.html',
        'tlozla' => 'https://www.nintendo.co.uk/Support/Nintendo-Switch/Game-Updates/How-to-Update-The-Legend-of-Zelda-Link-s-Awakening-1666739.html',
        'tlozss' => 'https://www.nintendo.co.uk/Support/Nintendo-Switch/Game-Updates/How-to-Update-The-Legend-of-Zelda-Skyward-Sword-HD-2022801.html',
        'tloztotk' => 'https://www.nintendo.co.uk/Support/Nintendo-Switch/Game-Updates/How-to-Update-The-Legend-of-Zelda-Tears-of-the-Kingdom-2388231.html',
        'xc2' => 'https://www.nintendo.co.uk/Support/Nintendo-Switch/Game-Updates/Xenoblade-Chronicles-2-Update-History-1482911.html',
    ];
    const XPATH_EXPRESSION_ITEM = '//div[@class="col-xs-12 content"]/div[starts-with(@id,"v") and @class="collapse"]';
    const XPATH_EXPRESSION_ITEM_FIRMWARE = '//div[@id="latest" and @class="collapse" and @rel="1"]';
    const XPATH_EXPRESSION_ITEM_TITLE = '(.//h2[1] | .//strong[1])[1]/node()';
    const XPATH_EXPRESSION_ITEM_CONTENT = '.';
    const XPATH_EXPRESSION_ITEM_URI = '//link[@rel="canonical"]/@href';

    //const XPATH_EXPRESSION_ITEM_AUTHOR = '';
    const XPATH_EXPRESSION_ITEM_TIMESTAMP_PART = 'substring-after(//a[@class="collapse_link collapsed" and @data-target="#{{id_here}}"]/text(), "{{label_here}}")';
    const XPATH_EXPRESSION_ITEM_TIMESTAMP = 'substring(' . self::XPATH_EXPRESSION_ITEM_TIMESTAMP_PART . ', 1, string-length('
        . self::XPATH_EXPRESSION_ITEM_TIMESTAMP_PART . ') - 1)';

    //const XPATH_EXPRESSION_ITEM_ENCLOSURES = '';
    //const XPATH_EXPRESSION_ITEM_CATEGORIES = '';
    const SETTING_FIX_ENCODING = false;
    const SETTING_USE_RAW_ITEM_CONTENT = true;

    private const GAME_COUNTRY_DATE_SUBSTRING_PART = [
        'mk8d' => [
            'de' => 'eröffentlicht am ',
            'es' => 'isponible desde el ',
            'fr' => 'atée du ',
            'it' => 'ubblicata il ',
            'nl' => 'itgebracht op ',
            'pt' => 'ançada no dia ',
            'en' => 'eleased ',
        ],
        's2' => [
            'de' => 'eröffentlicht am ',
            'es' => 'isponible desde el ',
            'fr' => 'atée du ',
            'it' => 'ubblicata il ',
            'nl' => 'itgebracht op ',
            'pt' => 'ançada a ',
            'en' => 'eleased ',
        ],
        'sm3as' => [
            'de' => 'eröffentlicht am ',
            'es' => 'isponible desde el ',
            'fr' => 'ubliée le ',
            'it' => 'istribuita il ',
            'nl' => 'itgebracht op ',
            'pt' => 'ançada a ',
            'en' => 'eleased ',
        ],
        'sm3wbf' => [
            'de' => 'eröffentlicht am ',
            'es' => 'isponible desde el ',
            'fr' => 'atée du ',
            'it' => 'istribuita il ',
            'nl' => 'itgebracht op ',
            'pt' => 'ançada no dia ',
            'en' => 'eleased ',
        ],
        'smbw' => [
            'de' => 'eröffentlicht am ',
            'es' => 'isponible desde el ',
            'fr' => 'atée du ',
            'it' => 'istribuita il ',
            'nl' => 'itgebracht op ',
            'pt' => 'ançada a ',
            'en' => 'eleased ',
        ],
        'smm2' => [
            'de' => 'eröffentlicht am ',
            'es' => 'isponible desde el ',
            'fr' => 'ubliée le ',
            'it' => 'istribuita il ',
            'nl' => 'itgebracht op ',
            'pt' => 'ançada no dia ',
            'en' => 'eleased ',
        ],
        'smo' => [
            'de' => 'eröffentlicht am ',
            'es' => 'isponible desde el ',
            'fr' => 'atée du ',
            'it' => 'istribuita il ',
            'nl' => 'itgebracht op ',
            'pt' => 'ançada no dia ',
            'en' => 'eleased ',
        ],
        'ssbu' => [
            'de' => 'eröffentlicht am ',
            'es' => 'isponible desde el ',
            'fr' => 'atée du ',
            'it' => 'istribuita il ',
            'nl' => 'itgebracht op ',
            'pt' => 'ançada no dia ',
            'en' => 'eleased ',
        ],
        'sf' => [
            'de' => 'eröffentlicht am ',
            'es' => 'isponible desde el ',
            'fr' => 'ise en ligne le ',
            'it' => 'ubblicata il ',
            'nl' => 'itgebracht op ',
            'pt' => 'ançada no dia ',
            'en' => 'istributed ',
        ],
        'tlozla' => [
            'de' => 'eröffentlicht ',
            'es' => 'ublicada el ',
            'fr' => 'atée du ',
            'it' => 'istribuita il ',
            'nl' => 'itgegeven op ',
            'pt' => 'ançada a ',
            'en' => 'eleased ',
        ],
        'tlozss' => [
            'de' => 'eröffentlicht am ',
            'es' => 'isponible desde el ',
            'fr' => 'atée du ',
            'it' => 'ubblicata l\'',
            'nl' => 'itgebracht op ',
            'pt' => 'ançada a ',
            'en' => 'eleased ',
        ],
        'tloztotk' => [
            'de' => 'eröffentlicht am ',
            'es' => 'isponible desde el ',
            'fr' => 'ubliée le ',
            'it' => 'ubblicata il ',
            'nl' => 'erschenen op ',
            'pt' => 'ançada a ',
            'en' => 'eleased ',
        ],
        'xc2' => [
            'de' => 'eröffentlicht am ',
            'es' => 'isponible desde el ',
            'fr' => 'atée du ',
            'it' => 'istribuita il ',
            'nl' => 'itgebracht op ',
            'pt' => 'ançada a ',
            'en' => 'eleased ',
        ],
    ];

    private const GAME_COUNTRY_DATE_FORMAT = [
        'mk8d' => [
            'de' => 'd.m.y',
            'es' => 'd-m-y',
            'fr' => 'd/m/Y',
            'it' => 'd/m/y',
            'nl' => 'd m Y',
            'pt' => 'd/m/y',
            'en' => 'd/m/y',
        ],
        's2' => [
            'de' => 'd.m.Y',
            'es' => 'd-m-Y',
            'fr' => 'd/m/y',
            'it' => 'd/m/y',
            'nl' => 'd/m/y',
            'pt' => 'd/m/y',
            'en' => 'd F Y',
        ],
        'sm3as' => [
            'de' => 'j. m Y',
            'es' => 'j \d\e m \d\e Y',
            'fr' => 'j m Y',
            'it' => 'j m Y',
            'nl' => 'j m Y',
            'pt' => 'j \d\e m \d\e Y',
            'en' => 'j F Y',
        ],
        'sm3wbf' => [
            'de' => 'd.m.y',
            'es' => 'd-m-y',
            'fr' => 'd/m/y',
            'it' => 'd/m/y',
            'nl' => 'd m Y',
            'pt' => 'd/m/y',
            'en' => 'F j, Y',
        ],
        'smbw' => [
            'de' => 'd. m Y',
            'es' => 'j \d\e m \d\e Y',
            'fr' => 'd/m/Y',
            'it' => 'j m Y',
            'nl' => 'd m Y',
            'pt' => 'j \d\e m \d\e Y',
            'en' => 'j F Y',
        ],
        'smm2' => [
            'de' => 'd.m.Y',
            'es' => 'd-m-Y',
            'fr' => 'd/m/Y',
            'it' => 'd/m/Y',
            'nl' => 'd m Y',
            'pt' => 'd/m/y',
            'en' => 'd/m/y',
        ],
        'smo' => [
            'de' => 'd.m.Y',
            'es' => 'd-m-Y',
            'fr' => 'd/m/Y',
            'it' => 'd/m/y',
            'nl' => 'd m Y',
            'pt' => 'd/m/y',
            'en' => 'd/m/y',
        ],
        'ssbu' => [
            'de' => 'd. m Y',
            'es' => 'j \d\e m \d\e Y',
            'fr' => 'j m Y',
            'it' => 'j m Y',
            'nl' => 'd m Y',
            'pt' => 'd/m/Y',
            'en' => 'j F Y',
        ],
        'sf' => [
            'de' => 'd.m.Y',
            'es' => 'd-m-y',
            'fr' => 'd/m/Y',
            'it' => 'd/m/Y',
            'nl' => 'd m Y',
            'pt' => 'd/m/Y',
            'en' => 'd/m/Y',
        ],
        'tlozla' => [
            'de' => 'd. m Y',
            'es' => 'j m \d\e Y',
            'fr' => 'd/m/y',
            'it' => 'j m Y',
            'nl' => 'd m Y',
            'pt' => 'j \d\e m \d\e Y',
            'en' => 'j F y',
        ],
        'tlozss' => [
            'de' => 'd. m Y',
            'es' => 'j \d\e m \d\e Y',
            'fr' => 'd/m/y',
            'it' => 'j m Y',
            'nl' => 'd m Y',
            'pt' => 'j \d\e m \d\e Y',
            'en' => 'j F Y',
        ],
        'tloztotk' => [
            'de' => 'd. m Y',
            'es' => 'j \d\e m \d\e Y',
            'fr' => 'j m Y',
            'it' => 'j m Y',
            'nl' => 'd m Y',
            'pt' => 'j \d\e m \d\e Y',
            'en' => 'j F Y',
        ],
        'xc2' => [
            'de' => 'd.m.y',
            'es' => 'd-m-y',
            'fr' => 'd/m/Y',
            'it' => 'd/m/y',
            'nl' => 'd m Y',
            'pt' => 'd/m/y',
            'en' => 'd/m/y',
        ],
    ];

    private const FOREIGN_MONTH_NAMES = [
        'nl' => ['01' => 'januari', '02' => 'februari', '03' => 'maart', '04' => 'april', '05' => 'mei', '06' => 'juni', '07' => 'juli', '08' => 'augustus',
            '09' => 'september', '10' => 'oktober', '11' => 'november', '12' => 'december'],
        'fr' => ['01' => 'janvier', '02' => 'février', '03' => 'mars', '04' => 'avril', '05' => 'mai', '06' => 'juin', '07' => 'juillet', '08' => 'août',
            '09' => 'septembre', '10' => 'octobre', '11' => 'novembre', '12' => 'décembre'],
        'de' => ['01' => 'Januar', '02' => 'Februar', '03' => 'März', '04' => 'April', '05' => 'Mai', '06' => 'Juni', '07' => 'Juli', '08' => 'August',
            '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Dezember'],
        'es' => ['01' => 'enero', '02' => 'febrero', '03' => 'marzo', '04' => 'abril', '05' => 'mayo', '06' => 'junio', '07' => 'julio', '08' => 'agosto',
            '09' => 'septiembre', '10' => 'octubre', '11' => 'noviembre', '12' => 'diciembre'],
        'it' => ['01' => 'gennaio', '02' => 'febbraio', '03' => 'marzo', '04' => 'aprile', '05' => 'maggio', '06' => 'giugno', '07' => 'luglio', '08' => 'agosto',
            '09' => 'settembre', '10' => 'ottobre', '11' => 'novembre', '12' => 'dicembre'],
        'pt' => ['01' => 'janeiro', '02' => 'fevereiro', '03' => 'março', '04' => 'abril', '05' => 'maio', '06' => 'junho', '07' => 'julho', '08' => 'agosto',
            '09' => 'setembro', '10' => 'outubro', '11' => 'novembro', '12' => 'dezembro'],
    ];
    const LANGUAGE_REWRITE = ['co.uk' => 'en', 'co.za' => 'en', 'at' => 'de'];

    private string $lastId = '';
    private ?string $currentCategory = '';

    private function getCurrentCategory()
    {
        if (empty($this->currentCategory)) {
            $category = $this->getInput('category');
            $this->currentCategory = empty($category) ? self::PARAMETERS['']['category']['defaultValue'] : $category;
        }
        return $this->currentCategory;
    }

    public function getIcon()
    {
        return 'https://www.nintendo.co.uk/favicon.ico';
    }

    public function getURI()
    {
        $category = $this->getInput('category');
        if ('all' === $category) {
            return self::URI;
        } else {
            return $this->getSourceUrl();
        }
    }

    protected function provideFeedTitle(\DOMXPath $xpath)
    {
        $category = $this->getInput('category');
        $categoryName = array_search($category, self::PARAMETERS['']['category']['values']);
        return 'all' === $category ? self::NAME : $categoryName . ' Software-Updates';
    }

    protected function getSourceUrl()
    {
        $country = $this->getInput('country') ?? '';
        $category = $this->getCurrentCategory();
        return str_replace(self::PARAMETERS['']['country']['defaultValue'], $country, self::FEED_SOURCE_URL[$category]);
    }

    protected function getExpressionItem()
    {
        $category = $this->getCurrentCategory();
        return 'sf' === $category ? self::XPATH_EXPRESSION_ITEM_FIRMWARE : self::XPATH_EXPRESSION_ITEM;
    }

    protected function getExpressionItemTimestamp()
    {
        if (empty($this->lastId)) {
            return null;
        }
        $country = $this->getInput('country');
        $category = $this->getCurrentCategory();
        $language = $this->getLanguageFromCountry($country);
        return str_replace(
            ['{{id_here}}', '{{label_here}}'],
            [$this->lastId, static::GAME_COUNTRY_DATE_SUBSTRING_PART[$category][$language]],
            static::XPATH_EXPRESSION_ITEM_TIMESTAMP
        );
    }

    protected function getExpressionItemCategories()
    {
        $category = $this->getCurrentCategory();
        $categoryName = array_search($category, self::PARAMETERS['']['category']['values']);
        return 'string("' . $categoryName . '")';
    }

    public function collectData()
    {
        $category = $this->getCurrentCategory();
        if ('all' === $category) {
            $allItems = [];
            foreach (self::PARAMETERS['']['category']['values'] as $catKey) {
                if ('all' === $catKey) {
                    continue;
                }
                $this->currentCategory = $catKey;
                $this->items = [];
                parent::collectData();
                $allItems = [...$allItems, ...$this->items];
            }
            $this->currentCategory = 'all';
            $this->items = $allItems;
        } else {
            parent::collectData();
        }
    }

    protected function formatItemTitle($value)
    {
        if (false !== strpos($value, ' (')) {
            $value = substr($value, 0, strpos($value, ' ('));
        }
        if ('all' === $this->getInput('category')) {
            $category = $this->getCurrentCategory();
            $categoryName = array_search($category, self::PARAMETERS['']['category']['values']);
            return $categoryName . ' ' . $value;
        }
        return $value;
    }

    protected function formatItemContent($value)
    {
        $result = preg_match('~<div class="collapse" id="([a-z0-9]+)" rel="1">(.*)</div>~', $value, $matches);
        if (1 === $result) {
            $this->lastId = $matches[1];
            return trim($matches[2]);
        }
        return $value;
    }

    protected function formatItemTimestamp($value)
    {
        $country = $this->getInput('country');
        $category = $this->getCurrentCategory();
        $language = $this->getLanguageFromCountry($country);

        $aMonthNames = self::FOREIGN_MONTH_NAMES[$language] ?? null;
        if (null !== $aMonthNames) {
            $value = str_replace(array_values($aMonthNames), array_keys($aMonthNames), $value);
        }
        $value = str_replace('­', '-', $value);
        $value = str_replace('--', '-', $value);

        $date = \DateTime::createFromFormat(self::GAME_COUNTRY_DATE_FORMAT[$category][$language], $value);
        if (false === $date) {
            $date = new \DateTime('now');
        }
        return $date->getTimestamp();
    }

    protected function generateItemId(FeedItem $item)
    {
        return $this->getCurrentCategory() . '-' . $this->lastId;
    }

    private function getLanguageFromCountry($country)
    {
        return (strpos($country, '/') !== false) ? substr($country, strpos($country, '/') + 1) : (self::LANGUAGE_REWRITE[$country] ?? $country);
    }
}
