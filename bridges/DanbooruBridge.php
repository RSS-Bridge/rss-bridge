<?php
class DanbooruBridge extends BridgeAbstract{

  const MAINTAINER = "mitsukarenai";
  const NAME = "Danbooru";
  const URI = "http://donmai.us/";
  const CACHE_TIMEOUT = 1800; // 30min
  const DESCRIPTION = "Returns images from given page";

  const PARAMETERS = array(
    'global'=>array(
      'p'=>array(
        'name'=>'page',
        'defaultValue'=>1,
        'type'=>'number'
      ),
      't'=>array('name'=>'tags')
    ),
    0=>array()
  );

  const PATHTODATA='article';
  const IDATTRIBUTE='data-id';

  protected function getFullURI(){
    return $this->getURI().'posts?'
      .'&page='.$this->getInput('p')
      .'&tags='.urlencode($this->getInput('t'));
  }

  protected function getItemFromElement($element){
    $item = array();
    $item['uri'] = $this->getURI().$element->find('a', 0)->href;
    $item['postid'] = (int)preg_replace("/[^0-9]/",'', $element->getAttribute(static::IDATTRIBUTE));
    $item['timestamp'] = time();
    $thumbnailUri = $this->getURI().$element->find('img', 0)->src;
    $item['tags'] = $element->find('img', 0)->getAttribute('alt');
    $item['title'] = $this->getName().' | '.$item['postid'];
    $item['content'] = '<a href="' . $item['uri'] . '"><img src="' . $thumbnailUri . '" /></a><br>Tags: '.$item['tags'];
    return $item;
  }

  public function collectData(){
    $html = getSimpleHTMLDOM($this->getFullURI())
      or returnServerError('Could not request '.$this->getName());

    foreach($html->find(static::PATHTODATA) as $element) {
      $this->items[] = $this->getItemFromElement($element);
    }
  }
}
