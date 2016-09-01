<?php
class StripeAPIChangeLogBridge extends BridgeAbstract{
  public $maintainer = 'Pierre Mazière';
  public $name = 'Stripe API Changelog';
  public $uri = 'https://stripe.com/docs/upgrades';
  public $description = 'Returns the changes made to the stripe.com API';

  public function collectData(){
    $html = $this->getSimpleHTMLDOM($this->uri)
      or $this->returnServerError('No results for Stripe API Changelog');


    foreach($html->find('h3') as $change){
      $item=array();
      $item['title']=trim($change->plaintext);
      $item['uri']=$this->uri.'#'.$item['title'];
      $item['author']='stripe';
      $item['content']=$change->nextSibling()->outertext;
      $item['timestamp']=strtotime($item['title']);
      $this->items[]=$item;
    }
  }

  public function getCacheDuration(){
    return 86400; // one day
  }
}
