<?php
class CNETBridge extends BridgeAbstract {

    const MAINTAINER = 'ORelio';
    const NAME = 'CNET News';
    const URI = 'http://www.cnet.com/';
    const CACHE_TIMEOUT = 1800; // 30min
    const DESCRIPTION = 'Returns the newest articles. <br /> You may specify a topic found in some section URLs, else all topics are selected.';

    const PARAMETERS = array( array(
        'topic'=>array('name'=>'Topic name')
    ));

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

        function CleanArticle($article_html) {
            $article_html = '<p>'.substr($article_html, strpos($article_html, '<p>') + 3);
            $article_html = StripWithDelimiters($article_html, '<span class="credit">', '</span>');
            $article_html = StripWithDelimiters($article_html, '<script', '</script>');
            $article_html = StripWithDelimiters($article_html, '<div class="shortcode related-links', '</div>');
            $article_html = StripWithDelimiters($article_html, '<a class="clickToEnlarge">', '</a>');
            return $article_html;
        }

        $pageUrl = self::URI.(empty($this->getInput('topic')) ? '' : 'topics/'.$this->getInput('topic').'/');
        $html = getSimpleHTMLDOM($pageUrl) or returnServerError('Could not request CNET: '.$pageUrl);
        $limit = 0;

        foreach($html->find('div.assetBody') as $element) {
            if ($limit < 8) {

                $article_title = trim($element->find('h2', 0)->plaintext);
                $article_uri = self::URI.($element->find('a', 0)->href);
                $article_timestamp = strtotime($element->find('time.assetTime', 0)->plaintext);
                $article_author = trim($element->find('a[rel=author]', 0)->plaintext);

                if (!empty($article_title) && !empty($article_uri) && strpos($article_uri, '/news/') !== false) {

                    $article_html = getSimpleHTMLDOM($article_uri) or returnServerError('Could not request CNET: '.$article_uri);

                    $article_content = trim(CleanArticle(ExtractFromDelimiters($article_html, '<div class="articleContent', '<footer>')));

                    $item = array();
                    $item['uri'] = $article_uri;
                    $item['title'] = $article_title;
                    $item['author'] = $article_author;
                    $item['timestamp'] = $article_timestamp;
                    $item['content'] = $article_content;
                    $this->items[] = $item;
                    $limit++;
                }
            }
        }
    }

    public function getName() {
        $topic=$this->getInput('topic');
        return 'CNET News Bridge'.(empty($topic) ? '' : ' - '.$topic);
    }
}
