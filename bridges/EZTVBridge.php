<?php
class EZTVBridge extends BridgeAbstract{

	public function loadMetadatas() {

		$this->maintainer = "alexAubin";
		$this->name = "EZTV";
		$this->uri = "https://eztv.ch/";
		$this->description = "Returns list of *recent* torrents for a specific show on EZTV. Get showID from URLs in https://eztv.ch/shows/showID/show-full-name.";
		$this->update = "2016-08-09";

		$this->parameters[] =
		'[
			{
				"name" : "Show ids",
				"identifier" : "i",
				"exampleValue" : "showID1,showID2,..."
			}
		]';

	}

	public function collectData(array $param){

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

        // Check for ID provided
        if (!isset($param['i']))
			$this->returnError('You must provide a list of ID (?i=showID1,showID2,...)', 400);

        // Loop on show ids
        $showList = explode(",",$param['i']); 
        foreach($showList as $showID){

            // Get show page
            $html = $this->file_get_html('https://eztv.ch/shows/'.rawurlencode($showID).'/') or $this->returnError('Could not request EZTV for id "'.$showID.'"', 404);

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
                $item = new \Item();
                $item->uri = 'https://eztv.ch/'.$epinfo->href;
                $item->id = $item->uri;
                $item->timestamp = makeTimestamp($released->plaintext);
                $item->title = $epinfo->plaintext;
                $item->content = $epinfo->alt;
                if(!empty($item->title))
                    $this->items[] = $item;
            }
        }
	}
}
