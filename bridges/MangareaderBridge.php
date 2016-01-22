<?php
define('MANGAREADER_LIMIT', 10); // The default limit
class MangareaderBridge extends BridgeAbstract{

	public function loadMetadatas() {

		$this->maintainer = "logmanoriginal";
		$this->name = "Mangareader Bridge";
		$this->uri = "http://www.mangareader.net";
		$this->description = "Returns the latest updates, popular mangas or manga updates (new chapters)";
		$this->update = "2016-01-22";

		$this->parameters["Get latest updates"] = '[]';
        $this->parameters["Get popular mangas"] = 
        '[
            {
                "name" : "Category",
                "identifier" : "category",
                "type" : "list",
                "required" : "true",
                "values" : [
                    { 
                        "name" : "All", 
                        "value" : "all" 
                    },
                    {
                        "name" : "Action",
                        "value" : "action"
                    },
                    {
                        "name" : "Adventure",
                        "value" : "adventure"
                    },
                    {
                        "name" : "Comedy",
                        "value" : "comedy"
                    },
                    {
                        "name" : "Demons",
                        "value" : "demons"
                    },
                    {
                        "name" : "Drama",
                        "value" : "drama"
                    },
                    {
                        "name" : "Ecchi",
                        "value" : "ecchi"
                    },
                    {
                        "name" : "Fantasy",
                        "value" : "fantasy"
                    },
                    {
                        "name" : "Gender Bender",
                        "value" : "gender-bender"
                    },
                    {
                        "name" : "Harem",
                        "value" : "harem"
                    },
                    {
                        "name" : "Historical",
                        "value" : "historical"
                    },
                    {
                        "name" : "Horror",
                        "value" : "horror"
                    },
                    {
                        "name" : "Josei",
                        "value" : "josei"
                    },
                    {
                        "name" : "Magic",
                        "value" : "magic"
                    },
                    {
                        "name" : "Martial Arts",
                        "value" : "martial-arts"
                    },
                    {
                        "name" : "Mature",
                        "value" : "mature"
                    },
                    {
                        "name" : "Mecha",
                        "value" : "mecha"
                    },
                    {
                        "name" : "Military",
                        "value" : "military"
                    },
                    {
                        "name" : "Mystery",
                        "value" : "mystery"
                    },
                    {
                        "name" : "One Shot",
                        "value" : "one-shot"
                    },
                    {
                        "name" : "Psychological",
                        "value" : "psychological"
                    },
                    {
                        "name" : "Romance",
                        "value" : "romance"
                    },
                    {
                        "name" : "School Life",
                        "value" : "school-life"
                    },
                    {
                        "name" : "Sci-Fi",
                        "value" : "sci-fi"
                    },
                    {
                        "name" : "Seinen",
                        "value" : "seinen"
                    },
                    {
                        "name" : "Shoujo",
                        "value" : "shoujo"
                    },
                    {
                        "name" : "Shoujoai",
                        "value" : "shoujoai"
                    },
                    {
                        "name" : "Shounen",
                        "value" : "shounen"
                    },
                    {
                        "name" : "Shounenai",
                        "value" : "shounenai"
                    },
                    {
                        "name" : "Slice of Life",
                        "value" : "slice-of-life"
                    },
                    {
                        "name" : "Smut",
                        "value" : "smut"
                    },
                    {
                        "name" : "Sports",
                        "value" : "sports"
                    },
                    {
                        "name" : "Super Power",
                        "value" : "super-power"
                    },
                    {
                        "name" : "Supernatural",
                        "value" : "supernatural"
                    },
                    {
                        "name" : "Tragedy",
                        "value" : "tragedy"
                    },
                    {
                        "name" : "Vampire",
                        "value" : "vampire"
                    },
                    {
                        "name" : "Yaoi",
                        "value" : "yaoi"
                    },
                    {
                        "name" : "Yuri",
                        "value" : "yuri"
                    }
                ],
                "exampleValue" : "All",
                "title" : "Select your category"
            }
        ]';
        $this->parameters["Get manga updates"] = 
        '[
           {
               "name" : "Path",
               "identifier" : "path",
               "type" : "text",
               "required" : "true",
               "pattern" : "[a-zA-Z0-9-_]*",
               "exampleValue" : "bleach, umi-no-kishidan",
               "title" : "URL part of desired manga"
           },
           {
               "name" : "Limit",
               "identifier" : "limit",
               "type" : "number",
               "exampleValue" : "10",
               "title" : "Number of items to return.\n-1 returns all"
           }
        ]';
	}
    
	public function collectData(array $param){

        $this->request = '';

        $type = "latest"; // can be "latest", "popular" or "path". Default is "latest"!
        $path = "latest";
        $limit = MANGAREADER_LIMIT;
        
        if(isset($param['category'])){ // Get popular updates
            $type = "popular";
            $path = "popular";
            if($param['category'] !== "all"){
                $path .= "/" . $param['category'];
            }
        }
        
        if(isset($param['path'])){ // Get manga updates
            $type = "path";
            $path = $param['path'];
        } 
        
        if(isset($param['limit']) && $param['limit'] !== ""){ // Get manga updates (optional parameter)
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
        if($type === "latest"){
            
            $this->request = 'Latest updates';
            
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
        } 
        
        if($type === "popular"){
            
            $pagetitle = $xpath->query(".//*[@id='bodyalt']/h1")->item(0)->nodeValue;
            $this->request = substr($pagetitle, 0, strrpos($pagetitle, " -")); // "Popular mangas for ..."
            
            // Query all mangas
            $mangas = $xpath->query("//*[@id='mangaresults']/*[@class='mangaresultitem']");
            
            foreach ($mangas as $manga){ 
                
                // The thumbnail is encrypted in a css-style...
                // format: "background-image:url('<the part which is actually interesting>')"
                $mangaimgelement = $xpath->query(".//*[@class='imgsearchresults']", $manga)->item(0)->getAttribute('style'); 
                
                $item = new \Item();
                $item->title = htmlspecialchars($xpath->query(".//*[@class='manga_name']//a", $manga)->item(0)->nodeValue);
                $item->uri = 'http://www.mangareader.net' . $xpath->query(".//*[@class='manga_name']//a", $manga)->item(0)->getAttribute('href');
                $item->author = htmlspecialchars($xpath->query("//*[@class='author_name']", $manga)->item(0)->nodeValue);
                $item->chaptercount = $xpath->query(".//*[@class='chapter_count']", $manga)->item(0)->nodeValue;
                $item->genre = htmlspecialchars($xpath->query(".//*[@class='manga_genre']", $manga)->item(0)->nodeValue);
                $item->thumbnailUri = substr($mangaimgelement, 22, strlen($mangaimgelement) - 24);
                $item->content = '<a href="' . $item->uri . '"><img src="' . $item->thumbnailUri . '" alt="' . $item->title . '" /></a><p>' . $item->genre . '</p><p>' . $item->chaptercount . '</p>';
                $this->items[] = $item;
            }
        }
        
        if($type === "path") {
            
            $this->request = $xpath->query(".//*[@id='mangaproperties']//*[@class='aname']")->item(0)->nodeValue;
            
            $query = "(.//*[@id='listing']//tr)[position() > 1]";
                        
            if($limit !== -1){
                $query = "(.//*[@id='listing']//tr)[position() > 1][position() > last() - " . $limit . "]";
            } 
            
            $chapters = $xpath->query($query);
            
            foreach ($chapters as $chapter){
                $item = new \Item();
                $item->title = htmlspecialchars($xpath->query("td[1]", $chapter)->item(0)->nodeValue);
                $item->uri = 'http://www.mangareader.net' . $xpath->query("td[1]/a", $chapter)->item(0)->getAttribute('href');
                $item->timestamp = strtotime($xpath->query("td[2]", $chapter)->item(0)->nodeValue);
                array_unshift($this->items, $item);
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