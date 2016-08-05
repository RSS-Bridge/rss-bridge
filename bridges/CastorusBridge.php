<?php
class CastorusBridge extends BridgeAbstract {
	public function loadMetadatas(){
		$this->maintainer = "logmanoriginal";
		$this->name = "Castorus Bridge";
		$this->uri = $this->getURI();
		$this->description = "Returns the latest changes";
		$this->update = "2016-08-05";
	}

	// Extracts the tile from an actitiy
	function ExtractActivityTitle($activity){
		$title = $activity->find('a', 0);

		if(!$title)
			$this->returnError('Cannot find title!', 404);
		
		return htmlspecialchars(trim($title->plaintext));
	}

	// Extracts the url from an actitiy
	function ExtractActivityUrl($activity){
		$url = $activity->find('a', 0);

		if(!$url)
			$this->returnError('Cannot find url!', 404);
		
		return $this->getURI() . $url->href;
	}

	// Extracts the time from an activity
	function ExtractActivityTime($activity){
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
	function ExtractActivityPrice($activity){
		$price = $activity->find('span', 1);

		if(!$price)
			$this->returnError('Cannot find price!', 404);
		
		return $price->innertext;
	}

	public function collectData(array $params){
		$html = $this->file_get_html($this->getURI());

		if(!$html)
			$this->returnError('Could not load data from ' . $this->getURI() . '!', 404);
		
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

			$this->items[] = $item;
		}
	}

	public function getName(){
		return 'Castorus Bridge';
	}

	public function getURI(){
		return 'http://www.castorus.com';
	}

	public function getCacheDuration(){
		return 3600; // 1 hour
	}
}
