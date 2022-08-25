<?php

class InstituteForTheStudyOfWarBridge extends BridgeAbstract
{
    const MAINTAINER = 'sqrtminusone';
    const NAME = 'Institute for the Study of War';
    const URI = 'https://www.understandingwar.org';

    const CACHE_TIMEOUT = 3600; // 1 hour
    const DESCRIPTION = 'Recent publications of the ISW.';

    const PARAMETERS = [
        '' => [
            'searchURL' => [
                'name' => 'Filter URL',
                'required' => false,
                'title' => 'Set a filter on https://www.understandingwar.org/publications and copy the URL parameters.'
            ],
        ]
    ];

    public function collectData()
    {
        $filter = $this->getInput('searchURL');
        $html = getSimpleHTMLDOM(self::URI . '/publications?' . $filter);
        $entries = $html->find('.view-content', 0);
        foreach ($entries->find('.views-row') as $entry) {
            $this->items[] = $this->processEntry($entry);
        }
    }

    private function processEntry($entry)
    {
        $h2 = $entry->find('h2', 0);
        $title = $h2->plaintext;
        $uri = $h2->find('a', 0)->href;

        $date_span = $entry->find('span.datespan', 0);
        list($date_string, $user) = explode('-', $date_span->innertext);
        $date = DateTime::createFromFormat('F d, Y', trim($date_string));

        $html = getSimpleHTMLDOMCached(self::URI . $uri);
        $content = $html->find('[property=content:encoded]', 0)->innertext;

        $enclosures = [];
        $pdfs_list = $html->find('.field-name-field-pdf-report', 0);
        if ($pdfs_list != null) {
            foreach ($pdfs_list->find('.field-item') as $pdf_item) {
                $a = $pdf_item->find('a', 0);
                array_push($enclosures, $a->href);
            }
        }

        return [
            'uri' => self::URI . $uri,
            'title' => $title,
            'uid' => $uri,
            'author' => trim($user),
            'timestamp' => $date->getTimestamp(),
            'content' => $content,
            'enclosures' => $enclosures
        ];
    }
}
