<?php
class CastorusBridge extends BridgeAbstract {
	public function loadMetadatas(){
		$this->maintainer = "logmanoriginal";
		$this->name = "Castorus Bridge";
		$this->uri = 'http://www.castorus.com';
		$this->description = "Returns the latest changes";
		$this->update = "2016-08-15";

		$this->parameters["Get latest changes"] = '[]';
		$this->parameters["Get latest changes via ZIP code"] = 
		'[
			{
				"name": "ZIP code",
				"identifier" : "zip",
				"type" : "text",
				"required" : true,
				"exampleValue" : "74910, 74",
				"title" : "Insert ZIP code (complete or partial)"
			}
		]';
		$this->parameters["Get latest changes via city name"] = 
		'[
			{
				"name": "City name",
				"identifier" : "city",
				"type" : "text",
				"required" : true,
				"exampleValue" : "Seyssel, Seys",
				"title" : "Insert city name (complete or partial)"
			}
		]';
	}

	// Extracts the tile from an actitiy
	private function ExtractActivityTitle($activity){
		$title = $activity->find('a', 0);

		if(!$title)
			$this->returnError('Cannot find title!', 404);
		
		return htmlspecialchars(trim($title->plaintext));
	}

	// Extracts the url from an actitiy
	private function ExtractActivityUrl($activity){
		$url = $activity->find('a', 0);

		if(!$url)
			$this->returnError('Cannot find url!', 404);
		
		return $this->uri . $url->href;
	}

	// Extracts the time from an activity
	private function ExtractActivityTime($activity){
		// Unfortunately the time is part of the parent node, 
		// so we have to clear all child nodes first
		$nodes = $activity->find('*');

		if(!$nodes)
			$this->returnError('Cannot find nodes!', 404);
		
		foreach($nodes as $node){
			$node->outertext = '';
		}

		return strtotime($activity->innertext);
	}

	// Extracts the price change
	private function ExtractActivityPrice($activity){
		$price = $activity->find('span', 1);

		if(!$price)
			$this->returnError('Cannot find price!', 404);
		
		return $price->innertext;
	}

	public function collectData(array $params){
		if(isset($params['zip']))
			$zip_filter = trim($params['zip']);

		if(isset($params['city']))
			$city_filter = trim($params['city']);

		$html = $this->file_get_html($this->uri);

		if(!$html)
			$this->returnError('Could not load data from ' . $this->uri . '!', 404);
		
		$activities = $html->find('div#activite/li');

		if(!$activities)
			$this->returnError('Failed to find activities!', 404);
		
		foreach($activities as $activity){
			$item = new \Item();

			$item->title = $this->ExtractActivityTitle($activity);
			$item->uri = $this->ExtractActivityUrl($activity);
			$item->timestamp = $this->ExtractActivityTime($activity);
			$item->content = '<a href="' . $item->uri . '">' . $item->title . '</a><br><p>' 
								. $this->ExtractActivityPrice($activity) . '</p>';

			if(isset($zip_filter) && !(substr($item->title, 0, strlen($zip_filter)) === $zip_filter)){
				continue; // Skip this item
			}

			if(isset($city_filter) && !(substr($item->title, strpos($item->title, ' ') + 1, strlen($city_filter)) === $city_filter)){
				continue; // Skip this item
			}

			$this->items[] = $item;
		}
	}

	public function getCacheDuration(){
		return 600; // 10 minutes
	}
}
