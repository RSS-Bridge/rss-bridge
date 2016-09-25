<?php
class NextgovBridge extends FeedExpander {

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
        $this->collectExpandableDatas(self::URI . 'rss/' . $this->getInput('category') . '/', 10);
    }

    protected function parseItem($newsItem){
        $item = parent::parseItem($newsItem);

        $item['content'] = '';

        $namespaces = $newsItem->getNamespaces(true);
        if(isset($namespaces['media'])){
            $media = $newsItem->children($namespaces['media']);
            if(isset($media->content)){
                $attributes = $media->content->attributes();
                $item['content'] = '<img src="' . $attributes['url'] . '">';
            }
        }

        $item['content'] .= $this->ExtractContent($item['uri']);
        return $item;
    }

    private function StripWithDelimiters($string, $start, $end) {
        while (strpos($string, $start) !== false) {
            $section_to_remove = substr($string, strpos($string, $start));
            $section_to_remove = substr($section_to_remove, 0, strpos($section_to_remove, $end) + strlen($end));
            $string = str_replace($section_to_remove, '', $string);
        } return $string;
    }

    private function ExtractContent($url){
        $article = getSimpleHTMLDOMCached($url)
            or returnServerError('Could not request Nextgov: ' . $url);

        $contents = $article->find('div.wysiwyg', 0)->innertext;
        $contents = $this->StripWithDelimiters($contents, '<div class="ad-container">', '</div>');
        $contents = $this->StripWithDelimiters($contents, '<div', '</div>'); //ad outer div
        return $this->StripWithDelimiters($contents, '<script', '</script>');
        $contents = ($article_thumbnail == '' ? '' : '<p><img src="'.$article_thumbnail.'" /></p>')
            .'<p><b>'.$article_subtitle.'</b></p>'
            .trim($contents);
    }
}
