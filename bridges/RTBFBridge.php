<?php
class RTBFBridge extends BridgeAbstract {
	public $name = "RTBF Bridge";
	public $uri = "http://www.rtbf.be/auvio/emissions";
	public $description = "Returns the newest RTBF videos by series ID";
	public $maintainer = "Frenzie";

    public $parameters = array( array(
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

		if ($this->getInput('c')) {
			$html = $this->getSimpleHTMLDOM('http://www.rtbf.be/auvio/emissions/detail?id='.$this->getInput('c')) or $this->returnServerError('Could not request RTBF.');

			foreach($html->find('section[id!=widget-ml-avoiraussi-] .rtbf-media-grid article') as $element) {
				if($count < $limit) {
					$item = array();
					$item['id'] = $element->getAttribute('data-id');
					$item['uri'] = 'http://www.rtbf.be/auvio/detail?id='.$item['id'];
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
		}
		else {
			$this->returnClientError('You must specify a series id.');
		}
	}

	public function getName(){
		return (!empty($this->request) ? $this->request .' - ' : '') .'RTBF Bridge';
	}

	public function getCacheDuration(){
		return 21600; // 6 hours
	}
}
