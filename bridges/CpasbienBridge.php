<?php
class CpasbienBridge extends BridgeAbstract {

    const MAINTAINER = "lagaisse";
    const NAME = "Cpasbien Bridge";
    const URI = "http://www.cpasbien.io";
    const CACHE_TIMEOUT = 86400; // 24h
    const DESCRIPTION = "Returns latest torrents from a request query";

    const PARAMETERS = array( array(
        'q'=>array(
            'name'=>'Search',
            'required'=>true,
            'title'=>'Type your search'
        )
    ));

    public function collectData(){
        $request = str_replace(" ","-",trim($this->getInput('q')));
        $html = getSimpleHTMLDOM(self::URI.'/recherche/'.urlencode($request).'.html')
            or returnServerError('No results for this query.');

        foreach ($html->find('#gauche',0)->find('div') as $episode) {
            if ($episode->getAttribute('class')=='ligne0' ||
                $episode->getAttribute('class')=='ligne1')
            {
                $htmlepisode=getSimpleHTMLDOMCached($episode->find('a', 0)->getAttribute('href'));

                $item = array();
                $item['author'] = $episode->find('a', 0)->text();
                $item['title'] = $episode->find('a', 0)->text();
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
                $item['uri'] = self::URI . $htmlepisode->find('#telecharger',0)->getAttribute('href');
                $this->items[] = $item;
            }
        }
    }


    public function getName(){
        return $this->getInput('q').' : '.self::NAME;
    }
}
