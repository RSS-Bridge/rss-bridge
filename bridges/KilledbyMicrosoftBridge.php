<?php

class KilledbyMicrosoftBridge extends BridgeAbstract
{
    const NAME = 'Killed by Microsoft Bridge';
    const URI = 'https://killedbymicrosoft.info';
    const DESCRIPTION = 'Lists recently discontinued Microsoft products';
    const MAINTAINER = 'tillcash';

    public function collectData()
    {
        // Fetch JSON data
        $json = getContents('https://killedbymicrosoft.info/graveyard.json');

        // Decode JSON data
        $discontinuedServices = json_decode($json, true);

        // Sort the array based on dateClose in descending order
        usort($discontinuedServices, function ($a, $b) {
            return strtotime($b['dateClose']) - strtotime($a['dateClose']);
        });

        // Slice the array to limit the number of items processed
        $discontinuedServices = array_slice($discontinuedServices, 0, 15);

        // Process each item
        foreach ($discontinuedServices as $service) {
            // Construct the title
            $title = $this->formatTitle(
                $service['name'],
                $service['dateOpen'],
                $service['dateClose']
            );

            // Construct the content
            $content = sprintf(
                '<p>%s</p><p>Scheduled closure on %s.</p>',
                $service['description'],
                $service['dateClose']
            );

            // Add the item to the feed
            $this->items[] = [
                'title' => $title,
                'uid' => $service['slug'],
                'uri' => $service['link'],
                'content' => $content
            ];
        }
    }

    private function formatTitle($name, $dateOpen, $dateClose)
    {
        // Extract years from dateOpen and dateClose
        $yearOpen = date('Y', strtotime($dateOpen));
        $yearClose = date('Y', strtotime($dateClose));

        // Format the title
        return "{$name} ({$yearOpen} - {$yearClose})";
    }
}
