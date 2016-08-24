<?php
define('WORLD_OF_TANKS', 'http://worldoftanks.eu/');
define('NEWS', '/news/');
class WorldOfTanksBridge extends HttpCachingBridgeAbstract{

    private $lang = "fr";
    public $uri = WORLD_OF_TANKS;

	public function loadMetadatas() {

		$this->maintainer = "mitsukarenai";
		$this->name = "World of Tanks";
		$this->uri = "http://worldoftanks.eu/";
		$this->description = "News about the tank slaughter game.";

        $this->parameters[] = array(
          'category'=>array(
            'name'=>'ID de la catégorie',
            'type'=>'number'
          ),
          'lang'=>array(
            'name'=>'Langue',
            'type'=>'list',
            'values'=>array(
              'Français'=>'fr',
              'English'=>'en',
              'Español'=>'es',
              'Deutsch'=>'de',
              'Čeština'=>'cs',
              'Polski'=>'pl',
              'Türkçe'=>'tr'
            )
          )
        );
	}


    public function collectData(array $param){
        if (!empty($param['lang'])) {
            $this->lang = $param['lang'];
        }
        if(empty($param['category'])) {
            $this->uri = WORLD_OF_TANKS.$this->lang.NEWS;
        } else {
            $this->uri = WORLD_OF_TANKS.$this->lang.NEWS.'pc-browser/'.$param['category']."/";
        }
        $html = $this->getSimpleHTMLDOM($this->getURI()) or $this->returnServerError('Could not request '.$this->getURI());
        $this->message("loaded HTML from ".$this->getURI());
        // customize name
        $this->name = $html->find('title', 0)->innertext;
        foreach($html->find('.b-imgblock_ico') as $infoLink) {
            $this->parseLine($infoLink);
       }
    }

    private function parseLine($infoLink) {
        $item = array();
        $item['uri'] = WORLD_OF_TANKS.$infoLink->href;
        // now load that uri from cache
        $this->message("loading page ".$item['uri']);
        $articlePage = str_get_html($this->get_cached($item['uri']));
        $content = $articlePage->find('.l-content', 0);
        HTMLSanitizer::defaultImageSrcTo($content, WORLD_OF_TANKS);
        $item['title'] = $content->find('h1', 0)->innertext;
        $item['content'] = $content->find('.b-content', 0)->innertext;
        $item['timestamp'] = $content->find('.b-statistic_time', 0)->getAttribute("data-timestamp");
        $this->items[] = $item;
    }
}
