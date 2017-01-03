<?php
class MangareaderBridge extends BridgeAbstract {

    const MAINTAINER = "logmanoriginal";
    const NAME = "Mangareader Bridge";
    const URI = "http://www.mangareader.net/";
    const CACHE_TIMEOUT = 10800; // 3h
    const DESCRIPTION = "Returns the latest updates, popular mangas or manga updates (new chapters)";

    const PARAMETERS = array(
        'Get latest updates' => array(),
        'Get popular mangas' => array(
          'category' => array(
            'name' => 'Category',
            'type' => 'list',
            'required' => true,
            'values' => array(
              'All' => 'all',
              'Action' => 'action',
              'Adventure' => 'adventure',
              'Comedy' => 'comedy',
              'Demons' => 'demons',
              'Drama' => 'drama',
              'Ecchi' => 'ecchi',
              'Fantasy' => 'fantasy',
              'Gender Bender' => 'gender-bender',
              'Harem' => 'harem',
              'Historical' => 'historical',
              'Horror' => 'horror',
              'Josei' => 'josei',
              'Magic' => 'magic',
              'Martial Arts' => 'martial-arts',
              'Mature' => 'mature',
              'Mecha' => 'mecha',
              'Military' => 'military',
              'Mystery' => 'mystery',
              'One Shot' => 'one-shot',
              'Psychological' => 'psychological',
              'Romance' => 'romance',
              'School Life' => 'school-life',
              'Sci-Fi' => 'sci-fi',
              'Seinen' => 'seinen',
              'Shoujo' => 'shoujo',
              'Shoujoai' => 'shoujoai',
              'Shounen' => 'shounen',
              'Shounenai' => 'shounenai',
              'Slice of Life' => 'slice-of-life',
              'Smut' => 'smut',
              'Sports' => 'sports',
              'Super Power' => 'super-power',
              'Supernatural' => 'supernatural',
              'Tragedy' => 'tragedy',
              'Vampire' => 'vampire',
              'Yaoi' => 'yaoi',
              'Yuri' => 'yuri'
            ),
            'exampleValue' => 'All',
            'title' => 'Select your category'
          )
        ),
        'Get manga updates' => array(
          'path' => array(
            'name' => 'Path',
            'required' => true,
            'pattern' => '[a-zA-Z0-9-_]*',
            'exampleValue' => 'bleach, umi-no-kishidan',
            'title' => 'URL part of desired manga'
          ),
          'limit' => array(
            'name' => 'Limit',
            'type' => 'number',
            'defaultValue' => 10,
            'title' => 'Number of items to return [-1 returns all]'
          )
      )
  );

    private $request = '';

    public function collectData(){
        // We'll use the DOM parser for this as it makes navigation easier
        $html = getContents($this->getURI());
        if(!$html){
            returnClientError('Could not receive data for ' . $path . '!');
        }
        libxml_use_internal_errors(true);
        $doc = new DomDocument;
        @$doc->loadHTML($html);
        libxml_clear_errors();

        // Navigate via XPath
        $xpath = new DomXPath($doc);

        $this->request = '';
        switch($this->queriedContext){
        case 'Get latest updates':
            $this->request = 'Latest updates';
            $this->get_latest_updates($xpath);
            break;
        case 'Get popular mangas':
            // Find manga name within "Popular mangas for ..."
            $pagetitle = $xpath->query(".//*[@id='bodyalt']/h1")->item(0)->nodeValue;
            $this->request = substr($pagetitle, 0, strrpos($pagetitle, " -"));
            $this->get_popular_mangas($xpath);
            break;
        case 'Get manga updates':
            $limit = $this->getInput('limit');
            if(empty($limit)){
                $limit = self::PARAMETERS[$this->queriedContext]['limit']['defaultValue'];
            }

            $this->request = $xpath->query(".//*[@id='mangaproperties']//*[@class='aname']")
                ->item(0)
                ->nodeValue;

            $this->get_manga_updates($xpath, $limit);
            break;
        }

        // Return some dummy-data if no content available
        if(empty($this->items)){
            $item = array();
            $item['content'] = "<p>No updates available</p>";

            $this->items[] = $item;
        }
    }

