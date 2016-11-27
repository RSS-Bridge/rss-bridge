<?php
class EstCeQuonMetEnProdBridge extends BridgeAbstract {

    const MAINTAINER = 'ORelio';
    const NAME = 'Est-ce qu\'on met en prod aujourd\'hui ?';
    const URI = 'https://www.estcequonmetenprodaujourdhui.info/';
    const CACHE_TIMEOUT = 21600; // 6h
    const DESCRIPTION = 'Should we put a website in production today? (French)';

    public function collectData(){
        function ExtractFromDelimiters($string, $start, $end) {
            if (strpos($string, $start) !== false) {
                $section_retrieved = substr($string, strpos($string, $start) + strlen($start));
                $section_retrieved = substr($section_retrieved, 0, strpos($section_retrieved, $end));
                return $section_retrieved;
            } return false;
        }

        $html = getSimpleHTMLDOM($this->getURI()) or returnServerError('Could not request EstCeQuonMetEnProd: '.$this->getURI());

        $item = array();
        $item['uri'] = $this->getURI().'#'.date('Y-m-d');
        $item['title'] = $this->getName();
        $item['author'] = 'Nicolas Hoffmann';
        $item['timestamp'] = strtotime('today midnight');
        $item['content'] = str_replace('src="/', 'src="'.$this->getURI(), trim(ExtractFromDelimiters($html->outertext, '<body role="document">', '<br /><br />')));
        $this->items[] = $item;
    }
}
?>
