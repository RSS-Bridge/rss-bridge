<?php
class GBAtempBridge extends BridgeAbstract {

    public $maintainer = 'ORelio';
    public $name = 'GBAtemp';
    public $uri = 'http://gbatemp.net/';
    public $description = 'GBAtemp is a user friendly underground video game community.';

    public $parameters = array( array(
        'type'=>array(
            'name'=>'Type',
            'type'=>'list',
            'required'=>true,
            'values'=>array(
                'News'=>'N',
                'Reviews'=>'R',
                'Tutorials'=>'T',
                'Forum'=>'F'
            )
        )
    ));

    private function ExtractFromDelimiters($string, $start, $end) {
        if (strpos($string, $start) !== false) {
            $section_retrieved = substr($string, strpos($string, $start) + strlen($start));
            $section_retrieved = substr($section_retrieved, 0, strpos($section_retrieved, $end));
            return $section_retrieved;
        } return false;
    }

    private function StripWithDelimiters($string, $start, $end) {
        while (strpos($string, $start) !== false) {
            $section_to_remove = substr($string, strpos($string, $start));
            $section_to_remove = substr($section_to_remove, 0, strpos($section_to_remove, $end) + strlen($end));
            $string = str_replace($section_to_remove, '', $string);
        } return $string;
    }

    private function build_item($uri, $title, $author, $timestamp, $content) {
        $item = array();
        $item['uri'] = $uri;
        $item['title'] = $title;
        $item['author'] = $author;
        $item['timestamp'] = $timestamp;
        $item['content'] = $content;
        return $item;
    }

    private function cleanup_post_content($content, $site_url) {
        $content = str_replace(':arrow:', '&#x27a4;', $content);
        $content = str_replace('href="attachments/', 'href="'.$site_url.'attachments/', $content);
        $content = $this->StripWithDelimiters($content, '<script', '</script>');
        return $content;
    }

    private function fetch_post_content($uri, $site_url) {
        $html = $this->getSimpleHTMLDOM($uri);
        if(!$html){
            return 'Could not request GBAtemp '.$uri;
        }

        $content = $html->find('div.messageContent', 0)->innertext;
        return $this->cleanup_post_content($content, $site_url);
    }

    public function collectData(){

        $html = $this->getSimpleHTMLDOM($this->uri)
            or $this->returnServerError('Could not request GBAtemp.');

        switch($this->getInput('type')){
        case 'N':
            foreach ($html->find('li[class=news_item full]') as $newsItem) {
                $url = $this->uri.$newsItem->find('a', 0)->href;
                $time = intval($this->ExtractFromDelimiters($newsItem->find('abbr.DateTime', 0)->outertext, 'data-time="', '"'));
                $author = $newsItem->find('a.username', 0)->plaintext;
                $title = $newsItem->find('a', 1)->plaintext;
                $content = $this->fetch_post_content($url, $this->uri);
                $this->items[] = $this->build_item($url, $title, $author, $time, $content);
            }
        case 'R':
            foreach ($html->find('li.portal_review') as $reviewItem) {
                $url = $this->uri.$reviewItem->find('a', 0)->href;
                $title = $reviewItem->find('span.review_title', 0)->plaintext;
                $content = $this->getSimpleHTMLDOM($url) or $this->returnServerError('Could not request GBAtemp: '.$uri);
                $author = $content->find('a.username', 0)->plaintext;
                $time = intval($this->ExtractFromDelimiters($content->find('abbr.DateTime', 0)->outertext, 'data-time="', '"'));
                $intro = '<p><b>'.($content->find('div#review_intro', 0)->plaintext).'</b></p>';
                $review = $content->find('div#review_main', 0)->innertext;
                $subheader = '<p><b>'.$content->find('div.review_subheader', 0)->plaintext.'</b></p>';
                $procons = $content->find('table.review_procons', 0)->outertext;
                $scores = $content->find('table.reviewscores', 0)->outertext;
                $content = $this->cleanup_post_content($intro.$review.$subheader.$procons.$scores, $this->uri);
                $this->items[] = $this->build_item($url, $title, $author, $time, $content);
            }
        case 'T':
            foreach ($html->find('li.portal-tutorial') as $tutorialItem) {
                $url = $this->uri.$tutorialItem->find('a', 0)->href;
                $title = $tutorialItem->find('a', 0)->plaintext;
                $time = intval($this->ExtractFromDelimiters($tutorialItem->find('abbr.DateTime', 0)->outertext, 'data-time="', '"'));
                $author = $tutorialItem->find('a.username', 0)->plaintext;
                $content = $this->fetch_post_content($url, $this->uri);
                $this->items[] = $this->build_item($url, $title, $author, $time, $content);
            }
        case 'F':
            foreach ($html->find('li.rc_item') as $postItem) {
                $url = $this->uri.$postItem->find('a', 1)->href;
                $title = $postItem->find('a', 1)->plaintext;
                $time = intval($this->ExtractFromDelimiters($postItem->find('abbr.DateTime', 0)->outertext, 'data-time="', '"'));
                $author = $postItem->find('a.username', 0)->plaintext;
                $content = $this->fetch_post_content($url, $this->uri);
                $this->items[] = $this->build_item($url, $title, $author, $time, $content);
            }
        }
    }

    public function getName() {
        $type=array_search(
            $this->getInput('type'),
            $this->parameters[$this->queriedContext]['type']['values']
        );
        return 'GBAtemp '.$type.' Bridge';
    }

    public function getCacheDuration() {
        return ($this->filter === 'Forum') ? 300 : 3600; // 5 minutes / 1 hour
    }

}
