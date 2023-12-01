<?php

class DemosBerlinBridge extends BridgeAbstract
{
    const NAME = 'Demos Berlin';
    const URI = 'https://www.berlin.de/polizei/service/versammlungsbehoerde/versammlungen-aufzuege/';
    const CACHE_TIMEOUT = 3 * 60 * 60;
    const DESCRIPTION = 'Angezeigte Versammlungen und Aufzüge in Berlin';
    const MAINTAINER = 'knrdl';
    const PARAMETERS = [[
        'days' => [
            'name' => 'Tage',
            'type' => 'number',
            'title' => 'Einträge für die nächsten Tage zurückgeben',
            'required' => true,
            'defaultValue' => 7,
        ]
    ]];

    public function getIcon()
    {
        return 'https://www.berlin.de/i9f/r1/images/favicon/favicon.ico';
    }

    public function collectData()
    {
        $json = getContents('https://www.berlin.de/polizei/service/versammlungsbehoerde/versammlungen-aufzuege/index.php/index/all.json');
        $jsonFile = json_decode($json, true);

        $daysInterval = DateInterval::createFromDateString($this->getInput('days') . ' day');
        $maxTargetDate = date_add(new DateTime('now'), $daysInterval);

        foreach ($jsonFile['index'] as $entry) {
            $entryDay = implode('-', array_reverse(explode('.', $entry['datum']))); // dd.mm.yyyy to yyyy-mm-dd
            $ts = (new DateTime())->setTimestamp(strtotime($entryDay));
            if ($ts <= $maxTargetDate) {
                $item = [];
                $item['uri'] = 'https://www.berlin.de/polizei/service/versammlungsbehoerde/versammlungen-aufzuege/index.php/detail/' . $entry['id'];
                $item['timestamp'] = $entryDay . ' ' . $entry['von'];
                $item['title'] = $entry['thema'];
                $location = $entry['strasse_nr'] . ' ' . $entry['plz'];
                $locationQuery = http_build_query(['query' => $location]);
                $item['content'] = <<<HTML
                <h1>{$entry['thema']}</h1>
                <p>📅 <time datetime="{$item['timestamp']}">{$entry['datum']} {$entry['von']} - {$entry['bis']}</time></p>
                <a href="https://www.openstreetmap.org/search?$locationQuery">
                📍 {$location}
                </a>
                <p>{$entry['aufzugsstrecke']}</p>
                HTML;
                $item['uid'] = $this->getSanitizedHash($entry['datum'] . '-' . $entry['von'] . '-' . $entry['bis'] . '-' . $entry['thema']);

                $this->items[] = $item;
            }
        }
    }

    private function getSanitizedHash($string)
    {
        return hash('sha1', preg_replace('/[^a-zA-Z0-9]/', '', strtolower($string)));
    }
}
