<?php
/**
* CNET Bridge
* Returns the newest articles
* 2015-09-08
*
* @name CNET News
* @homepage http://www.cnet.com/
* @description Returns the newest articles. <br /> You may specify a topic, else all topics are selected.
* @maintainer ORelio
* @update 2015-09-10
* @use1(topic="Topic name")
*/
class CNETBridge extends BridgeAbstract {

    private $topicName = '';

    public function collectData(array $param) {

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
            $article_html = '<p>'.substr($article_html, strpos($article_html, '</script></div><p>') + 18);
            $article_html = StripWithDelimiters($article_html, '<script>', '</script>');
            $article_html = StripWithDelimiters($article_html, '<div class="shortcode related-links', '</div>');
            $article_html = StripWithDelimiters($article_html, '<a class="clickToEnlarge">', '</a>');
            return $article_html;
        }

        if (!empty($param['topic']))
            $this->topicName = $param['topic'];

        $pageUrl = 'http://www.cnet.com/'.(empty($this->topicName) ? '' : 'topics/'.$this->topicName.'/');
        $html = file_get_html($pageUrl) or $this->returnError('Could not request CNET: '.$pageUrl, 404);
        $limit = 0;

        foreach($html->find('div.socialSharingSmall') as $element) {
            if ($limit < 4) {

                $article_meta = json_decode(ExtractFromDelimiters($element->outertext, 'data-social-counts-options=\'', '\'>'));
                $article_title = $article_meta->title;
                $article_uri = $article_meta->url;

                if (!empty($article_title) && !empty($article_uri) && strpos($article_uri, '/news/') !== false) {

                    $article_html = file_get_html($article_uri) or $this->returnError('Could not request CNET: '.$article_uri, 404);
                    $article_timestamp = strtotime(ExtractFromDelimiters($article_html->innertext, '<time itemprop="datePublished" class="', '">'));
                    $article_thumbnail = $article_html->find('div.originalImage', 0);

                    if (is_null($article_thumbnail))
                        $article_thumbnail = $article_html->find('span.imageContainer', 0);

                    if (!is_null($article_thumbnail))
                        $article_thumbnail = $article_thumbnail->find('img', 0)->src;
                    
                    $article_content = trim(CleanArticle(ExtractFromDelimiters($article_html, '</div></div></div><div class="col-8">', '<footer>')));
                    $article_author = trim($article_html->find('a.author', 0)->plaintext);

                    $item = new \Item();
                    $item->uri = $article_uri;
                    $item->thumbnailUri = $article_thumbnail;
                    $item->title = $article_title;
                    $item->author = $article_author;
                    $item->timestamp = $article_timestamp;
                    $item->content = $article_content;
                    $this->items[] = $item;
                    $limit++;
                }
            }
        }
    }

    public function getName() {
        return 'CNET News Bridge'.(empty($this->topicName) ? '' : ' - '.$this->topicName);
    }

    public function getURI() {
        return 'http://www.cnet.com/';
    }

    public function getCacheDuration() {
        return 1800; // 30 minutes
        // return 0;
    }
}
