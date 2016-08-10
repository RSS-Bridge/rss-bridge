<?php
class NeuviemeArtBridge extends BridgeAbstract {

    public function loadMetadatas() {
        $this->maintainer = "ORelio";
        $this->name = '9ème Art Bridge';
        $this->uri = "http://www.9emeart.fr/";
        $this->description = "Returns the newest articles.";
        $this->update = "2016-08-09";
    }

    public function collectData(array $param) {

        function StripWithDelimiters($string, $start, $end) {
            while (strpos($string, $start) !== false) {
                $section_to_remove = substr($string, strpos($string, $start));
                $section_to_remove = substr($section_to_remove, 0, strpos($section_to_remove, $end) + strlen($end));
                $string = str_replace($section_to_remove, '', $string);
            } return $string;
        }

        $feedUrl = 'http://www.9emeart.fr/9emeart.rss';
        $html = $this->file_get_html($feedUrl) or $this->returnError('Could not request 9eme Art: '.$feedUrl, 500);
        $limit = 0;

        foreach ($html->find('item') as $element) {
            if ($limit < 5) {

                //Retrieve article Uri and get that page
                $article_uri = $element->find('guid', 0)->plaintext;
                $article_html = $this->file_get_html($article_uri) or $this->returnError('Could not request 9eme Art: '.$article_uri, 500);

                //Build article contents from corresponding elements
                $article_title = trim($element->find('title', 0)->plaintext);
                $article_image = $element->find('enclosure', 0)->url;
                foreach ($article_html->find('img.img_full') as $img)
                    if ($img->alt == $article_title)
                        $article_image = 'http://www.9emeart.fr'.$img->src;
                $article_content = '<p><img src="'.$article_image.'" /></p>'
                    .str_replace('src="/', 'src="http://www.9emeart.fr/', $article_html->find('div.newsGenerique_con', 0)->innertext);
                $article_content = StripWithDelimiters($article_content, '<script', '</script>');
                $article_content = StripWithDelimiters($article_content, '<style', '</style>');
                $article_content = StripWithDelimiters($article_content, '<link', '>');

                //Build and add final item
                $item = new \Item();
                $item->uri = $article_uri;
                $item->title = $article_title;
                $item->author = $article_html->find('a[class=upp transition_fast upp]', 0)->plaintext;
                $item->timestamp = strtotime($element->find('pubDate', 0)->plaintext);
                $item->content = $article_content;
                $this->items[] = $item;
                $limit++;
            }
        }
    }
}
