<?php

class AlfaBankByBridge extends BridgeAbstract
{
    const MAINTAINER = 'lassana';
    const NAME = 'AlfaBank.by Новости';
    const URI = 'https://www.alfabank.by';
    const DESCRIPTION = 'Уведомления Alfa-Now — новости от Альфа-Банка';
    const CACHE_TIMEOUT = 3600; // 1 hour
    const PARAMETERS = [
        'News' => [
            'business' => [
                'name' => 'Альфа Бизнес',
                'type' => 'list',
                'title' => 'В зависимости от выбора, возращает уведомления для" .
					" клиентов физ. лиц либо для клиентов-юридических лиц и ИП',
                'values' => [
                    'Новости' => 'news',
                    'Новости бизнеса' => 'newsBusiness'
                ],
                'defaultValue' => 'news'
            ],
            'fullContent' => [
                'name' => 'Включать содержимое',
                'type' => 'checkbox',
                'title' => 'Если выбрано, содержимое уведомлений вставляется в поток (работает медленно)'
            ]
        ]
    ];

    public function collectData()
    {
        $business = $this->getInput('business') == 'newsBusiness';
        $fullContent = $this->getInput('fullContent') == 'on';

        $mainPageUrl = self::URI . '/about/articles/uvedomleniya/';
        if ($business) {
            $mainPageUrl .= '?business=true';
        }
        $html = getSimpleHTMLDOM($mainPageUrl);
        $limit = 0;

        foreach ($html->find('a.notifications__item') as $element) {
            if ($limit < 10) {
                $item = [];
                $item['uid'] = 'urn:sha1:' . hash('sha1', $element->getAttribute('data-notification-id'));
                $item['title'] = $element->find('div.item-title', 0)->innertext;
                $item['timestamp'] = DateTime::createFromFormat(
                    'd M Y',
                    $this->ruMonthsToEn($element->find('div.item-date', 0)->innertext)
                )->getTimestamp();

                $itemUrl = self::URI . $element->href;
                if ($business) {
                    $itemUrl = str_replace('?business=true', '', $itemUrl);
                }
                $item['uri'] = $itemUrl;

                if ($fullContent) {
                    $itemHtml = getSimpleHTMLDOM($itemUrl);
                    if ($itemHtml) {
                        $item['content'] = $itemHtml->find('div.now-p__content-text', 0)->innertext;
                    }
                }

                $this->items[] = $item;
                $limit++;
            }
        }
    }

    public function getIcon()
    {
        return static::URI . '/local/images/favicon.ico';
    }

    private function ruMonthsToEn($date)
    {
        $ruMonths = [
            'Января', 'Февраля', 'Марта', 'Апреля', 'Мая', 'Июня',
            'Июля', 'Августа', 'Сентября', 'Октября', 'Ноября', 'Декабря' ];
        $enMonths = [
            'January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December' ];
        return str_replace($ruMonths, $enMonths, $date);
    }
}
