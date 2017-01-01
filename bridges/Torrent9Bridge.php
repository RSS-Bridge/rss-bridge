<?php
class Torrent9Bridge extends BridgeAbstract {

    const MAINTAINER = "lagaisse";
    const NAME = "Torrent9 Bridge";
    const URI = "http://www.torrent9.biz";
    const CACHE_TIMEOUT = 86400 ; // 24h = 86400s
    const DESCRIPTION = "Returns latest torrents";

    const PAGE_SERIES = "torrents_series";
    const PAGE_SERIES_VOSTFR = "torrents_series_vostfr";
    const PAGE_SERIES_FR = "torrents_series_french";

    const PARAMETERS = array(
    'From search' => array(
        'q' => array(
            'name'=>'Search',
            'required'=>true,
            'title'=>'Type your search'
        )
    ),
    'By page' => array(
        'page' => array(
            'name'=>'Page',
            'type'=>'list',
            'required'=>false,
            'values'=>array(
                'Series'=>self::PAGE_SERIES,
                'Series VOST'=>self::PAGE_SERIES_VOSTFR,
                'Series FR'=>self::PAGE_SERIES_FR,
            ),
            'defaultValue'=>self::PAGE_SERIES
        )
    ));

    public function collectData(){

        if($this->queriedContext === 'From search'){

            $request = str_replace(" ","-",trim($this->getInput('q')));
            $page = self::URI.'/search_torrent/'.urlencode($request).'.html';
        } else {
            $request = $this->getInput('page');
            $page = self::URI.'/'.$request.'.html';
        }
        
        $html = getSimpleHTMLDOM($page)
            or returnServerError('No results for this query.');

        foreach ($html->find('table',0)->find('tr') as $episode) {
            if ($episode->parent->tag == 'tbody') {
                

                $urlepisode = self::URI . $episode->find('a', 0)->getAttribute('href');
                $htmlepisode = getSimpleHTMLDOMCached($urlepisode, 86400*366*30); //30 years = forever

                $item = array();
                $item['author'] = $episode->find('a', 0)->text();
                $item['title'] = $episode->find('a', 0)->text();
                $item['id'] = $episode->find('a', 0)->getAttribute('href');
                $item['pubdate'] = $this->getCachedDate($urlepisode);
                
                $textefiche=$htmlepisode->find('.movie-information', 0)->find('p',1);
                if (isset($textefiche)) {
                    $item['content'] = $textefiche->text();
                } else {
                    $p=$htmlepisode->find('.movie-information',0)->find('p');
                    if(!empty($p)){
                        $item['content'] = $htmlepisode->find('.movie-information', 0)->find('p',0)->text();
                    }
                }

                $item['id'] = $episode->find('a', 0)->getAttribute('href');
                $item['uri'] = self::URI . $htmlepisode->find('.download',0)->getAttribute('href');
                
                $this->items[] = $item;
            }
        }
    }


    public function getName(){
        return $this->getInput('q').' : '.self::NAME;
    }

    private function getCachedDate($url){
        debugMessage('getting pubdate from url ' . $url . '');
        // Initialize cache
        $cache = Cache::create('FileCache');
        $cache->setPath(CACHE_DIR . '/pages');
        $params = [$url];
        $cache->setParameters($params);
        // Get cachefile timestamp
        $time = $cache->getTime();
        return ($time!==false?$time:time());
    }
}
