<?php
class GithubIssueBridge extends BridgeAbstract{

  const MAINTAINER = 'Pierre Mazière';
  const NAME = 'Github Issue';
  const URI = 'https://github.com/';
  const DESCRIPTION = 'Returns the issues or comments of an issue of a github project';

  const PARAMETERS=array(
    'global'=>array (
      'u'=>array(
        'name'=>'User name',
        'required'=>true
      ),
      'p'=>array(
        'name'=>'Project name',
        'required'=>true
      )
    ),

    'Project Issues'=>array(
      'c'=>array(
        'name'=>'Show Issues Comments',
        'type'=>'checkbox'
      )
    ),
    'Issue comments'=>array(
      'i'=>array(
        'name'=>'Issue number',
        'type'=>'number',
        'required'=>'true'
      )
    )
  );

  public function getName(){
    $name=$this->getInput('u').'/'.$this->getInput('p');
    switch($this->queriedContext){
    case 'Project Issues':
      if($this->getInput('c')){
        $prefix=static::NAME.'s comments for ';
      }else{
        $prefix=static::NAME.'s for ';
      }
      $name=$prefix.$name;
      break;
    case 'Issue comments':
      $name=static::NAME.' '.$name.' #'.$this->getInput('i');
      break;
    }
    return $name;
  }

  public function getURI(){
    $uri = static::URI.$this->getInput('u').'/'.$this->getInput('p').'/issues';
    if($this->queriedContext==='Issue comments'){
      $uri.='/'.$this->getInput('i');
    }else if($this->getInput('c')){
      $uri.='?q=is%3Aissue+sort%3Aupdated-desc';
    }
    return $uri;
  }


  protected function extractIssueComment($issueNbr,$title,$comment){
    $item = array();
    $item['author']=$comment->find('img',0)->getAttribute('alt');

    $comment=$comment->firstChild()->nextSibling();

    $item['uri']= static::URI.$this->getInput('u').'/'.$this->getInput('p').'/issues/'
      .$issueNbr.'#'.$comment->getAttribute('id');
    $title.=' / '.trim($comment->firstChild()->plaintext);
    $item['title']=html_entity_decode($title,ENT_QUOTES,'UTF-8');
    $item['timestamp']=strtotime($comment->find('relative-time',0)->getAttribute('datetime'));
    $item['content']="<pre>".$comment->find('.comment-body',0)->innertext."</pre>";
    return $item;
  }

  protected function extractIssueComments($issue){
    $items=array();
    $title=$issue->find('.gh-header-title',0)->plaintext;
    $issueNbr=trim(substr($issue->find('.gh-header-number',0)->plaintext,1));
    foreach($issue->find('.js-comment-container') as $comment){
      $items[]=$this->extractIssueComment($issueNbr,$title,$comment);
    }
    return $items;
  }

  public function collectData(){
    $html = $this->getSimpleHTMLDOM($this->getURI())
      or $this->returnServerError('No results for Github Issue '.$this->getURI());

    switch($this->queriedContext){
    case 'Issue comments':
      $this->items=$this->extractIssueComments($html);
      break;
    case 'Project Issues':
      foreach($html->find('.js-active-navigation-container .js-navigation-item') as $issue){
        $info=$issue->find('.opened-by',0);
        $issueNbr=substr(trim($info->plaintext),1,strpos(trim($info->plaintext),' '));

        $item=array();
        $item['content']='';

        if($this->getInput('c')){
          $uri=static::URI.$this->getInput('u').'/'.$this->getInput('p').'/issues/'.$issueNbr;
          $issue=$this->getSimpleHTMLDOMCached($uri);
          if($issue){
            $this->items=array_merge($this->items,$this->extractIssueComments($issue));
            continue;
          }
          $item['content']='Can not extract comments from '.$uri;
        }

        $item['author']=$info->find('a',0)->plaintext;
        $item['timestamp']=strtotime($info->find('relative-time',0)->getAttribute('datetime'));
        $item['title']=html_entity_decode(
          $issue->find('.js-navigation-open',0)->plaintext,
          ENT_QUOTES,
          'UTF-8'
        );
        $comments=$issue->find('.col-5',0)->plaintext;
        $item['content'].="\n".'Comments: '.($comments?$comments:'0');
        $item['uri']=self::URI.$issue->find('.js-navigation-open',0)->getAttribute('href');
        $this->items[]=$item;
      }
      break;
    }
  }

  public function getCacheDuration(){
    return 600; // ten minutes
  }
}
