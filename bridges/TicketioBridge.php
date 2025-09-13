<?php

declare(strict_types=1);

class TicketioBridge extends BridgeAbstract
{
    const NAME = 'Ticket.io Bridge';
    const URI = 'https://www.ticket.io';
    const DESCRIPTION = 'Provides updates for available events in a specific ticketshop on ticket.io';
    const MAINTAINER = 'SebLaus';
    const CACHE_TIMEOUT = 60 * 60 * 12; // 12 hours
    const PARAMETERS = [
        [
            'Link' => [
                'name'          => 'Link to Ticketpage',
                'required'      => true,
                'exampleValue'  => 'https://LOCATION.ticket.io'
            ]
        ]
    ];

    public function collectData()
    {
        $html = getSimpleHTMLDOM($this->getInput('Link'));

        if (!$html) {
            throwServerException('Could not retrieve website content.');
        }

        // Find all event rows
        $eventRows = $html->find('tr.container');

        foreach ($eventRows as $eventRow) {
            // Get the event name
            $eventName = $eventRow->find('a.a-eventlink', 0)->plaintext;

            // Reduce eventName length if too long
            if (strlen($eventName) > 35) {
                $eventName = substr($eventName, 0, 35);
            }

            // Find the list item containing the date
            $dateElement = $eventRow->find('ul.fa-ul li span', 2); // Third <span> inside the list item

            // Check if the date element is found
            if ($dateElement) {
                $eventDate = $dateElement->plaintext;
            } else {
                $eventDate = 'Date not found';
            }

            // Get Picture
            $imageElement = $eventRow->find('img', 0);
            if ($imageElement) {
                $image = $imageElement->src;
            } else {
                $image = '';
            }


            // Build title out of Name and Date
            $eventTitle = $eventName . ' - ' . $eventDate;

            // Link to the event page
            $eventLink = $this->getInput('Link') . $eventRow->find('a.a-eventlink', 0)->href;

            // Create a feed item with the title and link
            $item = [];
            $item['title'] = $eventTitle;
            $item['uri'] = $eventLink;
            $item['content'] = "
            <p><a href='$eventLink'>
            <img src='$image'>
            </a></p>
            <p><a href='$eventLink'>More details</a></p>
            ";

            $this->items[] = $item;
        }
    }
}
