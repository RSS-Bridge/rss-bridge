<?php
class DilbertBridge extends BridgeAbstract {

    public function loadMetadatas() {

        $this->maintainer = "kranack";
        $this->name = "Dilbert Daily Strip";
        $this->uri = "http://dilbert.com/";
        $this->description = "The Unofficial Dilbert Daily Comic Strip";
        $this->update = "14/02/2016";

    }

    public function collectData(array $param) {

        $html = file_get_html('http://dilbert.com/') or $this->returnError('Could not request Dilbert.', 500);

        foreach ($html->find('section.comic-item') as $element) {

            $img = $element->find('img', 0);
            $comic = $img->src;
            $title = $img->alt;
            $url = $element->find('a', 0)->href;
            $author = trim(substr($title, strpos($title, ' - Dilbert by ') + 14));
            $title = trim(substr($title, 0, strpos($title, ' - ')));
            $date = substr($url, 25);
            if (empty($title))
                $title = "Dilbert Comic Strip on ".$date;
            $date = strtotime($date);

            $item = new \Item();
            $item->uri = $url;
            $item->thumbnailUri = $comic;
            $item->title = $title;
            $item->author = $author;
            $item->timestamp = $date;
            $item->content = '<img src="'.$comic.'" alt="'.$title.'" />';
            $this->items[] = $item;
        }
    }

    public function getName() {
        return 'Dilbert Bridge';
    }

    public function getURI() {
        return 'http://dilbert.com';
    }

    public function getDescription() {
        return 'Dilbert Daily Strip Bridge';
    }

    public function getCacheDuration() {
        return 21600; // 6 hours
    }
}
?>
