<?php
class StripeAPIChangeLogBridge extends BridgeAbstract{
  const MAINTAINER = 'Pierre Mazière';
  const NAME = 'Stripe API Changelog';
  const URI = 'https://stripe.com/docs/upgrades';
  const DESCRIPTION = 'Returns the changes made to the stripe.com API';

  public function collectData(){
    $html = $this->getSimpleHTMLDOM(self::URI)
      or $this->returnServerError('No results for Stripe API Changelog');


    foreach($html->find('h3') as $change){
      $item=array();
      $item['title']=trim($change->plaintext);
      $item['uri']=self::URI.'#'.$item['title'];
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
