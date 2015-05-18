<?php
/**
* RssBridgeCpasBien 
* 
* 2015-05-17
*
* @name Cpasbien Bridge
* @homepage http://Cpasbien.pw/
* @description Returns latest torrent from request query
* @maintainer lagaisse
* @use1(q="keywords like this")
*/
class CpasbienBridge extends BridgeAbstract{
    
    private $request;

    public function collectData(array $param){
        $html = '';
        if (isset($param['q'])) {   /* keyword search mode */
            $this->request = str_replace(" ","-",trim($param['q']));
            $html = file_get_html('http://www.cpasbien.pw/recherche/'.urlencode($this->request).'.html') or $this->returnError('No results for this query.', 404);
        }
        else {
            $this->returnError('You must specify a keyword (?q=...).', 400);
        }

        foreach ($html->find('#gauche',0)->find('div') as $episode) {
            if ($episode->getAttribute('class')=='ligne0' || $episode->getAttribute('class')=='ligne1')
            {
                $htmlepisode=file_get_html($episode->find('a', 0)->getAttribute('href'));

                $item = new \Item();
                $item->name = $episode->find('a', 0)->text();
                $item->title = $episode->find('a', 0)->text();
                $element=$htmlepisode->find('#textefiche', 0)->find('p',1);
                if (isset($element)) {
                    $item->content = $element->text();
                }
                else {
                    $item->content = $htmlepisode->find('#textefiche', 0)->find('p',0)->text();    
                }

                $item->id = $episode->find('a', 0)->getAttribute('href');
                $item->uri = $this->getURI() . $htmlepisode->find('#telecharger',0)->getAttribute('href');
                $this->items[] = $item;
            }
        }


    }


    public function getName(){
        return (!empty($this->request) ? $this->request .' - ' : '') .'Cpasbien Bridge';
    }

    public function getURI(){
        return 'http://www.cpasbien.pw';
    }

    public function getCacheDuration(){
        return 60*60*24; // 24 hours
    }
}
