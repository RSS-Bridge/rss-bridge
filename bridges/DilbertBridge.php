<?php
class DilbertBridge extends BridgeAbstract {

    public function loadMetadatas() {

        $this->maintainer = 'kranack';
        $this->name = $this->getName();
        $this->uri = $this->getURI();
        $this->description = $this->getDescription();
        $this->update = "14/05/2016";

    }

    public function collectData(array $param) {

        $html = file_get_html($this->getURI()) or $this->returnError('Could not request Dilbert: '.$this->getURI(), 500);

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
            $item->thumbnailUri = $comic;
            $item->title = $title;
            $item->author = 'Scott Adams';
            $item->timestamp = $date;
            $item->content = '<img src="'.$comic.'" alt="'.$img->alt.'" />';
            $this->items[] = $item;
        }
    }

    public function getName() {
        return 'Dilbert Daily Strip';
    }

    public function getURI() {
        return 'http://dilbert.com';
    }

    public function getDescription() {
        return 'The Unofficial Dilbert Daily Comic Strip';
    }

    public function getCacheDuration() {
        return 21600; // 6 hours
    }
}
?>
