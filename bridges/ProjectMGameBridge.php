<?php
class ProjectMGameBridge extends BridgeAbstract{

	public function loadMetadatas() {

		$this->maintainer = "corenting";
		$this->name = "Project M Game Bridge";
		$this->uri = "http://projectmgame.com/en/";
		$this->description = "Returns the newest articles.";
		$this->update = "2016-08-09";

	}


  public function collectData(array $param){
    $html = '';
    $html = $this->file_get_html('http://projectmgame.com/en/') or $this->returnError('Error while downloading the Project M homepage', 404);

    foreach($html->find('article') as $article) {
      $item = new \Item();
      $item->uri = 'http://projectmgame.com/en/'.$article->find('section div.info_block a',0)->href;
      $item->title = $article->find('h1 p',0)->innertext;

      $p_list = $article->find('section p');
      $content = '';
      foreach($p_list as $p) $content .= $p->innertext;
      $item->content = $content;

      // get publication date
      $str_date = $article->find('section div.info_block a',0)->innertext;
      $item->timestamp = strtotime($str_date);
      $this->items[] = $item;
    }
  }

  public function getCacheDuration(){
    return 10800; //3 hours
  }
}
