<?php
class NakedSecurityBridge extends BridgeAbstract {

    public function loadMetadatas() {
        $this->maintainer = 'ORelio';
        $this->name = 'Naked Security';
        $this->uri = 'https://nakedsecurity.sophos.com/';
        $this->description = 'Returns the newest articles.';
        $this->update = '2016-08-09';
    }

    public function collectData(array $param) {

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

        $feedUrl = 'https://feeds.feedburner.com/nakedsecurity?format=xml';
        $html = $this->file_get_html($feedUrl) or $this->returnError('Could not request '.$this->getName().': '.$feedUrl, 500);
        $limit = 0;

        foreach ($html->find('item') as $element) {
            if ($limit < 10) {

                //Retrieve article Uri and get that page
                $article_uri = $element->find('guid', 0)->plaintext;
                $article_html = $this->file_get_html($article_uri) or $this->returnError('Could not request '.$this->getName().': '.$article_uri, 500);

                //Build article contents from corresponding elements
                $article_title = trim($element->find('title', 0)->plaintext);
                $article_image = $article_html->find('img.wp-post-image', 0)->src;
                $article_summary = strip_tags(html_entity_decode($element->find('description', 0)->plaintext));
                $article_content = $article_html->find('div.entry-content', 0)->innertext;
                $article_content = StripRecursiveHTMLSection($article_content , 'div', '<div class="entry-prefix"');
                $article_content = StripRecursiveHTMLSection($article_content , 'script', '<script');
                $article_content = StripRecursiveHTMLSection($article_content , 'aside', '<aside');
                $article_content = '<p><img src="'.$article_image.'" /></p><p><b>'.$article_summary.'</b></p>'.$article_content;

                //Build and add final item
                $item = new \Item();
                $item->uri = $article_uri;
                $item->title = $article_title;
                $item->author = $article_html->find('a[rel=author]', 0)->plaintext;
                $item->timestamp = strtotime($element->find('pubDate', 0)->plaintext);
                $item->content = $article_content;
                $this->items[] = $item;
                $limit++;
            }
        }
    }
}