    private function get_latest_updates($xpath){
        // Query each item (consists of Manga + chapters)
        $nodes = $xpath->query("//*[@id='latestchapters']/table//td");

        foreach ($nodes as $node){
            // Query the manga
            $manga = $xpath->query("a[@class='chapter']", $node)->item(0);

            // Collect the chapters for each Manga
            $chapters = $xpath->query("a[@class='chaptersrec']", $node);

            if (isset($manga) && $chapters->length >= 1){
                $item = array();
                $item['uri'] = self::URI . htmlspecialchars($manga->getAttribute('href'));
                $item['title'] = htmlspecialchars($manga->nodeValue);

                // Add each chapter to the feed
                $item['content'] = "";

                foreach ($chapters as $chapter){
                    if($item['content'] <> ""){
                        $item['content'] .= "<br>";
                    }
                    $item['content'] .=
                        "<a href='"
                        . self::URI
                        . htmlspecialchars($chapter->getAttribute('href'))
                        . "'>"
                        . htmlspecialchars($chapter->nodeValue)
                        . "</a>";
                }

                $this->items[] = $item;
            }
        }
    }

    private function get_popular_mangas($xpath){
        // Query all mangas
        $mangas = $xpath->query("//*[@id='mangaresults']/*[@class='mangaresultitem']");

        foreach ($mangas as $manga){

            // The thumbnail is encrypted in a css-style...
            // format: "background-image:url('<the part which is actually interesting>')"
            $mangaimgelement = $xpath->query(".//*[@class='imgsearchresults']", $manga)
                ->item(0)
                ->getAttribute('style');
            $thumbnail = substr($mangaimgelement, 22, strlen($mangaimgelement) - 24);

            $item = array();
            $item['title'] = htmlspecialchars($xpath->query(".//*[@class='manga_name']//a", $manga)
                ->item(0)
                ->nodeValue);
            $item['uri'] = self::URI . $xpath->query(".//*[@class='manga_name']//a", $manga)
                ->item(0)
                ->getAttribute('href');
            $item['author'] = htmlspecialchars($xpath->query("//*[@class='author_name']", $manga)
                ->item(0)
                ->nodeValue);
            $item['chaptercount'] = $xpath->query(".//*[@class='chapter_count']", $manga)
                ->item(0)
                ->nodeValue;
            $item['genre'] = htmlspecialchars($xpath->query(".//*[@class='manga_genre']", $manga)
                ->item(0)
                ->nodeValue);
            $item['content'] = <<<EOD
<a href="{$item['uri']}"><img src="{$thumbnail}" alt="{$item['title']}" /></a>
<p>{$item['genre']}</p>
<p>{$item['chaptercount']}</p>
EOD;
            $this->items[] = $item;
        }
    }

    private function get_manga_updates($xpath, $limit){
        $query = "(.//*[@id='listing']//tr)[position() > 1]";

        if($limit !== -1){
            $query = "(.//*[@id='listing']//tr)[position() > 1][position() > last() - {$limit}]";
        }

        $chapters = $xpath->query($query);

        foreach ($chapters as $chapter){
            $item = array();
            $item['title'] = htmlspecialchars($xpath->query("td[1]", $chapter)
                ->item(0)
                ->nodeValue);
            $item['uri'] = self::URI . $xpath->query("td[1]/a", $chapter)
                ->item(0)
                ->getAttribute('href');
            $item['timestamp'] = strtotime($xpath->query("td[2]", $chapter)
                ->item(0)
                ->nodeValue);
            array_unshift($this->items, $item);
        }
    }

    public function getURI(){
        switch($this->queriedContext){
        case 'Get latest updates':
            $path = "latest";
            break;
        case 'Get popular mangas':
            $path = "popular";
            if($this->getInput('category') !== "all"){
                $path .= "/" . $this->getInput('category');
            }
            break;
        case 'Get manga updates':
            $path = $this->getInput('path');
            break;
        default: return parent::getURI();
        }
        return self::URI . $path;
    }


    public function getName(){
        return (!empty($this->request) ? $this->request . ' - ' : '') . 'Mangareader Bridge';
    }
}
?>
