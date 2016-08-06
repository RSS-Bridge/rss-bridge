<?php
class EstCeQuonMetEnProdBridge extends BridgeAbstract {

    public function loadMetadatas() {
        $this->maintainer = 'ORelio';
        $this->name = $this->getName();
        $this->uri = $this->getURI();
        $this->description = 'Should we put a website in production today? (French)';
        $this->update = "2016-08-06";
    }

    public function collectData(array $param) {
        function ExtractFromDelimiters($string, $start, $end) {
            if (strpos($string, $start) !== false) {
                $section_retrieved = substr($string, strpos($string, $start) + strlen($start));
                $section_retrieved = substr($section_retrieved, 0, strpos($section_retrieved, $end));
                return $section_retrieved;
            } return false;
        }

        $html = $this->file_get_html($this->getURI()) or $this->returnError('Could not request EstCeQuonMetEnProd: '.$this->getURI(), 500);

        $img = $html->find('img', 0);
        if (is_object($img)) {
            $img = $img->src;
            if ($img[0] == '/')
                $img = substr($this->getURI(), 0, strlen($this->getURI()) - 1).$img;
        }

        $item = new \Item();
        $item->uri = $this->getURI().'#'.date('Y-m-d');
        $item->thumbnailUri = $img;
        $item->title = $this->getName();
        $item->author = 'Nicolas Hoffmann';
        $item->timestamp = strtotime('today midnight');
        $item->content = str_replace('src="/', 'src="'.$this->getURI(), trim(ExtractFromDelimiters($html->outertext, '<body role="document">', '<br /><br />')));
        $this->items[] = $item;
    }

    public function getName() {
        return 'Est-ce qu\'on met en prod aujourd\'hui ?';
    }

    public function getURI() {
        return 'https://www.estcequonmetenprodaujourdhui.info/';
    }

    public function getCacheDuration() {
        return 21600; // 6 hours
    }
}
?>