<?php
/**
* RssBridgeFlickrTagUser 
* Returns the tagged images from http://www.flickr.com/
*
* @name Flickr TagUser
* @description Returns the tagged or user images from Flickr
* @use1(q="keyword")
* @use2(u="username")
*/
class FlickrTagBridge extends BridgeAbstract{

    public function collectData(array $param){
        $html = file_get_html('http://www.flickr.com/search/?q=vendee&s=rec') or $this->returnError('Could not request Flickr.', 404);
        if (isset($param['q'])) {   /* keyword search mode */
            $this->request = $param['q'];
            $html = file_get_html('http://www.flickr.com/search/?q='.urlencode($this->request).'&s=rec') or $this->returnError('No results for this query.', 404);
        }
        elseif (isset($param['u'])) {   /* user timeline mode */
            $this->request = $param['u'];
            $html = file_get_html('http://www.flickr.com/photos/'.urlencode($this->request).'/') or $this->returnError('Requested username can\'t be found.', 404);
        }
        
        else {
            $this->returnError('You must specify a keyword or a Flickr username.', 400);
        }

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
        return 'Flickr Tag';
    }

    public function getURI(){
        return 'http://www.flickr.com/search/';
    }

    public function getCacheDuration(){
        return 21600; // 6 hours
    }
}
