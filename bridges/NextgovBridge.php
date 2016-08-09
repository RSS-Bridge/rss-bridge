<?php
class NextgovBridge extends BridgeAbstract {

    public function loadMetadatas() {

        $this->maintainer = 'ORelio';
        $this->name = 'Nextgov Bridge';
        $this->uri = 'https://www.nextgov.com/';
        $this->description = 'USA Federal technology news, best practices, and web 2.0 tools.';
        $this->update = '2016-08-09';

        $this->parameters[] =
        '[
            {
                "name" : "Category",
                "type" : "list",
                "identifier" : "category",
                "values" :
                [
                    { "name" : "All", "value" : "all" },
                    { "name" : "Technology News", "value" : "technology-news" },
                    { "name" : "CIO Briefing", "value" : "cio-briefing" },
                    { "name" : "Emerging Tech", "value" : "emerging-tech" },
                    { "name" : "Cloud", "value" : "cloud-computing" },
                    { "name" : "Cybersecurity", "value" : "cybersecurity" },
                    { "name" : "Mobile", "value" : "mobile" },
                    { "name" : "Health", "value" : "health" },
                    { "name" : "Defense", "value" : "defense" },
                    { "name" : "Big Data", "value" : "big-data" }
                ]
            }
        ]';

    }

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

        $category = $param['category'];
        if (empty($category))
            $category = 'all';
        if ($category !== preg_replace('/[^a-z-]+/', '', $category) || strlen($category > 32))
            $this->returnError('Invalid "category" parameter.', 400);
        $url = $this->getURI().'rss/'.$category.'/';
        $html = $this->file_get_html($url) or $this->returnError('Could not request Nextgov: '.$url, 500);
        $limit = 0;

        foreach ($html->find('item') as $element) {
            if ($limit < 10) {

                $article_url = ExtractFromDelimiters($element->innertext, '<link>', '</link>');
                $article_author = ExtractFromDelimiters($element->innertext, 'dc/elements/1.1/">', '</dc:creator>');
                $article_title = $element->find('title', 0)->plaintext;
                $article_subtitle = $element->find('description', 0)->plaintext;
                $article_timestamp = strtotime($element->find('pubDate', 0)->plaintext);
                $article_thumbnail = ExtractFromDelimiters($element->innertext, '<media:content url="', '"');
                $article = $this->file_get_html($article_url) or $this->returnError('Could not request Nextgov: '.$article_url, 500);

                $contents = $article->find('div.wysiwyg', 0)->innertext;
                $contents = StripWithDelimiters($contents, '<div class="ad-container">', '</div>');
                $contents = StripWithDelimiters($contents, '<div', '</div>'); //ad outer div
                $contents = StripWithDelimiters($contents, '<script', '</script>');
                $contents = ($article_thumbnail == '' ? '' : '<p><img src="'.$article_thumbnail.'" /></p>')
                    .'<p><b>'.$article_subtitle.'</b></p>'
                    .trim($contents);

                $item = new \Item();
                $item->uri = $article_url;
                $item->title = $article_title;
                $item->author = $article_author;
                $item->timestamp = $article_timestamp;
                $item->content = $contents;
                $this->items[] = $item;
                $limit++;
            }
        }

    }
}