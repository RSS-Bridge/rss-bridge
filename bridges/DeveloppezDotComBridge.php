<?php
class DeveloppezDotComBridge extends BridgeAbstract{

	public function loadMetadatas() {

		$this->maintainer = "polopollo";
		$this->name = "Developpez.com Actus (FR)";
		$this->uri = "http://www.developpez.com/";
		$this->description = "Returns the 15 newest posts from DeveloppezDotCom (full text).";
		$this->update = "2014-07-14";

	}

    public function collectData(array $param){

        function DeveloppezDotComStripCDATA($string) {
            $string = str_replace('<![CDATA[', '', $string);
            $string = str_replace(']]>', '', $string);
            return $string;
        }

        function convert_smart_quotes($string)//F***ing quotes from Microsoft Word badly encoded, here was the trick: http://stackoverflow.com/questions/1262038/how-to-replace-microsoft-encoded-quotes-in-php
        {
            $search = array(chr(145),
                            chr(146),
                            chr(147),
                            chr(148),
                            chr(151));

            $replace = array("'",
                             "'",
                             '"',
                             '"',
                             '-');

            return str_replace($search, $replace, $string);
        }

        function DeveloppezDotComExtractContent($url) {
            $articleHTMLContent = file_get_html($url);
            $text = convert_smart_quotes($articleHTMLContent->find('div.content', 0)->innertext);
            $text = utf8_encode($text);
            return trim($text);
        }

        $rssFeed = file_get_html('http://www.developpez.com/index/rss') or $this->returnError('Could not request http://www.developpez.com/index/rss', 404);
    	$limit = 0;

    	foreach($rssFeed->find('item') as $element) {
            if($limit < 10) {
                $item = new \Item();
                $item->title = DeveloppezDotComStripCDATA($element->find('title', 0)->innertext);
                $item->uri = DeveloppezDotComStripCDATA($element->find('guid', 0)->plaintext);
                $item->timestamp = strtotime($element->find('pubDate', 0)->plaintext);
                $content = DeveloppezDotComExtractContent($item->uri);
                $item->content = strlen($content) ? $content : $element->description;//In case of it is a tutorial, we just keep the original description
                $this->items[] = $item;
                $limit++;
            }
    	}

    }

    public function getName(){
        return 'DeveloppezDotCom';
    }

    public function getURI(){
        return 'http://www.developpez.com/';
    }

    public function getCacheDuration(){
        return 1800; // 30min
    }
}
