<?php
/**
 * RssBridgeLWNprev
 *
 * @name LWNPrev Bridge
 * @description Returns the articles from the previous LWN.net edition
 */
class LWNprevBridge extends BridgeAbstract{
  public function loadMetadatas() {

    $this->maintainer = 'Pierre Mazière';
    $this->name = 'LWN Free Weekly Edition';
    $this->uri = 'https://lwn.net/free/bigpage';
    $this->description = 'LWN Free Weekly Edition available one week late';

  }

  private function jumpToNextTag(&$node){
    while($node && $node->nodeType===XML_TEXT_NODE){
      $nextNode=$node->nextSibling;
      if(!$nextNode){
        break;
      }
      $node=$nextNode;
    }
  }

  private function jumpToPreviousTag(&$node){
    while($node && $node->nodeType===XML_TEXT_NODE){
      $previousNode=$node->previousSibling;
      if(!$previousNode){
        break;
      }
      $node=$previousNode;
    }
  }

  public function collectData(){
    // Because the LWN page is written in loose HTML and not XHTML,
    // Simple HTML Dom is not accurate enough for the job

    $uri='https://lwn.net/free/bigpage';
    $context=null;
    if(defined('PROXY_URL')) {
      $context = array(
        'http' => array(
          'proxy' => PROXY_URL,
          'request_fulluri' => true,
        ),
      );
      $context = stream_context_create($context);
    }

    $content=file_get_contents($uri, false, $context)
      or $this->returnServerError('No results for LWNprev');

    libxml_use_internal_errors(true);
    $html=new DOMDocument();
    $html->loadHTML($content);
    libxml_clear_errors();

    $cat1='';
    $cat2='';

    $realURI='https://lwn.net';
    foreach($html->getElementsByTagName('a') as $a){
      if($a->textContent==='Multi-page format'){
        break;
      }
    }
    $realURI.=$a->getAttribute('href');
    $URICounter=0;

    $edition=$html->getElementsByTagName('h1')->item(0)->textContent;
    $editionTimeStamp=strtotime(
      substr($edition,strpos($edition,'for ')+strlen('for '))
    );

    foreach($html->getElementsByTagName('h2') as $h2){
      if($h2->getAttribute('class')!=='SummaryHL'){
        continue;
      }

      $item = array();

      $h2NextSibling=$h2->nextSibling;
      $this->jumpToNextTag($h2NextSibling);

      switch($h2NextSibling->getAttribute('class')){
      case 'FeatureByline':
        $item['author']=$h2NextSibling->getElementsByTagName('b')->item(0)->textContent;
        break;
      case 'GAByline':
        $text=$h2NextSibling->textContent;
        $item['author']=substr($text,strpos($text,'by '));
        break;
      default:
        $item['author']='LWN';
        break;
      };

      $h2FirstChild=$h2->firstChild;
      $this->jumpToNextTag($h2FirstChild);
      if($h2FirstChild->nodeName==='a'){
        $item['uri']='https://lwn.net'.$h2FirstChild->getAttribute('href');
      }else{
        $item['uri']=$realURI.'#'.$URICounter;
      }
      $URICounter++;

      $item['timestamp']=$editionTimeStamp+$URICounter;

      $h2PrevSibling=$h2->previousSibling;
      $this->jumpToPreviousTag($h2PrevSibling);
      switch($h2PrevSibling->getAttribute('class')){
      case 'Cat2HL':
        $cat2=$h2PrevSibling->textContent;
        $h2PrevSibling=$h2PrevSibling->previousSibling;
        $this->jumpToPreviousTag($h2PrevSibling);
        if($h2PrevSibling->getAttribute('class')!=='Cat1HL'){
          break;
        }
        $cat1=$h2PrevSibling->textContent;
        break;
      case 'Cat1HL':
        $cat1=$h2PrevSibling->textContent;
        $cat2='';
        break;
      default:
        break;
      }
      $h2PrevSibling=null;

      $item['title']='';
      if(!empty($cat1)){
        $item['title'].='['.$cat1.($cat2?'/'.$cat2:'').'] ';
      }
      $item['title'].=$h2->textContent;

      $node=$h2;
      $content='';
      $contentEnd=false;
      while(!$contentEnd){
        $node=$node->nextSibling;
        if(
          !$node || (
            $node->nodeType!==XML_TEXT_NODE && (
              $node->nodeName==='h2' ||
              (!is_null($node->attributes) && !is_null($class=$node->attributes->getNamedItem('class')) &&
              in_array($class->nodeValue,array('Cat1HL','Cat2HL')))
            )
          )
        ){
          $contentEnd=true;
        }else{
          $content.=$node->C14N();
        }
      }
      $item['content']=$content;
      $this->items[]=$item;
    }
  }

  public function getCacheDuration(){
    return 604800; // one week
  }
}
