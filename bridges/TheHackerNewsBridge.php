<?php
class TheHackerNewsBridge extends BridgeAbstract {

    const MAINTAINER = 'ORelio';
    const NAME = 'The Hacker News Bridge';
    const URI = 'https://thehackernews.com/';
    const DESCRIPTION = 'Cyber Security, Hacking, Technology News.';

    public function collectData(){

        function StripWithDelimiters($string, $start, $end) {
            while (strpos($string, $start) !== false) {
                $section_to_remove = substr($string, strpos($string, $start));
                $section_to_remove = substr($section_to_remove, 0, strpos($section_to_remove, $end) + strlen($end));
                $string = str_replace($section_to_remove, '', $string);
            } return $string;
        }

        function StripRecursiveHTMLSection($string, $tag_name, $tag_start) {
            $open_tag = '<'.$tag_name;
            $close_tag = '</'.$tag_name.'>';
            $close_tag_length = strlen($close_tag);
            if (strpos($tag_start, $open_tag) === 0) {
                while (strpos($string, $tag_start) !== false) {
                    $max_recursion = 100;
                    $section_to_remove = null;
                    $section_start = strpos($string, $tag_start);
                    $search_offset = $section_start;
                    do {
                        $max_recursion--;
                        $section_end = strpos($string, $close_tag, $search_offset);
                        $search_offset = $section_end + $close_tag_length;
                        $section_to_remove = substr($string, $section_start, $section_end - $section_start + $close_tag_length);
                        $open_tag_count = substr_count($section_to_remove, $open_tag);
                        $close_tag_count = substr_count($section_to_remove, $close_tag);
                    } while ($open_tag_count > $close_tag_count && $max_recursion > 0);
                    $string = str_replace($section_to_remove, '', $string);
                }
            }
            return $string;
        }

        $html = getSimpleHTMLDOM($this->getURI()) or returnServerError('Could not request TheHackerNews: '.$this->getURI());
        $limit = 0;

        foreach ($html->find('article') as $element) {
            if ($limit < 5) {

                $article_url = $element->find('a.entry-title', 0)->href;
                $article_author = trim($element->find('span.vcard', 0)->plaintext);
                $article_title = $element->find('a.entry-title', 0)->plaintext;
                $article_timestamp = strtotime($element->find('span.updated', 0)->plaintext);
                $article = getSimpleHTMLDOM($article_url) or returnServerError('Could not request TheHackerNews: '.$article_url);

                $contents = $article->find('div.articlebodyonly', 0)->innertext;
                $contents = StripRecursiveHTMLSection($contents, 'div', '<div class=\'clear\'');
                $contents = StripWithDelimiters($contents, '<script', '</script>');

                $item = array();
                $item['uri'] = $article_url;
                $item['title'] = $article_title;
                $item['author'] = $article_author;
                $item['timestamp'] = $article_timestamp;
                $item['content'] = trim($contents);
                $this->items[] = $item;
                $limit++;
            }
        }

    }
}
