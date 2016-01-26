<?php
// simple_html_dom funtion to get the dom from contents instead from file
function content_get_html($contents, $maxLen=-1, $lowercase = true, $forceTagsClosed=true, $target_charset = DEFAULT_TARGET_CHARSET, $stripRN=true, $defaultBRText=DEFAULT_BR_TEXT, $defaultSpanText=DEFAULT_SPAN_TEXT)
{
    // We DO force the tags to be terminated.
    $dom = new simple_html_dom(null, $lowercase, $forceTagsClosed, $target_charset, $stripRN, $defaultBRText, $defaultSpanText);

    if (empty($contents) || strlen($contents) > MAX_FILE_SIZE)
    {
        return false;
    }
    // The second parameter can force the selectors to all be lowercase.
    $dom->load($contents, $lowercase, $stripRN);
    return $dom;
}

class CpasbienBridge extends HttpCachingBridgeAbstract{
    
    private $request;

	public function loadMetadatas() {

		$this->maintainer = "lagaisse";
		$this->name = "Cpasbien Bridge";
		$this->uri = "http://www.cpasbien.io";
		$this->description = "Returns latest torrents from a request query";
		$this->update = "2016-01-26";

		$this->parameters[] =
		'[
			{
				"name" : "Search",
				"identifier" : "q",
                "required" : true,
                "title" : "Type your search"
			}
		]';

	}


    public function collectData(array $param){
        $this->loadMetadatas();
        $html = '';
        if (isset($param['q'])) {   /* keyword search mode */
            $this->request = str_replace(" ","-",trim($param['q']));
            $html = file_get_html($this->uri.'/recherche/'.urlencode($this->request).'.html') or $this->returnError('No results for this query.', 404);
        }
        else {
            $this->returnError('You must specify a keyword (?q=...).', 400);
        }

        foreach ($html->find('#gauche',0)->find('div') as $episode) {
            if ($episode->getAttribute('class')=='ligne0' || $episode->getAttribute('class')=='ligne1')
            {
                
                $htmlepisode=content_get_html($this->get_cached($episode->find('a', 0)->getAttribute('href')));

                $item = new \Item();
                $item->name = $episode->find('a', 0)->text();
                $item->title = $episode->find('a', 0)->text();
                $item->timestamp = $this->get_cached_time($episode->find('a', 0)->getAttribute('href'));
                $textefiche=$htmlepisode->find('#textefiche', 0)->find('p',1);
                if (isset($textefiche)) {
                    $item->content = $textefiche->text();
                }
                else {
                    $item->content = $htmlepisode->find('#textefiche', 0)->find('p',0)->text();    
                }

                $item->id = $episode->find('a', 0)->getAttribute('href');
                $item->uri = $this->uri . $htmlepisode->find('#telecharger',0)->getAttribute('href');
                $item->thumbnailUri = $htmlepisode->find('#bigcover', 0)->find('img',0)->getAttribute('src');
                $this->items[] = $item;
            }
        }


    }


    public function getName(){
        return (!empty($this->request) ? $this->request .' - ' : '') . $this->name;
    }

    public function getURI(){
        return $this->uri;
    }

    public function getCacheDuration(){
        return 60*60*24; // 24 hours
    }
}
