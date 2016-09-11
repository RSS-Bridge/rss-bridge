<?php
class SiliconBridge extends BridgeAbstract {

	const MAINTAINER = "ORelio";
	const NAME = 'Silicon Bridge';
	const URI = 'http://www.silicon.fr/';
	const DESCRIPTION = "Returns the newest articles.";

    public function collectData(){

        function StripCDATA($string) {
            $string = str_replace('<![CDATA[', '', $string);
            $string = str_replace(']]>', '', $string);
            return $string;
        }

        $feedUrl = self::URI.'feed';
        $html = $this->getSimpleHTMLDOM($feedUrl)
          or $this->returnServerError('Could not request Silicon: '.$feedUrl);
        $limit = 0;

        foreach($html->find('item') as $element) {
            if($limit < 5) {

                //Retrieve article Uri and get that page
                $article_uri = $element->innertext;
                $article_uri = substr($article_uri, strpos($article_uri, '<link>') + 6);
                $article_uri = substr($article_uri, 0, strpos($article_uri, '</link>'));
                $article_html = $this->getSimpleHTMLDOM($article_uri)
                  or $this->returnServerError('Could not request Silicon: '.$article_uri);

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
                $item = array();
                $item['uri'] = $article_uri;
                $item['title'] = StripCDATA($element->find('title', 0)->innertext);
                $item['author'] = StripCDATA($element->find('dc:creator', 0)->innertext);
                $item['timestamp'] = strtotime($element->find('pubDate', 0)->plaintext);
                $item['content'] = $article_content;
                $this->items[] = $item;
                $limit++;
            }
        }
    }

    public function getCacheDuration() {
        return 1800; // 30 minutes
    }
}
