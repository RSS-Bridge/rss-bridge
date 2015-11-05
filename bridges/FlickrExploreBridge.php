<?php
class FlickrExploreBridge extends BridgeAbstract{

	public function loadMetadatas() {

		$this->maintainer = "sebsauvage";
		$this->name = "Flickr Explore";
		$this->uri = "http://www.flickr.com/explore";
		$this->description = "Returns the latest interesting images from Flickr";
		$this->update = "2014-05-25";

	}

    public function collectData(array $param){
        $html = file_get_html('http://www.flickr.com/explore') or $this->returnError('Could not request Flickr.', 404);
    
        foreach($html->find('span.photo_container') as $element) {
            $item = new \Item();
            $item->uri = 'http://flickr.com'.$element->find('a',0)->href;
            $item->thumbnailUri = $element->find('img',0)->getAttribute('data-defer-src');
            $item->content = '<a href="' . $item->uri . '"><img src="' . $item->thumbnailUri . '" /></a>'; // FIXME: Filter javascript ?
            $item->title = $element->find('a',0)->title;
            $this->items[] = $item;
        }
    }

    public function getName(){
        return 'Flickr Explore';
    }

    public function getURI(){
        return 'http://www.flickr.com/explore';
    }

    public function getCacheDuration(){
        return 21600; // 6 hours
    }
}
