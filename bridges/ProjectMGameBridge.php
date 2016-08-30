<?php
class ProjectMGameBridge extends BridgeAbstract{

	public $maintainer = "corenting";
	public $name = "Project M Game Bridge";
	public $uri = "http://projectmgame.com/en/";
	public $description = "Returns the newest articles.";


  public function collectData(){
    $html = '';
    $html = $this->getSimpleHTMLDOM('http://projectmgame.com/en/') or $this->returnServerError('Error while downloading the Project M homepage');

    foreach($html->find('article') as $article) {
      $item = array();
      $item['uri'] = 'http://projectmgame.com/en/'.$article->find('section div.info_block a',0)->href;
      $item['title'] = $article->find('h1 p',0)->innertext;

      $p_list = $article->find('section p');
      $content = '';
      foreach($p_list as $p) $content .= $p->innertext;
      $item['content'] = $content;

      // get publication date
      $str_date = $article->find('section div.info_block a',0)->innertext;
      $item['timestamp'] = strtotime($str_date);
      $this->items[] = $item;
    }
  }

  public function getCacheDuration(){
    return 10800; //3 hours
  }
}
