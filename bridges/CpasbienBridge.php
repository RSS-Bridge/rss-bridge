<?php
class CpasbienBridge extends HttpCachingBridgeAbstract{

    public $maintainer = "lagaisse";
    public $name = "Cpasbien Bridge";
    public $uri = "http://www.cpasbien.io";
    public $description = "Returns latest torrents from a request query";

    public $parameters = array( array(
        'q'=>array(
            'name'=>'Search',
            'required'=>true,
            'title'=>'Type your search'
        )
    ));

    public function collectData(){
        $request = str_replace(" ","-",trim($this->getInput('q')));
        $html = $this->getSimpleHTMLDOM($this->uri.'/recherche/'.urlencode($request).'.html')
            or $this->returnServerError('No results for this query.');

        foreach ($html->find('#gauche',0)->find('div') as $episode) {
            if ($episode->getAttribute('class')=='ligne0' ||
                $episode->getAttribute('class')=='ligne1')
            {
                $htmlepisode=$this->get_cached($episode->find('a', 0)->getAttribute('href'));

                $item = array();
                $item['author'] = $episode->find('a', 0)->text();
                $item['title'] = $episode->find('a', 0)->text();
                $item['timestamp'] = $this->get_cached_time($episode->find('a', 0)->getAttribute('href'));
                $textefiche=$htmlepisode->find('#textefiche', 0)->find('p',1);
                if (isset($textefiche)) {
                    $item['content'] = $textefiche->text();
                } else {
                    $p=$htmlepisode->find('#textefiche',0)->find('p');
                    if(!empty($p)){
                        $item['content'] = $htmlepisode->find('#textefiche', 0)->find('p',0)->text();
                    }
                }

                $item['id'] = $episode->find('a', 0)->getAttribute('href');
                $item['uri'] = $this->uri . $htmlepisode->find('#telecharger',0)->getAttribute('href');
                $this->items[] = $item;
            }
        }
    }


    public function getName(){
        return $this->getInput('q').' : '.$this->name;
    }

    public function getCacheDuration(){
        return 60*60*24; // 24 hours
    }
}
