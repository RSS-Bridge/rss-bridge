<?php
class ZoneTelechargementBridge extends BridgeAbstract {

    public $maintainer = 'ORelio';
    public $name = 'Zone Telechargement Bridge';
    public $uri = 'https://www.zone-telechargement.com/';
    public $description = 'RSS proxy returning the newest releases.<br />You may specify a category found in RSS URLs, else main feed is selected.';

    public $parameters = array( array(
        'category'=>array('name'=>'Category')
    ));

    public function collectData(){
        $param=$this->parameters[$this->queriedContext];

        function StripCDATA($string) {
            $string = str_replace('<![CDATA[', '', $string);
            $string = str_replace(']]>', '', $string);
            return $string;
        }

        $category = '/';
        if (!empty($param['category']['value']))
            $category = '/'.$param['category']['value'].'/';

        $url = $this->getURI().$category.'rss.xml';
        $html = $this->getSimpleHTMLDOM($url) or $this->returnServerError('Could not request Zone Telechargement: '.$url);

        foreach($html->find('item') as $element) {
            $item = array();
            $item['title'] = $element->find('title', 0)->plaintext;
            $item['uri'] = str_replace('http://', 'https://', $element->find('guid', 0)->plaintext);
            $item['timestamp'] = strtotime($element->find('pubDate', 0)->plaintext);
            $item['content'] = StripCDATA($element->find('description', 0)->innertext);
            $this->items[] = $item;
            $limit++;
        }
    }
}
