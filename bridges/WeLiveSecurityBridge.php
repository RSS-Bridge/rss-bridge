<?php
class WeLiveSecurityBridge extends BridgeAbstract {

    public $maintainer = 'ORelio';
    public $name = 'We Live Security';
    public $uri = 'http://www.welivesecurity.com/';
    public $description = 'Returns the newest articles.';

    public function collectData(){

        function ExtractFromDelimiters($string, $start, $end) {
            if (strpos($string, $start) !== false) {
                $section_retrieved = substr($string, strpos($string, $start) + strlen($start));
                $section_retrieved = substr($section_retrieved, 0, strpos($section_retrieved, $end));
                return $section_retrieved;
            } return false;
        }

        function StripWithDelimiters($string, $start, $end) {
            while (strpos($string, $start) !== false) {
                $section_to_remove = substr($string, strpos($string, $start));
                $section_to_remove = substr($section_to_remove, 0, strpos($section_to_remove, $end) + strlen($end));
                $string = str_replace($section_to_remove, '', $string);
            } return $string;
        }

        $feed = $this->getURI().'feed/';
        $html = $this->getSimpleHTMLDOM($feed) or $this->returnServerError('Could not request '.$this->getName().': '.$feed);
        $limit = 0;

        foreach ($html->find('item') as $element) {
            if ($limit < 5) {

                $article_image = $element->find('image', 0)->plaintext;
                $article_url = ExtractFromDelimiters($element->innertext, '<link>', '</link>');
                $article_summary = ExtractFromDelimiters($element->innertext, '<description><![CDATA[<p>', '</p>');
                $article_html = $this->getContents($article_url) or $this->returnServerError('Could not request '.$this->getName().': '.$article_url);
                if (substr($article_html, 0, 2) == "\x1f\x8b") //http://www.gzip.org/zlib/rfc-gzip.html#header-trailer -> GZip ID1
                    $article_html = gzdecode($article_html);   //Response is GZipped even if we didn't accept GZip!? Let's decompress...
                $article_html = str_get_html($article_html);   //Now we have our HTML data. But still, that's an important HTTP violation...
                $article_content = $article_html->find('div.wlistingsingletext', 0)->innertext;
                $article_content = StripWithDelimiters($article_content, '<script', '</script>');
                $article_content = '<p><img src="'.$article_image.'" /></p>'
                    .'<p><b>'.$article_summary.'</b></p>'
                    .trim($article_content);

                $item = array();
                $item['uri'] = $article_url;
                $item['title'] = $element->find('title', 0)->plaintext;
                $item['author'] = $article_html->find('a[rel=author]', 0)->plaintext;
                $item['timestamp'] = strtotime($element->find('pubDate', 0)->plaintext);
                $item['content'] = $article_content;
                $this->items[] = $item;
                $limit++;

            }
        }
    }
}
