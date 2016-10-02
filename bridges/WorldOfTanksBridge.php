<?php
class WorldOfTanksBridge extends BridgeAbstract {

    const MAINTAINER = "mitsukarenai";
    const NAME = "World of Tanks";
    const URI = "http://worldoftanks.eu/";
    const DESCRIPTION = "News about the tank slaughter game.";

    const PARAMETERS = array( array(
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
        $uri = self::URI.$lang.'/news/';
        if(!empty($this->getInput('category'))) {
            $uri .= 'pc-browser/'.$this->getInput('category')."/";
        }
        return $uri;
    }

    public function getName(){
      return $this->title?:self::NAME;
    }

    public function collectData(){
      $html = getSimpleHTMLDOM($this->getURI())
        or returnServerError('Could not request '.$this->getURI());
        debugMessage("loaded HTML from ".$this->getURI());
        // customize name
        $this->title = $html->find('title', 0)->innertext;
        foreach($html->find('.b-imgblock_ico') as $infoLink) {
            $this->parseLine($infoLink);
       }
    }

    private function parseLine($infoLink) {
        $item = array();
        $item['uri'] = self::URI.$infoLink->href;
        // now load that uri from cache
        debugMessage("loading page ".$item['uri']);
        $articlePage = getSimpleHTMLDOMCached($item['uri']);
        $content = $articlePage->find('.l-content', 0);
        defaultImageSrcTo($content, self::URI);
        $item['title'] = $content->find('h1', 0)->innertext;
        $item['content'] = $content->find('.b-content', 0)->innertext;
        $item['timestamp'] = $content->find('.b-statistic_time', 0)->getAttribute("data-timestamp");
        $this->items[] = $item;
    }
}
