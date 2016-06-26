<?php
class FrandroidBridge extends BridgeAbstract
{
	public function loadMetadatas() {

		$this->maintainer = "Daiyousei";
		$this->name = "Frandroid";
		$this->uri = "http://www.frandroid.com/";
		$this->description = "Returns the RSS feed from Frandroid (full text articles)";
		$this->update = "2015-03-05";

	}
    
    public function collectData(array $param)
    {
        
        function FrandroidStripCDATA($string)
        {
            $string = str_replace('<![CDATA[', '', $string);
            $string = str_replace(']]>', '', $string);
            return $string;
        }
        function FrandroidExtractContent($url)
        {
            $html2 = $this->file_get_html($url);
            $html3 = $html2->find('div.post-content', 0);
            $html3->find('div.no-sidebar-ad-top', 0)->outertext = '';
            $ret = $html3->find('div.shortcode-container');
            foreach ($ret as $value) {
                $value->outertext = '';
            }
            
            $html3->find('div#hrr-link', 0)->outertext = '';
            $text = $html3->innertext;
            $text = strip_tags($text, '<h1><span><h2><p><b><a><blockquote><img><em><ul><ol>');
            return $text;
        }
        $html = $this->file_get_html('http://feeds.feedburner.com/Frandroid?format=xml') or $this->returnError('Could not request Frandroid.', 404);
        $limit = 0;
        
        foreach ($html->find('item') as $element) {
            if ($limit < 5) {
                $item = new \Item();
                $item->title = FrandroidStripCDATA($element->find('title', 0)->innertext);
                $item->uri = FrandroidStripCDATA($element->find('guid', 0)->plaintext);
                $item->timestamp = strtotime($element->find('pubDate', 0)->plaintext);
                $item->content = FrandroidExtractContent($item->uri);
                $this->items[] = $item;
                $limit++;
            }
        }
        
    }
    
    public function getName()
    {
        return 'Frandroid';
    }
    
    public function getURI()
    {
        return 'http://www.frandroid.com/';
    }
    
    public function getCacheDuration()
    {
        return 300; // 5min
    }
}
