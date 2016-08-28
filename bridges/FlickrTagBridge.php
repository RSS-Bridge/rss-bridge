<?php
class FlickrTagBridge extends BridgeAbstract{

	public $maintainer = "erwang";
	public $name = "Flickr TagUser";
	public $uri = "http://www.flickr.com/";
	public $description = "Returns the tagged or user images from Flickr";

    public $parameters = array(
        'By keyword' => array(
            'q'=>array('name'=>'keyword')
        ),

        'By username' => array(
            'u'=>array('name'=>'Username')
        ),
    );

    public function collectData(){
        $html = $this->getSimpleHTMLDOM('http://www.flickr.com/search/?q=vendee&s=rec') or $this->returnServerError('Could not request Flickr.');
        if ($this->getInput('q')) {   /* keyword search mode */
            $this->request = $this->getInput('q');
            $html = $this->getSimpleHTMLDOM('http://www.flickr.com/search/?q='.urlencode($this->request).'&s=rec') or $this->returnServerError('No results for this query.');
        }
        elseif ($this->getInput('u')) {   /* user timeline mode */
            $this->request = $this->getInput('u');
            $html = $this->getSimpleHTMLDOM('http://www.flickr.com/photos/'.urlencode($this->request).'/') or $this->returnServerError('Requested username can\'t be found.');
        }

        else {
            $this->returnClientError('You must specify a keyword or a Flickr username.');
        }

        foreach($html->find('span.photo_container') as $element) {
            $item = array();
            $item['uri'] = 'http://flickr.com'.$element->find('a',0)->href;
            $thumbnailUri = $element->find('img',0)->getAttribute('data-defer-src');
            $item['content'] = '<a href="' . $item['uri'] . '"><img src="' . $thumbnailUri . '" /></a>'; // FIXME: Filter javascript ?
            $item['title'] = $element->find('a',0)->title;
            $this->items[] = $item;
        }
    }

    public function getCacheDuration(){
        return 21600; // 6 hours
    }
}

