<?php
define('WORLD_OF_TANKS', 'http://worldoftanks.eu/');
define('NEWS', '/news/');
class WorldOfTanks extends HttpCachingBridgeAbstract{

    private $lang = "fr";
    public $uri = WORLD_OF_TANKS;

	public function loadMetadatas() {

		$this->maintainer = "mitsukarenai";
		$this->name = "World of Tanks";
		$this->uri = "http://worldoftanks.eu/";
		$this->description = "News about the tank slaughter game.";
		$this->update = "2016-08-09";

		$this->parameters[] =
		'[
			{
				"name" : "ID de la catégorie",
				"type" : "number",
				"identifier" : "category"
			},
			{
				"name" : "Langue",
				"identifier" : "lang",
				"type" : "list",
				"values" : [
					{
						"name" : "Français",
						"value" : "fr"
					},
					{
						"name" : "English",
						"value" : "en"
					},
					{
						"name" : "Español",
						"value" : "es"
					},
					{
						"name" : "Deutsch",
						"value" : "de"
					},
					{
						"name" : "Čeština",
						"value" : "cs"
					},
					{
						"name" : "Polski",
						"value" : "pl"
					},
					{
						"name" : "Türkçe",
						"value" : "tr"
					}
				]

			}
		]';
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
        $html = $this->file_get_html($this->getURI()) or $this->returnError('Could not request '.$this->getURI(), 404);
        $this->message("loaded HTML from ".$this->getURI());
        // customize name 
        $this->name = $html->find('title', 0)->innertext;
        foreach($html->find('.b-imgblock_ico') as $infoLink) {
            $this->parseLine($infoLink);
       }
    }
    
    private function parseLine($infoLink) {
        $item = new Item();
        $item->uri = WORLD_OF_TANKS.$infoLink->href;
        // now load that uri from cache
//        $this->message("loading page ".$item->uri);
        $articlePage = str_get_html($this->get_cached($item->uri));
        $content = $articlePage->find('.l-content', 0);
        HTMLSanitizer::defaultImageSrcTo($content, WORLD_OF_TANKS);
        $item->title = $content->find('h1', 0)->innertext;
        $item->content = $content->find('.b-content', 0)->innertext;
        $item->timestamp = $content->find('.b-statistic_time', 0)->getAttribute("data-timestamp");
        $this->items[] = $item;
    }
}
