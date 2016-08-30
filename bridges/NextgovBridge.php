<?php
class NextgovBridge extends BridgeAbstract {

    const MAINTAINER = 'ORelio';
    const NAME = 'Nextgov Bridge';
    const URI = 'https://www.nextgov.com/';
    const DESCRIPTION = 'USA Federal technology news, best practices, and web 2.0 tools.';

    const PARAMETERS = array( array(
        'category'=>array(
            'name'=>'Category',
            'type'=>'list',
            'values'=>array(
                'All'=>'all',
                'Technology News'=>'technology-news',
                'CIO Briefing'=>'cio-briefing',
                'Emerging Tech'=>'emerging-tech',
                'Cloud'=>'cloud-computing',
                'Cybersecurity'=>'cybersecurity',
                'Mobile'=>'mobile',
                'Health'=>'health',
                'Defense'=>'defense',
                'Big Data'=>'big-data'
            )
        )
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

        $category = $this->getInput('category');
        $url = $this->getURI().'rss/'.$category.'/';
        $html = $this->getSimpleHTMLDOM($url) or $this->returnServerError('Could not request Nextgov: '.$url);
        $limit = 0;

        foreach ($html->find('item') as $element) {
            if ($limit >= 10) {
                break;
            }

            $article_url = ExtractFromDelimiters($element->innertext, '<link>', '</link>');
            $article_author = ExtractFromDelimiters($element->innertext, 'dc/elements/1.1/">', '</dc:creator>');
            $article_title = $element->find('title', 0)->plaintext;
            $article_subtitle = $element->find('description', 0)->plaintext;
            $article_timestamp = strtotime($element->find('pubDate', 0)->plaintext);
            $article_thumbnail = ExtractFromDelimiters($element->innertext, '<media:content url="', '"');
            $article = $this->getSimpleHTMLDOM($article_url) or $this->returnServerError('Could not request Nextgov: '.$article_url);

            $contents = $article->find('div.wysiwyg', 0)->innertext;
            $contents = StripWithDelimiters($contents, '<div class="ad-container">', '</div>');
            $contents = StripWithDelimiters($contents, '<div', '</div>'); //ad outer div
            $contents = StripWithDelimiters($contents, '<script', '</script>');
            $contents = ($article_thumbnail == '' ? '' : '<p><img src="'.$article_thumbnail.'" /></p>')
                .'<p><b>'.$article_subtitle.'</b></p>'
                .trim($contents);

            $item = array();
            $item['uri'] = $article_url;
            $item['title'] = $article_title;
            $item['author'] = $article_author;
            $item['timestamp'] = $article_timestamp;
            $item['content'] = $contents;
            $this->items[] = $item;
            $limit++;
        }
    }
}
