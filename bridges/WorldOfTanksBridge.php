<?php
class WorldOfTanksBridge extends HttpCachingBridgeAbstract{

	public function loadMetadatas() {

		$this->maintainer = "mitsukarenai";
		$this->name = "World of Tanks";
		$this->uri = "http://worldoftanks.eu/";
		$this->description = "News about the tank slaughter game.";

        $this->parameters[] = array(
            'category'=>array(
                // TODO: should be a list
                'name'=>'nom de la catégorie'
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

    function getURI(){
        $param=$this->parameters[$this->queriedContext];
        $lang='fr';
        if (!empty($param['lang']['value'])) {
            $lang = $param['lang']['value'];
        }

        $uri = $this->uri.$lang.'/news/';
        if(!empty($param['category']['value'])) {
            $uri .= 'pc-browser/'.$param['category']['value']."/";
        }
        return $uri;
    }

    public function collectData(){
        $html = $this->getSimpleHTMLDOM($this->getURI()) or $this->returnServerError('Could not request '.$this->getURI());
        $this->debugMessage("loaded HTML from ".$this->getURI());
        // customize name
        $this->name = $html->find('title', 0)->innertext;
        foreach($html->find('.b-imgblock_ico') as $infoLink) {
            $this->parseLine($infoLink);
       }
    }

    private function parseLine($infoLink) {
        $item = array();
        $item['uri'] = $this->uri.$infoLink->href;
        // now load that uri from cache
        $this->debugMessage("loading page ".$item['uri']);
        $articlePage = str_get_html($this->get_cached($item['uri']));
        $content = $articlePage->find('.l-content', 0);
        HTMLSanitizer::defaultImageSrcTo($content, $this->uri);
        $item['title'] = $content->find('h1', 0)->innertext;
        $item['content'] = $content->find('.b-content', 0)->innertext;
        $item['timestamp'] = $content->find('.b-statistic_time', 0)->getAttribute("data-timestamp");
        $this->items[] = $item;
    }
}
