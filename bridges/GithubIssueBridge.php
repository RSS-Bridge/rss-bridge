<?php
class GithubIssueBridge extends BridgeAbstract{

  const MAINTAINER = 'Pierre Mazière';
  const NAME = 'Github Issue';
  const URI = 'https://github.com/';
  const CACHE_TIMEOUT = 600; // 10min
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
    $class=$comment->getAttribute('class');
    $classes=explode(' ',$class);
    $event=false;
    if(in_array('discussion-item',$classes)){
      $event=true;
    }

    $author='unknown';
    if($comment->find('.author',0)){
      $author=$comment->find('.author',0)->plaintext;
    }

    $uri=static::URI.$this->getInput('u').'/'.$this->getInput('p').'/issues/'
      .$issueNbr;

    $comment=$comment->firstChild();
    if(!$event){
      $comment=$comment->nextSibling();
    }

    if($event){
      $title.=' / '.substr($class,strpos($class,'discussion-item-')+strlen('discussion-item-'));
      if(!$comment->hasAttribute('id')){
        $items=array();
        $timestamp=strtotime($comment->find('relative-time',0)->getAttribute('datetime'));
        $content=$comment->innertext;
        while($comment=$comment->nextSibling()){
          $item=array();
          $item['author']=$author;
          $item['title']=html_entity_decode($title,ENT_QUOTES,'UTF-8');
          $item['timestamp']=$timestamp;
          $item['content']=$content.'<p>'.$comment->children(1)->innertext.'</p>';
          $item['uri']=$uri.'#'.$comment->children(1)->getAttribute('id');
          $items[]=$item;
        }
        return $items;
      }
      $content=$comment->parent()->innertext;
    }else{
      $title.=' / '.trim($comment->firstChild()->plaintext);
      $content="<pre>".$comment->find('.comment-body',0)->innertext."</pre>";
    }

    $item = array();
    $item['author']=$author;
    $item['uri']= $uri.'#'.$comment->getAttribute('id');
    $item['title']=html_entity_decode($title,ENT_QUOTES,'UTF-8');
    $item['timestamp']=strtotime($comment->find('relative-time',0)->getAttribute('datetime'));
    $item['content']=$content;
    return $item;
  }

  protected function extractIssueComments($issue){
    $items=array();
    $title=$issue->find('.gh-header-title',0)->plaintext;
    $issueNbr=trim(substr($issue->find('.gh-header-number',0)->plaintext,1));
    $comments=$issue->find('.js-discussion',0);
    foreach($comments->children() as $comment){
      $classes=explode(' ',$comment->getAttribute('class'));
      if(in_array('discussion-item',$classes) ||
        in_array('timeline-comment-wrapper',$classes)
      ){
        $item=$this->extractIssueComment($issueNbr,$title,$comment);
        if(array_keys($item)!==range(0,count($item)-1)){
          $item=array($item);
        }
        $items=array_merge($items,$item);
      }
    }
    return $items;
  }

  public function collectData(){
    $html = getSimpleHTMLDOM($this->getURI())
      or returnServerError('No results for Github Issue '.$this->getURI());

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
          $issue=getSimpleHTMLDOMCached($uri,static::CACHE_TIMEOUT);
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

    array_walk($this->items, function(&$item){
      $item['content']=preg_replace('/\s+/',' ',$item['content']);
      $item['content']=str_replace('href="/','href="'.static::URI,$item['content']);
      $item['content']=str_replace(
        'href="#',
        'href="'.substr($item['uri'],0,strpos($item['uri'],'#')+1),
        $item['content']
      );
      $item['title']=preg_replace('/\s+/',' ',$item['title']);
    });
  }
}
