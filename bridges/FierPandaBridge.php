<?php
class FierPandaBridge extends BridgeAbstract {

    const MAINTAINER = "snroki";
    const NAME = "Fier Panda Bridge";
    const URI = "http://www.fier-panda.fr/";
    const CACHE_TIMEOUT = 21600; // 6h
    const DESCRIPTION = "Returns latest articles from Fier Panda.";

    public function collectData(){
        $html = getSimpleHTMLDOM(self::URI) or returnServerError('Could not request Fier Panda.');

        foreach($html->find('div.container-content article') as $element) {
            $item = array();
            $item['uri'] = $this->getURI().$element->find('a', 0)->href;
            $item['title'] = trim($element->find('h1 a', 0)->innertext);
            // Remove the link at the end of the article
            $element->find('p a', 0)->outertext = '';
            $item['content'] = $element->find('p', 0)->innertext;
            $this->items[] = $item;
        }
    }
}
