<?php
class CastorusBridge extends BridgeAbstract {
	public $maintainer = "logmanoriginal";
	public $name = "Castorus Bridge";
	public $uri = 'http://www.castorus.com';
	public $description = "Returns the latest changes";

    public $parameters = array(
        'Get latest changes' => array(),
        'Get latest changes via ZIP code' => array(
            'zip'=>array(
                'name'=>'ZIP code',
                'type'=>'text',
                'required'=>true,
                'exampleValue'=>'74910, 74',
                'title'=>'Insert ZIP code (complete or partial)'
            )
        ),
        'Get latest changes via city name' => array(
            'city'=>array(
                'name'=>'City name',
                'type'=>'text',
                'required'=>true,
                'exampleValue'=>'Seyssel, Seys',
                'title'=>'Insert city name (complete or partial)'
            )
        )
    );

	// Extracts the tile from an actitiy
	private function ExtractActivityTitle($activity){
		$title = $activity->find('a', 0);

		if(!$title)
			$this->returnServerError('Cannot find title!');

		return htmlspecialchars(trim($title->plaintext));
	}

	// Extracts the url from an actitiy
	private function ExtractActivityUrl($activity){
		$url = $activity->find('a', 0);

		if(!$url)
			$this->returnServerError('Cannot find url!');

		return $this->uri . $url->href;
	}

	// Extracts the time from an activity
	private function ExtractActivityTime($activity){
		// Unfortunately the time is part of the parent node,
		// so we have to clear all child nodes first
		$nodes = $activity->find('*');

		if(!$nodes)
			$this->returnServerError('Cannot find nodes!');

		foreach($nodes as $node){
			$node->outertext = '';
		}

		return strtotime($activity->innertext);
	}

	// Extracts the price change
	private function ExtractActivityPrice($activity){
		$price = $activity->find('span', 1);

		if(!$price)
			$this->returnServerError('Cannot find price!');

		return $price->innertext;
	}

	public function collectData(){
        $params=$this->parameters[$this->queriedContext];
		if(isset($params['zip']['value']))
			$zip_filter = trim($params['zip']['value']);

		if(isset($params['city']['value']))
			$city_filter = trim($params['city']['value']);

		$html = $this->getSimpleHTMLDOM($this->uri);

		if(!$html)
			$this->returnServerError('Could not load data from ' . $this->uri . '!');

		$activities = $html->find('div#activite/li');

		if(!$activities)
			$this->returnServerError('Failed to find activities!');

		foreach($activities as $activity){
			$item = array();

			$item['title'] = $this->ExtractActivityTitle($activity);
			$item['uri'] = $this->ExtractActivityUrl($activity);
			$item['timestamp'] = $this->ExtractActivityTime($activity);
			$item['content'] = '<a href="' . $item['uri'] . '">' . $item['title'] . '</a><br><p>'
								. $this->ExtractActivityPrice($activity) . '</p>';

			if(isset($zip_filter) && !(substr($item['title'], 0, strlen($zip_filter)) === $zip_filter)){
				continue; // Skip this item
			}

			if(isset($city_filter) && !(substr($item['title'], strpos($item['title'], ' ') + 1, strlen($city_filter)) === $city_filter)){
				continue; // Skip this item
			}

			$this->items[] = $item;
		}
	}

	public function getCacheDuration(){
		return 600; // 10 minutes
	}
}
