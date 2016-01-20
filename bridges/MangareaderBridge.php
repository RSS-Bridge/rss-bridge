<?php
define('MANGAREADER_LIMIT', 10); // The default limit
class MangareaderBridge extends BridgeAbstract{

	public function loadMetadatas() {

		$this->maintainer = "logmanoriginal";
		$this->name = "Mangareader Bridge";
		$this->uri = "http://www.mangareader.net";
		$this->description = "Returns the latest updates. Set limits to -1 to disable the limit.";
		$this->update = "2016-01-20";

		$this->parameters["Get site updates"] = '[]';
        $this->parameters["Get manga updates"] = '
        [
           {
               "name" : "Manga path",
               "identifier" : "path",
               "type" : "text",
               "required" : "true",
               "pattern" : "[a-zA-Z0-9-_]*",
               "exampleValue" : "bleach, umi-no-kishidan"
           },
           {
               "name" : "Limit",
               "identifier" : "limit",
               "type" : "number",
               "exampleValue" : "10"
           }
        ]';
	}
    
	public function collectData(array $param){

        $this->request = '';

        $path = "latest";
        $limit = MANGAREADER_LIMIT;
        
        if(isset($param['path'])){
            $path = $param['path'];
        }
        
        if(isset($param['limit']) && $param['limit'] !== ""){
            $limit = $param['limit'];
        }
        
		// We'll use the DOM parser for this as it makes navigation easier
		$html = file_get_contents("http://www.mangareader.net/" . $path);
        if(!$html){
            $this->returnError('Could not receive data for ' . $path . '!', 400);
        }
        $doc = new DomDocument;
		@$doc->loadHTML($html);
		
		// Navigate via XPath
		$xpath = new DomXPath($doc);

        // Build feed based on the context (site updates or manga updates)
        if($path === "latest"){
            
            $this->request = 'Latest';
            
            // Query each item (consists of Manga + chapters)
            $nodes = $xpath->query("//*[@id='latestchapters']/table//td");

            foreach ($nodes as $node){
                // Query the manga
                $manga = $xpath->query("a[@class='chapter']", $node)->item(0);
                
                // Collect the chapters for each Manga
                $chapters = $xpath->query("a[@class='chaptersrec']", $node);

                if (isset($manga) && $chapters->length >= 1){
                    $item = new \Item();
                    $item->uri = 'http://www.mangareader.net' . htmlspecialchars($manga->getAttribute('href'));
                    $item->title = htmlspecialchars($manga->nodeValue);
                    
                    // Add each chapter to the feed	
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
        } else {
            
            $this->request = $xpath->query(".//*[@id='mangaproperties']//*[@class='aname']")->item(0)->nodeValue;
            
            $query = "(.//*[@id='listing']//tr)[position() > 1]";
                        
            if($limit !== -1){
                $query = "(.//*[@id='listing']//tr)[position() > 1][position() > last() - " . $limit . "]";
            } 
            
            $chapters = $xpath->query($query);
            
            foreach ($chapters as $chapter){
                $item = new \Item();
                $item->title = $xpath->query("td[1]", $chapter)->item(0)->nodeValue;
                $item->uri = 'http://www.mangareader.net' . $xpath->query("td[1]/a", $chapter)->item(0)->getAttribute('href');
                $item->description = substr($xpath->query("td[1]", $chapter)->item(0)->nodeValue, strrpos($item->title, ": ") + 2);
                $item->date = $xpath->query("td[2]", $chapter)->item(0)->nodeValue;
                $item->content = $item->description . "<br/><time datetime=\"" . $item->date . "\">" . $item->date . "</time>";
                $this->items[] = $item;
            }           
        }
        
		// Return some dummy-data if no content available
		if(count($this->items) == 0){
			$item = new \Item();
			$item->content = "<p>No updates available</p>";
			
			$this->items[] = $item;
		}		
	}

	public function getName(){
		return (!empty($this->request) ? $this->request . ' - ' : '') . 'Mangareader Bridge';
	}

	public function getURI(){
		return 'http://www.mangareader.net';
	}

	public function getCacheDuration(){
		return 10800; // 3 hours
	}
}
?>