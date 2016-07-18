<?php
class NumeramaBridge extends BridgeAbstract{

	public function loadMetadatas() {

		$this->maintainer = "mitsukarenai";
		$this->name = "Numerama";
		$this->uri = "http://www.numerama.com/";
		$this->description = "Returns the 5 newest posts from Numerama (full text)";
		$this->update = "2015-10-12";

	}

    public function collectData(array $param){

    function NumeramaStripCDATA($string) {
    	$string = str_replace('<![CDATA[', '', $string);
    	$string = str_replace(']]>', '', $string);
    	return $string;
    }

   function NumeramaExtractContent($url)
      {
      $html2 = file_get_html($url);
      $text = $html2->find('section[class=related-article]', 0)->innertext = ''; // remove related articles block
      $text = '<img alt="" style="max-width:300px;" src="'.$html2->find('meta[property=og:image]', 0)->getAttribute('content').'">'; // add post picture
      $text = $text.$html2->find('article[class=post-content]', 0)->innertext; // extract the post
      return $text;
      }

   $html = $this->file_get_html('http://www.numerama.com/feed/') or $this->returnError('Could not request Numerama.', 404);
	$limit = 0;

	foreach($html->find('item') as $element) {
	 if($limit < 5) {
	 $item = new \Item();
	 $item->title = html_entity_decode(NumeramaStripCDATA($element->find('title', 0)->innertext));
	 $item->author = NumeramaStripCDATA($element->find('dc:creator', 0)->innertext);
	 $item->uri = NumeramaStripCDATA($element->find('guid', 0)->plaintext);
	 $item->timestamp = strtotime($element->find('pubDate', 0)->plaintext);
	 $item->content = NumeramaExtractContent($item->uri);
	 $this->items[] = $item;
	 $limit++;
	 }
	}

    }

    public function getName(){
        return 'Numerama';
    }

    public function getURI(){
        return 'http://www.numerama.com/';
    }

    public function getCacheDuration(){
        return 1800; // 30min
    }
}
