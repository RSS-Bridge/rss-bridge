<?php

declare(strict_types=1);

class PanneauPocketBridge extends BridgeAbstract
{
    const NAME = 'Panneau Pocket';
    const URI = 'https://app.panneaupocket.com';
    const DESCRIPTION = 'Fetches the latest infos from Panneau Pocket';
    const MAINTAINER = 'floviolleau';
    const PARAMETERS = [
        [
            'city' => [
                'name' => 'City slug',
                'exampleValue' => '508884409-hadol-88220',
                'required' => true,
            ],
        ],
    ];
    const CACHE_TIMEOUT = 7200; // 2h

    private $cityName = '';

    public function getName()
    {
        return $this->cityName !== '' ? $this->cityName : self::NAME;
    }

    public function collectData()
    {
        $citySlug = $this->getInput('city');
        $cityUrl = self::URI . '/ville/' . $citySlug;

        if (!filter_var($cityUrl, FILTER_VALIDATE_URL)) {
            throwServerException('Invalid city slug: ' . $citySlug);
        }

        $dom = getSimpleHTMLDOM($cityUrl);

        $this->cityName = $this->extractCityName($dom);

        $notices = $dom->find('div.sign-carousel--item');
        if (!is_array($notices)) {
            throwServerException('Invalid or empty content');
        }

        foreach ($notices as $notice) {
            $a = $notice->find('button.dropdown-item', 0);
            $url = $a->href ?? '';

            if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
                continue;
            }

            $title = $notice->find('.sign-preview__content .title', 0);
            $content = $notice->find('.sign-preview__content .content', 0);
            $date = $notice->find('span.date', 0);

            $this->items[] = [
                'uid' => $url,
                'uri' => $url,
                'title' => $title ? trim($title->plaintext) : '',
                'timestamp' => $date ? $this->extractDate($date->plaintext) : '',
                'content' => $content ? sanitize($content->innertext) : '',
            ];
        }
    }

    private function extractCityName($dom)
    {
        $city = $dom->find('.sign-preview__title .infos .city', 0);
        if (!$city) {
            return '';
        }

        $cityName = trim($city->plaintext);
        if ($cityName === '') {
            return '';
        }

        $postcode = $dom->find('.sign-preview__title .infos .postcode', 0);
        if ($postcode) {
            $postcodeValue = trim($postcode->plaintext);
            if ($postcodeValue !== '') {
                return $cityName . ' - ' . $postcodeValue;
            }
        }

        return $cityName;
    }

    private function extractDate($text)
    {
        $text = trim($text);

        if (!preg_match('~(\d{2})/(\d{2})/(\d{4})$~', $text, $match)) {
            return '';
        }

        [, $day, $month, $year] = $match;

        if (!checkdate((int)$month, (int)$day, (int)$year)) {
            return '';
        }

        return mktime(0, 0, 0, (int)$month, (int)$day, (int)$year);
    }
}
