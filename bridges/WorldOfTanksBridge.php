<?php
class WorldOfTanksBridge extends HttpCachingBridgeAbstract{

    public $maintainer = "mitsukarenai";
    public $name = "World of Tanks";
    public $uri = "http://worldoftanks.eu/";
    public $description = "News about the tank slaughter game.";

    public $parameters = array( array(
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
    ));

    private $title='';

    function getURI(){
        $lang = $this->getInput('lang');
        $uri = $this->uri.$lang.'/news/';
        if(!empty($this->getInput('category'))) {
            $uri .= 'pc-browser/'.$this->getInput('category')."/";
        }
        return $uri;
    }

    public function getName(){
      return $this->title?:$this->name;
    }

    public function collectData(){
      $html = $this->getSimpleHTMLDOM($this->getURI())
        or $this->returnServerError('Could not request '.$this->getURI());
        $this->debugMessage("loaded HTML from ".$this->getURI());
        // customize name
        $this->title = $html->find('title', 0)->innertext;
        foreach($html->find('.b-imgblock_ico') as $infoLink) {
            $this->parseLine($infoLink);
       }
    }

    private function parseLine($infoLink) {
        $item = array();
        $item['uri'] = $this->uri.$infoLink->href;
        // now load that uri from cache
        $this->debugMessage("loading page ".$item['uri']);
        $articlePage = $this->get_cached($item['uri']);
        $content = $articlePage->find('.l-content', 0);
        HTMLSanitizer::defaultImageSrcTo($content, $this->uri);
        $item['title'] = $content->find('h1', 0)->innertext;
        $item['content'] = $content->find('.b-content', 0)->innertext;
        $item['timestamp'] = $content->find('.b-statistic_time', 0)->getAttribute("data-timestamp");
        $this->items[] = $item;
    }
}
