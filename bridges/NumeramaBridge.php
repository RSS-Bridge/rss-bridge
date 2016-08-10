<?php
class NumeramaBridge extends BridgeAbstract{

    public function loadMetadatas() {

        $this->maintainer = 'mitsukarenai';
        $this->name = 'Numerama';
        $this->uri = 'http://www.numerama.com/';
        $this->description = 'Returns the 5 newest posts from Numerama (full text)';
        $this->update = '2016-08-09';

    }

    public function collectData(array $param) {

        function NumeramaStripCDATA($string) {
            $string = str_replace('<![CDATA[', '', $string);
            $string = str_replace(']]>', '', $string);
            return $string;
        }

        $feed = $this->uri.'feed/';
        $html = $this->file_get_html($feed) or $this->returnError('Could not request Numerama: '.$feed, 500);
        $limit = 0;

        foreach($html->find('item') as $element) {
            if($limit < 5) {
                $item = new \Item();
                $item->title = html_entity_decode(NumeramaStripCDATA($element->find('title', 0)->innertext));
                $item->author = NumeramaStripCDATA($element->find('dc:creator', 0)->innertext);
                $item->uri = NumeramaStripCDATA($element->find('guid', 0)->plaintext);
                $item->timestamp = strtotime($element->find('pubDate', 0)->plaintext);

                $article_url = NumeramaStripCDATA($element->find('guid', 0)->plaintext);
                $article_html = $this->file_get_html($article_url) or $this->returnError('Could not request Numerama: '.$article_url, 500);
                $contents = $article_html->find('section[class=related-article]', 0)->innertext = ''; // remove related articles block
                $contents = '<img alt="" style="max-width:300px;" src="'.$article_html->find('meta[property=og:image]', 0)->getAttribute('content').'">'; // add post picture
                $contents = $contents.$article_html->find('article[class=post-content]', 0)->innertext; // extract the post

                $item->content = $contents;
                $this->items[] = $item;
                $limit++;
            }
        }

    }

    public function getCacheDuration() {
        return 1800; // 30min
    }
}
