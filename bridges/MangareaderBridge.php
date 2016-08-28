<?php
define('MANGAREADER_LIMIT', 10); // The default limit
class MangareaderBridge extends BridgeAbstract{

	public $maintainer = "logmanoriginal";
	public $name = "Mangareader Bridge";
	public $uri = "http://www.mangareader.net";
	public $description = "Returns the latest updates, popular mangas or manga updates (new chapters)";

    public $parameters = array(
        'Get latest updates' => array(),
        'Get popular mangas' => array(
          'category'=>array(
            'name'=>'Category',
            'type'=>'list',
            'required'=>true,
            'values'=>array(
              'All'=>'all',
              'Action'=>'action',
              'Adventure'=>'adventure',
              'Comedy'=>'comedy',
              'Demons'=>'demons',
              'Drama'=>'drama',
              'Ecchi'=>'ecchi',
              'Fantasy'=>'fantasy',
              'Gender Bender'=>'gender-bender',
              'Harem'=>'harem',
              'Historical'=>'historical',
              'Horror'=>'horror',
              'Josei'=>'josei',
              'Magic'=>'magic',
              'Martial Arts'=>'martial-arts',
              'Mature'=>'mature',
              'Mecha'=>'mecha',
              'Military'=>'military',
              'Mystery'=>'mystery',
              'One Shot'=>'one-shot',
              'Psychological'=>'psychological',
              'Romance'=>'romance',
              'School Life'=>'school-life',
              'Sci-Fi'=>'sci-fi',
              'Seinen'=>'seinen',
              'Shoujo'=>'shoujo',
              'Shoujoai'=>'shoujoai',
              'Shounen'=>'shounen',
              'Shounenai'=>'shounenai',
              'Slice of Life'=>'slice-of-life',
              'Smut'=>'smut',
              'Sports'=>'sports',
              'Super Power'=>'super-power',
              'Supernatural'=>'supernatural',
              'Tragedy'=>'tragedy',
              'Vampire'=>'vampire',
              'Yaoi'=>'yaoi',
              'Yuri'=>'yuri'
            ),
            'exampleValue'=>'All',
            'title'=>'Select your category'
          )
        ),
        'Get manga updates' => array(
          'path'=>array(
            'name'=>'Path',
            'required'=>true,
            'pattern'=>'[a-zA-Z0-9-_]*',
            'exampleValue'=>'bleach, umi-no-kishidan',
            'title'=>'URL part of desired manga'
          ),
          'limit'=>array(
            'name'=>'Limit',
            'type'=>'number',
            'exampleValue'=>10,
            'title'=>'Number of items to return [-1 returns all]'
          )
      )
  );

	public function collectData(){

        $this->request = '';

        $type = "latest"; // can be "latest", "popular" or "path". Default is "latest"!
        $path = "latest";
        $limit = MANGAREADER_LIMIT;

        if(isset($this->getInput('category'))){ // Get popular updates
            $type = "popular";
            $path = "popular";
            if($this->getInput('category') !== "all"){
                $path .= "/" . $this->getInput('category');
            }
        }

        if(isset($this->getInput('path'))){ // Get manga updates
            $type = "path";
            $path = $this->getInput('path');
        }

        if(isset($this->getInput('limit')) && $this->getInput('limit') !== ""){ // Get manga updates (optional parameter)
            $limit = $this->getInput('limit');
        }

		// We'll use the DOM parser for this as it makes navigation easier
		$html = $this->getContents("http://www.mangareader.net/" . $path);
        if(!$html){
            $this->returnClientError('Could not receive data for ' . $path . '!');
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
                    $item = array();
                    $item['uri'] = 'http://www.mangareader.net' . htmlspecialchars($manga->getAttribute('href'));
                    $item['title'] = htmlspecialchars($manga->nodeValue);

                    // Add each chapter to the feed
                    $item['content'] = "";

                    foreach ($chapters as $chapter){
                        if($item['content'] <> ""){
                            $item['content'] .= "<br>";
                        }
                        $item['content'] .= "<a href='http://www.mangareader.net" . htmlspecialchars($chapter->getAttribute('href')) . "'>" . htmlspecialchars($chapter->nodeValue) . "</a>";
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
                $thumbnail = substr($mangaimgelement, 22, strlen($mangaimgelement) - 24);

                $item = array();
                $item['title'] = htmlspecialchars($xpath->query(".//*[@class='manga_name']//a", $manga)->item(0)->nodeValue);
                $item['uri'] = 'http://www.mangareader.net' . $xpath->query(".//*[@class='manga_name']//a", $manga)->item(0)->getAttribute('href');
                $item['author'] = htmlspecialchars($xpath->query("//*[@class='author_name']", $manga)->item(0)->nodeValue);
                $item['chaptercount'] = $xpath->query(".//*[@class='chapter_count']", $manga)->item(0)->nodeValue;
                $item['genre'] = htmlspecialchars($xpath->query(".//*[@class='manga_genre']", $manga)->item(0)->nodeValue);
                $item['content'] = '<a href="' . $item['uri'] . '"><img src="' . $thumbnail . '" alt="' . $item['title'] . '" /></a><p>' . $item['genre'] . '</p><p>' . $item['chaptercount'] . '</p>';
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
                $item = array();
                $item['title'] = htmlspecialchars($xpath->query("td[1]", $chapter)->item(0)->nodeValue);
                $item['uri'] = 'http://www.mangareader.net' . $xpath->query("td[1]/a", $chapter)->item(0)->getAttribute('href');
                $item['timestamp'] = strtotime($xpath->query("td[2]", $chapter)->item(0)->nodeValue);
                array_unshift($this->items, $item);
            }
        }

		// Return some dummy-data if no content available
		if(count($this->items) == 0){
			$item = array();
			$item['content'] = "<p>No updates available</p>";

			$this->items[] = $item;
		}
	}

	public function getName(){
		return (!empty($this->request) ? $this->request . ' - ' : '') . 'Mangareader Bridge';
	}

	public function getCacheDuration(){
		return 10800; // 3 hours
	}
}
?>
