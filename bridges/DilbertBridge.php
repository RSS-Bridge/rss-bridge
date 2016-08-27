<?php
class DilbertBridge extends BridgeAbstract {

    public $maintainer = 'kranack';
    public $name = 'Dilbert Daily Strip';
    public $uri = 'http://dilbert.com';
    public $description = 'The Unofficial Dilbert Daily Comic Strip';

    public function collectData(){

        $html = $this->getSimpleHTMLDOM($this->getURI()) or $this->returnServerError('Could not request Dilbert: '.$this->getURI());

        foreach ($html->find('section.comic-item') as $element) {

            $img = $element->find('img', 0);
            $link = $element->find('a', 0);
            $comic = $img->src;
            $title = $link->alt;
            $url = $link->href;
            $date = substr($url, 25);
            if (empty($title))
                $title = 'Dilbert Comic Strip on '.$date;
            $date = strtotime($date);

            $item = array();
            $item['uri'] = $url;
            $item['title'] = $title;
            $item['author'] = 'Scott Adams';
            $item['timestamp'] = $date;
            $item['content'] = '<img src="'.$comic.'" alt="'.$img->alt.'" />';
            $this->items[] = $item;
        }
    }

    public function getCacheDuration() {
        return 21600; // 6 hours
    }
}
?>
