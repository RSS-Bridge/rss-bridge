<?php
/**
 * RssBridgeFlickrExplore 
 * Returns the newest interesting images from http://www.flickr.com/explore
 *
 * @name Flickr Explore
 * @description Returns the latest interesting images from Flickr
 */
class RssBridgeFlickrExplore extends RssBridgeAbstractClass
{
    protected $bridgeName = 'Flickr Explore';
    protected $bridgeURI = 'http://www.flickr.com/explore';
    protected $bridgeDescription = 'Returns the latest interesting images from Flickr';
    protected $cacheDuration = 360;  // 6 hours. No need to get more.
    protected function collectData($request) {
        $html = file_get_html('http://www.flickr.com/explore') or $this->returnError(404, 'could not request Flickr.');
        $this->items = Array();
        foreach($html->find('span.photo_container') as $element) {
            $item['uri'] = 'http://flickr.com'.$element->find('a',0)->href;
            $item['thumbnailUri'] = $element->find('img',0)->getAttribute('data-defer-src');
            $item['content'] = '<a href="'.$item['uri'].'"><img src="'.$item['thumbnailUri'].'" /></a>'; // FIXME: Filter javascript ?
            $item['title'] = $element->find('a',0)->title;
            $this->items[] = $item;
        }
    }
}

$bridge = new RssBridgeFlickrExplore();
$bridge->process();