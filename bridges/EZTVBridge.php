<?php
class EZTVBridge extends BridgeAbstract{

	const MAINTAINER = "alexAubin";
	const NAME = "EZTV";
	const URI = "https://eztv.ch/";
	const DESCRIPTION = "Returns list of *recent* torrents for a specific show on EZTV. Get showID from URLs in https://eztv.ch/shows/showID/show-full-name.";

    const PARAMETERS = array( array(
        'i'=>array(
            'name'=>'Show ids',
            'exampleValue'=>'showID1,showID2,…',
            'required'=>true
        )
    ));

	public function collectData(){

        // Make timestamp from relative released time in table
        function makeTimestamp($relativeReleaseTime){

                $relativeDays = 0;
                $relativeHours = 0;

                foreach (explode(" ",$relativeReleaseTime) as $relativeTimeElement) {
                    if (substr($relativeTimeElement,-1) == "d") $relativeDays = substr($relativeTimeElement,0,-1);
                    if (substr($relativeTimeElement,-1) == "h") $relativeHours = substr($relativeTimeElement,0,-1);
                }
                return mktime(date('h')-$relativeHours,0,0,date('m'),date('d')-$relativeDays,date('Y'));
        }

        // Loop on show ids
        $showList = explode(",",$this->getInput('i'));
        foreach($showList as $showID){

            // Get show page
            $html = getSimpleHTMLDOM(self::URI.'shows/'.rawurlencode($showID).'/')
                or returnServerError('Could not request EZTV for id "'.$showID.'"');

            // Loop on each element that look like an episode entry...
            foreach($html->find('.forum_header_border') as $element) {

                // Filter entries that are not episode entries
                $ep = $element->find('td',1);
                if (empty($ep)) continue;
                $epinfo = $ep->find('.epinfo',0);
                $released = $element->find('td',3);
                if (empty($epinfo)) continue;
                if (empty($released->plaintext)) continue;

                // Filter entries that are older than 1 week
                if ($released->plaintext == '&gt;1 week') continue;

                // Fill item
                $item = array();
                $item['uri'] = self::URI.$epinfo->href;
                $item['id'] = $item['uri'];
                $item['timestamp'] = makeTimestamp($released->plaintext);
                $item['title'] = $epinfo->plaintext;
                $item['content'] = $epinfo->alt;
                if(isset($item['title']))
                    $this->items[] = $item;
            }
        }
	}
}
