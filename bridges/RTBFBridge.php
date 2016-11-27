<?php
class RTBFBridge extends BridgeAbstract {
	const NAME = "RTBF Bridge";
	const URI = "http://www.rtbf.be/auvio/emissions/";
	const CACHE_TIMEOUT = 21600; //6h
	const DESCRIPTION = "Returns the newest RTBF videos by series ID";
	const MAINTAINER = "Frenzie";

    const PARAMETERS = array( array(
        'c'=>array(
            'name'=>'series id',
            'exampleValue'=>9500,
            'required'=>true
        )
    ));

	public function collectData(){
		$html = '';
		$limit = 10;
		$count = 0;

        $html = getSimpleHTMLDOM($this->getURI())
          or returnServerError('Could not request RTBF.');

		foreach($html->find('section[id!=widget-ml-avoiraussi-] .rtbf-media-grid article') as $element) {
			if($count >= $limit) {
              break;
            }
			$item = array();
			$item['id'] = $element->getAttribute('data-id');
			$item['uri'] = self::URI.'detail?id='.$item['id'];
			$thumbnailUriSrcSet = explode(',', $element->find('figure .www-img-16by9 img', 0)->getAttribute('data-srcset'));
			$thumbnailUriLastSrc = end($thumbnailUriSrcSet);
			$thumbnailUri = explode(' ', $thumbnailUriLastSrc)[0];
			$item['title'] = trim($element->find('h3',0)->plaintext) . ' - ' . trim($element->find('h4',0)->plaintext);
			$item['timestamp'] = strtotime($element->find('time', 0)->getAttribute('datetime'));
			$item['content'] = '<a href="' . $item['uri'] . '"><img src="' . $thumbnailUri . '" /></a>';
			$this->items[] = $item;
			$count++;
		}
	}

    public function getURI(){
      return self::URI.'detail?id='.$this->getInput('c');
    }

	public function getName(){
		return $this->getInput('c') .' - RTBF Bridge';
	}
}
