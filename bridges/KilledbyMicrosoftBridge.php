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
            // Concatenate service name with dateOpen and dateClose
            $title = "{$service['name']} ({$service['dateOpen']} - {$service['dateClose']})";

            $this->items[] = array(
                'title' => $title,
                'uid' => $service['slug'],
                'uri' => $service['link'],
                'content' => $service['description']
            );
        }
    }
}
