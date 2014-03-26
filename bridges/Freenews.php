<?php
/**
*
* @name FreeNews 
* @description Un site d'actualité pour les freenautes (mais ne parlant pas que de la freebox). Ne rentrez pas d'id si vous voulez accéder aux actualités générales.
* @update 26/03/2014
* @use1(id="Id de la rubrique (sans le '-')")
*/
define('FREENEWS', 'http://www.freenews.fr/');
define('NEWS', FREENEWS.'spip.php?page=news');
define('RUBRIQUE', FREENEWS.'spip.php?page=rubrique&id_rubrique=-');
class FreeNews extends HttpCachingBridgeAbstract{
    private $uri = NEWS;
    private $name = 'Freenews';

    public function collectData(array $param){
        if (!empty($param['id'])) {
            $this->uri = RUBRIQUE.$param['id'];
        }
        $html = file_get_html($this->getURI()) or $this->returnError('Could not request '.$this->getURI(), 404);
//        $this->message("loaded HTML from ".$this->getURI());
        // customize name 
        $this->name = $html->find('title', 0)->innertext;
        foreach($html->find('.news_line') as $newsLines) {
            $this->parseLine($newsLines);
       }
    }
    
    public function parseLine($newsLines) {
            foreach($newsLines->find('span') as $newsSpan) {
                foreach($newsSpan->find('a') as $newsLink) {
                    $item = new Item();
                    $item->title = trim($newsLink->title);
                    $item->uri = FREENEWS.$newsLink->href;
                    // now load that uri from cache
                    $articlePage = str_get_html($this->get_cached($item->uri));
                    $content = $articlePage->find('.chapo', 0);
                    foreach($content->find('img') as $image) {
                        $image->src = FREENEWS.$image->src;
                    }
                    $redaction = $articlePage->find('.redac', 0);
                    $rubrique = $redaction->find('a', 0);
                    $auteur = $redaction->find('a', 1);
                    $item->content = $content->innertext;
                    $item->name = $auteur->innertext;
                    // format should parse 2014-03-25T16:21:20Z. But, according to http://stackoverflow.com/a/10478469, it is not that simple
                    $item->timestamp = DateTime::createFromFormat('Y-m-d\TH:i:s+', $redaction->title)->getTimestamp();
                    $this->items[] = $item;
                    // return after first link, as there are hidden treasures in those pages
                    return;
                }
            }
    }

    public function getName(){
        return $this->name;
    }

    public function getURI(){
        return $this->uri;
    }

    public function getCacheDuration(){
        return 3600; // 2h hours
    }
    public function getDescription(){
        return "Un site d'actualité pour les freenautes (mais ne parlant pas que de la freebox). par rss-bridge";
    }
}
