<?php
Class FierPandaBridge extends BridgeAbstract{

    public function loadMetadatas() {

        $this->maintainer = "snroki";
        $this->name = "Fier Panda Bridge";
        $this->uri = "http://www.fier-panda.fr/";
        $this->description = "Returns latest articles from Fier Panda.";
        $this->update = "2016-08-09";

    }

    public function collectData(array $param){
        $link = 'http://www.fier-panda.fr/';

        $html = $this->file_get_html($link) or $this->returnError('Could not request Fier Panda.', 404);

        foreach($html->find('div.container-content article') as $element) {
            $item = new \Item();
            $item->uri = $this->getURI().$element->find('a', 0)->href;
            $item->title = trim($element->find('h2 a', 0)->innertext);
            // Remove the link at the end of the article
            $element->find('p a', 0)->outertext = '';
            $item->content = $element->find('p', 0)->innertext;
            $this->items[] = $item;
        }
    }

    public function getCacheDuration(){
        return 21600; // 6 hours
    }
}
