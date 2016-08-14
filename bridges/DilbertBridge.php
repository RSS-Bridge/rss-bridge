<?php
class DilbertBridge extends BridgeAbstract {

    public function loadMetadatas() {

        $this->maintainer = 'kranack';
        $this->name = 'Dilbert Daily Strip';
        $this->uri = 'http://dilbert.com';
        $this->description = 'The Unofficial Dilbert Daily Comic Strip';
        $this->update = "2016-08-09";

    }

    public function collectData(array $param) {

        $html = $this->file_get_html($this->getURI()) or $this->returnError('Could not request Dilbert: '.$this->getURI(), 500);

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

            $item = new \Item();
            $item->uri = $url;
            $item->title = $title;
            $item->author = 'Scott Adams';
            $item->timestamp = $date;
            $item->content = '<img src="'.$comic.'" alt="'.$img->alt.'" />';
            $this->items[] = $item;
        }
    }

    public function getCacheDuration() {
        return 21600; // 6 hours
    }
}
?>
