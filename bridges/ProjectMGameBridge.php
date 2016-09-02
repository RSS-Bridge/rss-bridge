<?php
class ProjectMGameBridge extends BridgeAbstract{

	const MAINTAINER = "corenting";
	const NAME = "Project M Game Bridge";
	const URI = "http://projectmgame.com/en/";
	const DESCRIPTION = "Returns the newest articles.";


  public function collectData(){
    $html = $this->getSimpleHTMLDOM(self::URI)
      or $this->returnServerError('Error while downloading the Project M homepage');

    foreach($html->find('article') as $article) {
      $item = array();
      $item['uri'] = self::URI.$article->find('section div.info_block a',0)->href;
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
