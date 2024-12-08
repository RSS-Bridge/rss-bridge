<?php

class FDroidBridge extends BridgeAbstract
{
    const MAINTAINER = 'Mitsukarenai';
    const NAME = 'F-Droid Bridge';
    const URI = 'https://f-droid.org/';
    const CACHE_TIMEOUT = 60 * 60 * 4; // 4 hours
    const DESCRIPTION = 'Returns latest added/updated apps on the open-source Android apps repository F-Droid';

    const PARAMETERS = [ [
        'u' => [
            'name' => 'Widget selection',
            'type' => 'list',
            'values' => [
                'Latest added apps' => 'added',
                'Latest updated apps' => 'updated'
            ]
        ]
    ]];

    public function getIcon()
    {
        return self::URI . 'assets/favicon.ico';
    }

    private function getTimestamp($url)
    {
        $curlOptions = [
            CURLOPT_CUSTOMREQUEST => 'HEAD',
            CURLOPT_NOBODY => true,
        ];
        $reponse = getContents($url, [], $curlOptions, true);
        $lastModified = $reponse->getHeader('last-modified');
        $timestamp = strtotime($lastModified ?? 'today');
        return $timestamp;
    }

    public function collectData()
    {
        $url = self::URI;
        $html = getSimpleHTMLDOM($url);

        // targetting the corresponding widget based on user selection
        // "updated" is the 5th widget on the page, "added" is the 6th

        switch ($this->getInput('u')) {
            case 'updated':
                $html_widget = $html->find('div.sidebar-widget', 5);
                break;
            default:
                $html_widget = $html->find('div.sidebar-widget', 6);
                break;
        }

        // and now extracting app info from the selected widget (and yeah turns out icons are of heterogeneous sizes)

        foreach ($html_widget->find('a') as $element) {
            $item = [];
            $item['uri'] = self::URI . $element->href;
            $item['title'] = $element->find('h4', 0)->plaintext;
            $item['icon'] = $element->find('img', 0)->src;
            $item['timestamp'] = $this->getTimestamp($item['icon']);
            $item['summary'] = $element->find('span.package-summary', 0)->plaintext;
            $item['content'] = '
					<a href="' . $item['uri'] . '">
						<img alt="" style="max-height:128px" src="' . $item['icon'] . '">
					</a><br>' . $item['summary'];
            $this->items[] = $item;
        }
    }
}
