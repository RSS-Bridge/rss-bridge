<?php

class JohannesBlickBridge extends BridgeAbstract
{
    const NAME        = 'Johannes Blick';
    const URI         = 'https://www.st-johannes-baptist.de/index.php/unsere-medien/johannesblick-archiv';
    const DESCRIPTION = 'RSS feed for Johannes Blick';
    const MAINTAINER  = 'jummo4@yahoo.de';

    public function collectData()
    {
        $html = getSimpleHTMLDOM(self::URI)
            or returnServerError('Could not request: ' . self::URI);

        $html = defaultLinkTo($html, self::URI);
        foreach ($html->find('td > a') as $index => $a) {
            $item = []; // Create an empty item
            $articlePath = $a->href;
            $item['title'] = $a->innertext;
            $item['uri'] = $articlePath;
            $item['content'] = '';

            $this->items[] = $item; // Add item to the list
            if (count($this->items) >= 10) {
                break;
            }
        }
    }
}
