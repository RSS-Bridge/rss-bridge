<?php

class BundesbankBridge extends BridgeAbstract
{
    const PARAM_LANG = 'lang';

    const LANG_EN = 'en';
    const LANG_DE = 'de';

    const NAME = 'Bundesbank Bridge';
    const URI = 'https://www.bundesbank.de/';
    const DESCRIPTION = 'Returns the latest studies of the Bundesbank (Germany)';
    const MAINTAINER = 'logmanoriginal';
    const CACHE_TIMEOUT = 86400; // 24 hours

    const PARAMETERS = [
        [
            self::PARAM_LANG => [
                'name' => 'Language',
                'type' => 'list',
                'defaultValue' => self::LANG_DE,
                'values' => [
                    'English' => self::LANG_EN,
                    'Deutsch' => self::LANG_DE
                ]
            ]
        ]
    ];

    public function getIcon()
    {
        return self::URI . 'resource/crblob/1890/a7f48ee0ae35348748121770ba3ca009/mL/favicon-ico-data.ico';
    }

    public function getURI()
    {
        switch ($this->getInput(self::PARAM_LANG)) {
            case self::LANG_EN:
                return self::URI . 'en/publications/reports/studies';
            case self::LANG_DE:
                return self::URI . 'de/publikationen/berichte/studien';
        }

        return parent::getURI();
    }

    public function collectData()
    {
        $html = getSimpleHTMLDOM($this->getURI());

        $html = defaultLinkTo($html, $this->getURI());

        foreach ($html->find('ul.resultlist li') as $study) {
            $item = [];

            $item['uri'] = $study->find('.teasable__link', 0)->href;

            // Get title without child elements (i.e. subtitle)
            $title = $study->find('.teasable__title div.h2', 0);

            foreach ($title->children as &$child) {
                $child->outertext = '';
            }

            $item['title'] = $title->innertext;

            // Add subtitle to the content if it exists
            $item['content'] = '';

            if ($subtitle = $study->find('.teasable__subtitle', 0)) {
                $item['content'] .= '<strong>' . $study->find('.teasable__subtitle', 0)->plaintext . '</strong>';
            }

            $teasable = $study->find('.teasable__text', 0);
            $teasableText = $teasable->plaintext ?? '';
            $item['content'] .= '<p>' . $teasableText . '</p>';

            $item['timestamp'] = strtotime($study->find('.teasable__date', 0)->plaintext);

            // Downloads and older studies don't have images
            if ($study->find('.teasable__image', 0)) {
                $item['enclosures'] = [
                    $study->find('.teasable__image img', 0)->src
                ];
            }

            $this->items[] = $item;
        }
    }
}
