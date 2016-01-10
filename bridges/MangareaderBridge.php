<?php

class MangareaderBridge extends BridgeAbstract{

	public function loadMetadatas() {

		$this->maintainer = "logmanoriginal";
		$this->name = "Mangareader Bridge";
		$this->uri = "http://www.mangareader.net";
		$this->description = "Returns the latest Manga updates";
		$this->update = "2016-01-10";

		$this->parameters["Get latest updates"] = '[]';

	}
    
	public function collectData(array $param){

		/* We'll use the DOM parser for this as it makes navigation easier */
		$html = file_get_contents("http://www.mangareader.net/latest");
		$doc = new DomDocument;
		@$doc->loadHTML($html);
		
		/* The latest updates are on the frontpage, navigate via XPath */
		$xpath = new DomXPath($doc);

		/* Query each item (consists of Manga + chapters) */
		$nodes = $xpath->query("//*[@id='latestchapters']/table//td");

		foreach ($nodes as $node){
			/* Query the manga */
			$manga = $xpath->query("a[@class='chapter']", $node)->item(0);
			
			/* Collect the chapters for each Manga */
			$chapters = $xpath->query("a[@class='chaptersrec']", $node);

			if (isset($manga) && $chapters->length >= 1){
				$item = new \Item();
				$item->uri = 'http://www.mangareader.net' . htmlspecialchars($manga->getAttribute('href'));
				$item->title = htmlspecialchars($manga->nodeValue);
				
				/* Add each chapter to the feed */		
				$item->content = "";
				
				foreach ($chapters as $chapter){
					if($item->content <> ""){
						$item->content .= "<br>";
					}
					$item->content .= "<a href='http://www.mangareader.net" . htmlspecialchars($chapter->getAttribute('href')) . "'>" . htmlspecialchars($chapter->nodeValue) . "</a>";
				}
				
				$this->items[] = $item;
			}
		}
		
		/* Return some dummy-data if no content available */
		if(count($this->items) == 0){
			$item = new \Item();
			$item->content = "<p>No updates available</p>";
			
			$this->items[] = $item;
		}		
	}

	public function getName(){
		return 'Mangareader Bridge';
	}

	public function getURI(){
		return 'http://www.mangareader.net';
	}

	public function getCacheDuration(){
		return 10800; // 3 hours
	}
}
?>
