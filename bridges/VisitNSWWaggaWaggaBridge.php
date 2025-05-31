<?php

class VisitNSWWaggaWaggaBridge extends BridgeAbstract
{
    const NAME = 'VisitNSW Wagga Wagga Events';
    const URI = 'https://www.visitnsw.com/destinations/country-nsw/riverina/wagga-wagga/events';
    const DESCRIPTION = 'Latest events from VisitNSW Wagga Wagga';
    const MAINTAINER = 'Scrub000';
    const PARAMETERS = [
        [
            'pages' => [
                'name' => 'Number of pages to load',
                'type' => 'number',
                'defaultValue' => 1,
                'required' => false,
            ]
        ]
    ];

    public function collectData() {
        $baseUri = self::URI;
        $maxPages = (int)($this->getInput('pages') ?? 1);

        for ($i = 0; $i < $maxPages; $i++) {
            $params = '?field_event_instance_value=' . urlencode(date('Y-m-d H:i:s')) . '&sort_by=field_event_instance_value&sort_order=ASC&page=' . $i;
            $pageUrl = $i === 0 ? $baseUri : $baseUri . $params;

            $html = getSimpleHTMLDOM($pageUrl)
                or returnServerError("Could not load page $i");

            foreach ($html->find('article.event') as $event) {
                $linkRel = $event->find('a.tile__product-list-link', 0)->href;
                $url = urljoin(self::URI, $linkRel);

                $title = trim($event->find('h3[itemprop=name] span', 0)->plaintext);
                $author = trim($event->find('div.tile__product-list-area', 0)->plaintext);
                $shortDesc = $event->find('div.prod-desc', 0)->plaintext ?? '';
                $categories = [$author];
                $timestamp = time();

                // Extract thumbnail from data-src
                $imgTag = $event->find('img', 0);
                $imgSrc = $imgTag ? $imgTag->getAttribute('data-src') : '';
                if ($imgSrc && str_starts_with($imgSrc, '/')) {
                    $imgSrc = 'https://www.visitnsw.com' . $imgSrc;
                }
                $imgHtml = $imgSrc ? '<p><img src="' . htmlspecialchars($imgSrc) . '" alt="' . htmlspecialchars($title) . '"></p>' : '';

                // Load full event page for long description + location/date
                $eventHtml = getSimpleHTMLDOMCached($url, 86400);

                $descBlock = $eventHtml->find('div.collapse-content.product__overview-full div.field--item', 0);
                $fullDescription = $descBlock ? $descBlock->innertext : htmlspecialchars($shortDesc);

                $locationSpan = $eventHtml->find('span.atdw-product__venue', 0);
                $dateDiv = $eventHtml->find('div.atdw-product__event-date', 0);
                $location = $locationSpan ? trim($locationSpan->plaintext) : 'Location unknown';
                $dateText = $dateDiv ? trim($dateDiv->plaintext) : 'Date unknown';

                $infoLine = '<p><strong>Location: ' . htmlspecialchars($location) . ' | Date: ' . $dateText . '</strong></p>';
                $content = $imgHtml . $infoLine . $fullDescription;

                $this->items[] = [
                    'title' => $title,
                    'uri' => $url,
                    'author' => $author,
                    'timestamp' => $timestamp,
                    'content' => $content ?: 'Content not found.',
                    'categories' => $categories,
                ];
            }
        }
    }
}
