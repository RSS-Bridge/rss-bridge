<?php
class SiliconBridge extends BridgeAbstract {

	public function loadMetadatas() {

		$this->maintainer = "ORelio";
		$this->name = 'Silicon Bridge';
		$this->uri = 'http://www.silicon.fr/';
		$this->description = "Returns the newest articles.";
		$this->update = "2016-08-09";

	}

    public function collectData(array $param) {

        function StripCDATA($string) {
            $string = str_replace('<![CDATA[', '', $string);
            $string = str_replace(']]>', '', $string);
            return $string;
        }

        $feedUrl = 'http://www.silicon.fr/feed';
        $html = $this->file_get_html($feedUrl) or $this->returnError('Could not request Silicon: '.$feedUrl, 500);
        $limit = 0;

        foreach($html->find('item') as $element) {
            if($limit < 5) {

                //Retrieve article Uri and get that page
                $article_uri = $element->innertext;
                $article_uri = substr($article_uri, strpos($article_uri, '<link>') + 6);
                $article_uri = substr($article_uri, 0, strpos($article_uri, '</link>'));
                $article_html = $this->file_get_html($article_uri) or $this->returnError('Could not request Silicon: '.$article_uri, 500);

                //Build article contents from corresponding elements
                $thumbnailUri = $element->find('enclosure', 0)->url;
                $article_content = '<p><img src="'.$thumbnailUri.'" /></p>'
                    .'<p><b>'.$article_html->find('div.entry-excerpt', 0)->plaintext.'</b></p>'
                    .$article_html->find('div.entry-content', 0)->innertext;

                //Remove useless scripts left in the page
                while (strpos($article_content, '<script') !== false) {
                    $script_section = substr($article_content, strpos($article_content, '<script'));
                    $script_section = substr($script_section, 0, strpos($script_section, '</script>') + 9);
                    $article_content = str_replace($script_section, '', $article_content);
                }

                //Build and add final item
                $item = new \Item();
                $item->uri = $article_uri;
                $item->title = StripCDATA($element->find('title', 0)->innertext);
                $item->author = StripCDATA($element->find('dc:creator', 0)->innertext);
                $item->timestamp = strtotime($element->find('pubDate', 0)->plaintext);
                $item->content = $article_content;
                $this->items[] = $item;
                $limit++;
            }
        }
    }

    public function getCacheDuration() {
        return 1800; // 30 minutes
    }
}
