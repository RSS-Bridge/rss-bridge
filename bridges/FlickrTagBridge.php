<?php
class FlickrTagBridge extends BridgeAbstract{

	public $maintainer = "erwang";
	public $name = "Flickr TagUser";
	public $uri = "http://www.flickr.com/";
	public $description = "Returns the tagged or user images from Flickr";

    public $parameters = array(
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
            $html = $this->getSimpleHTMLDOM($this->uri.'search/?q='.urlencode($this->getInput('q')).'&s=rec')
                or $this->returnServerError('No results for this query.');
            break;
        case 'by username':
            $html = $this->getSimpleHTMLDOM($this->uri.'photos/'.urlencode($this->getInput('u')).'/')
                or $this->returnServerError('Requested username can\'t be found.');
            break;
        }

        foreach($html->find('span.photo_container') as $element) {
            $item = array();
            $item['uri'] = $this->uri.$element->find('a',0)->href;
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

