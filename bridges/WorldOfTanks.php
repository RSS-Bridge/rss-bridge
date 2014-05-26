<?php
/**
*
* @name World of Tanks 
* @description News about the tank slaughter game. Language can be fr, ?
* @update 26/03/2014
* @use1(lang="Searched language",category="Category id")
*/
define('WORLD_OF_TANKS', 'http://worldoftanks.eu/');
define('NEWS', '/news/');
class WorldOfTanks extends HttpCachingBridgeAbstract{
    private $lang = "fr";
    private $uri = WORLD_OF_TANKS;
    private $name = 'World of tanks news';

    public function collectData(array $param){
        if (!empty($param['lang'])) {
            $this->lang = $param['lang'];
        }
        if(empty($param['category'])) {
            $this->uri = WORLD_OF_TANKS.$this->lang.NEWS;
        } else {
            $this->uri = WORLD_OF_TANKS.$this->lang.NEWS.$param['category']."/";
        }
        $html = file_get_html($this->getURI()) or $this->returnError('Could not request '.$this->getURI(), 404);
        $this->message("loaded HTML from ".$this->getURI());
        // customize name 
        $this->name = $html->find('title', 0)->innertext;
        foreach($html->find('.b-imgblock_ico') as $infoLink) {
            $this->parseLine($infoLink);
       }
    }
    
    public function parseLine($infoLink) {
        $item = new Item();
        $item->uri = WORLD_OF_TANKS.$infoLink->href;
        // now load that uri from cache
//        $this->message("loading page ".$item->uri);
        $articlePage = str_get_html($this->get_cached($item->uri));
        $content = $articlePage->find('.l-content', 0);
        $this->defaultImageSrcTo($content, WORLD_OF_TANKS);
        $item->title = $content->find('h1', 0)->innertext;
        $item->content = $content->find('.b-content', 0)->innertext;
//        $item->name = $auteur->innertext;
        $item->timestamp = $content->find('.b-statistic_time', 0)->getAttribute("data-timestamp");
        $this->items[] = $item;
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
        return "Toutes les actualités les plus brulantes de ce simulateur de destruction d'acier.";
    }
}
