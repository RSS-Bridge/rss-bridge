<?php
class FlickrTagBridge extends BridgeAbstract{

	const MAINTAINER = "erwang";
	const NAME = "Flickr TagUser";
	const URI = "http://www.flickr.com/";
	const CACHE_TIMEOUT = 21600; //6h
	const DESCRIPTION = "Returns the tagged or user images from Flickr";

    const PARAMETERS = array(
        'By keyword' => array(
            'q'=>array(
                'name'=>'keyword',
                'required'=>true
            )
        ),

        'By username' => array(
            'u'=>array(
                'name'=>'Username',
                'required'=>true
            )
        ),
    );

    public function collectData(){
        switch($this->queriedContext){
        case 'By keyword':
            $html = getSimpleHTMLDOM(self::URI.'search/?q='.urlencode($this->getInput('q')).'&s=rec')
                or returnServerError('No results for this query.');
            break;
        case 'by username':
            $html = getSimpleHTMLDOM(self::URI.'photos/'.urlencode($this->getInput('u')).'/')
                or returnServerError('Requested username can\'t be found.');
            break;
        }

        foreach($html->find('span.photo_container') as $element) {
            $item = array();
            $item['uri'] = self::URI.$element->find('a',0)->href;
            $thumbnailUri = $element->find('img',0)->getAttribute('data-defer-src');
            $item['content'] = '<a href="' . $item['uri'] . '"><img src="' . $thumbnailUri . '" /></a>'; // FIXME: Filter javascript ?
            $item['title'] = $element->find('a',0)->title;
            $this->items[] = $item;
        }
    }
}

