<?php
class LeMondeInformatiqueBridge extends BridgeAbstract {

    public function loadMetadatas() {

        $this->maintainer = "ORelio";
        $this->name = "Le Monde Informatique";
        $this->uri = "http://www.lemondeinformatique.fr/";
        $this->description = "Returns the newest articles.";
        $this->update = "2016-08-09";

    }

    public function collectData(array $param) {

        function StripCDATA($string) {
            $string = str_replace('<![CDATA[', '', $string);
            $string = str_replace(']]>', '', $string);
            return $string;
        }

        function StripWithDelimiters($string, $start, $end) {
            while (strpos($string, $start) !== false) {
                $section_to_remove = substr($string, strpos($string, $start));
                $section_to_remove = substr($section_to_remove, 0, strpos($section_to_remove, $end) + strlen($end));
                $string = str_replace($section_to_remove, '', $string);
            } return $string;
        }

        function CleanArticle($article_html) {
            $article_html = StripWithDelimiters($article_html, '<script', '</script>');
            $article_html = StripWithDelimiters($article_html, '<h1 class="cleanprint-title"', '</h1>');
            return $article_html;
        }

        $feedUrl = 'http://www.lemondeinformatique.fr/rss/rss.xml';
        $html = $this->file_get_html($feedUrl) or $this->returnError('Could not request LeMondeInformatique: '.$feedUrl, 500);
        $limit = 0;

        foreach($html->find('item') as $element) {
            if($limit < 5) {

                //Retrieve article details
                $article_uri = $element->innertext;
                $article_uri = substr($article_uri, strpos($article_uri, '<link>') + 6);
                $article_uri = substr($article_uri, 0, strpos($article_uri, '</link>'));
                $article_html = $this->file_get_html($article_uri) or $this->returnError('Could not request LeMondeInformatique: '.$article_uri, 500);
                $article_content = CleanArticle($article_html->find('div#article', 0)->innertext);
                $article_title = $article_html->find('h1.cleanprint-title', 0)->plaintext;

                //Build and add final item
                $item = new \Item();
                $item->uri = $article_uri;
                $item->title = $article_title;
                $item->author = StripCDATA($element->find('dc:creator', 0)->innertext);
                $item->timestamp = strtotime($element->find('dc:date', 0)->plaintext);
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
