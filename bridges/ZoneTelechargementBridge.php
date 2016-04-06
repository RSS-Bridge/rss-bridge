<?php
class ZoneTelechargementBridge extends BridgeAbstract {

    public function loadMetadatas() {

        $this->maintainer = 'ORelio';
        $this->name = $this->getName();
        $this->uri = $this->getURI();
        $this->description = 'RSS proxy returning the newest releases.<br />You may specify a category found in RSS URLs, else main feed is selected.';
        $this->update = "2016-03-16";

        $this->parameters[] =
        '[
            {
                "name" : "Category",
                "identifier" : "category"
            }
        ]';
    }

    public function collectData(array $param) {

        function StripCDATA($string) {
            $string = str_replace('<![CDATA[', '', $string);
            $string = str_replace(']]>', '', $string);
            return $string;
        }

        $category = '/';
        if (!empty($param['category']))
            $category = '/'.$param['category'].'/';

        $url = $this->getURI().$category.'rss.xml';
        $html = file_get_html($url) or $this->returnError('Could not request Zone Telechargement: '.$url, 500);

        foreach($html->find('item') as $element) {
            $item = new \Item();
            $item->title = $element->find('title', 0)->plaintext;
            $item->uri = str_replace('http://', 'https://', $element->find('guid', 0)->plaintext);
            $item->timestamp = strtotime($element->find('pubDate', 0)->plaintext);
            $item->content = StripCDATA($element->find('description', 0)->innertext);
            $this->items[] = $item;
            $limit++;
        }
    }

    public function getName() {
        return 'Zone Telechargement Bridge';
    }

    public function getURI() {
        return 'https://www.zone-telechargement.com/';
    }

    public function getCacheDuration() {
        return 3600;
    }
}
