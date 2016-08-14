<?php
class FlickrTagBridge extends BridgeAbstract{

	public function loadMetadatas() {

		$this->maintainer = "erwang";
		$this->name = "Flickr TagUser";
		$this->uri = "http://www.flickr.com/";
		$this->description = "Returns the tagged or user images from Flickr";
		$this->update = "2016-08-09";

		$this->parameters["By keyword"] =
		'[
			{
				"name" : "Keyword",
				"identifier" : "q"
			}
		]';

		$this->parameters["By username"] =
		'[
			{
				"name" : "Username",
				"identifier" : "u"
			}
		]';
	}

    public function collectData(array $param){
        $html = $this->file_get_html('http://www.flickr.com/search/?q=vendee&s=rec') or $this->returnError('Could not request Flickr.', 404);
        if (isset($param['q'])) {   /* keyword search mode */
            $this->request = $param['q'];
            $html = $this->file_get_html('http://www.flickr.com/search/?q='.urlencode($this->request).'&s=rec') or $this->returnError('No results for this query.', 404);
        }
        elseif (isset($param['u'])) {   /* user timeline mode */
            $this->request = $param['u'];
            $html = $this->file_get_html('http://www.flickr.com/photos/'.urlencode($this->request).'/') or $this->returnError('Requested username can\'t be found.', 404);
        }
        
        else {
            $this->returnError('You must specify a keyword or a Flickr username.', 400);
        }

        foreach($html->find('span.photo_container') as $element) {
            $item = new \Item();
            $item->uri = 'http://flickr.com'.$element->find('a',0)->href;
            $thumbnailUri = $element->find('img',0)->getAttribute('data-defer-src');
            $item->content = '<a href="' . $item->uri . '"><img src="' . $thumbnailUri . '" /></a>'; // FIXME: Filter javascript ?
            $item->title = $element->find('a',0)->title;
            $this->items[] = $item;
        }
    }

    public function getCacheDuration(){
        return 21600; // 6 hours
    }
}

