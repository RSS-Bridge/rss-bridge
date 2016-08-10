<?php
/**
* StripeAPIChangeLogBridge
*
* @name Stripe API Changelog Bridge
* @description Returns the changes made to the stripe.com API
 */
class StripeAPIChangeLogBridge extends BridgeAbstract{
  public function loadMetadatas() {

    $this->maintainer = 'Pierre Mazière';
    $this->name = 'Stripe API Changelog';
    $this->uri = 'https://stripe.com/docs/upgrades';
    $this->description = 'Returns the changes made to the stripe.com API';
    $this->update = '2016-08-09';
  }

  public function collectData(array $param){
    $html = file_get_html('https://stripe.com/docs/upgrades')
      or $this->returnError('No results for Stripe API Changelog', 404);


    foreach($html->find('h2') as $change){
      $item=new \Item();
      $item->title=trim($change->plaintext);
      $item->uri='https://stripe.com/docs/upgrades#'.$item->title;
      $item->author='stripe';
      $item->content=$change->nextSibling()->outertext;
      $item->timestamp=strtotime($item->title);
      $this->items[]=$item;
    }
  }

  public function getCacheDuration(){
    return 86400; // one day
  }
}
