<?php
class GithubIssueBridge extends BridgeAbstract{

  public $maintainer = 'Pierre Mazière';
  public $name = 'Github Issue';
  public $uri = '';
  public $description = 'Returns the issues or comments of an issue of a github project';

  public $parameters=array(
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

    'Project Issues'=>array(),
    'Issue comments'=>array(
      'i'=>array(
        'name'=>'Issue number',
        'type'=>'number',
        'required'=>'true'
      )
    )
  );

  public function collectData(){
    $uri = 'https://github.com/'.$this->getInput('u').'/'.$this->getInput('p').'/issues/'.(isset($this->getInput('i'))?$this->getInput('i'):'');
    $html = $this->getSimpleHTMLDOM($uri)
      or $this->returnServerError('No results for Github Issue '.$this->getInput('i').' in project '.$this->getInput('u').'/'.$this->getInput('p'));

    if(isset($this->getInput('i'))){
      foreach($html->find('.js-comment-container') as $comment){

        $item = array();
        $item['author']=$comment->find('img',0)->getAttribute('alt');

        $comment=$comment->firstChild()->nextSibling();

        $item['uri']=$uri.'#'.$comment->getAttribute('id');
        $item['title']=trim($comment->firstChild()->plaintext);
        $item['timestamp']=strtotime($comment->find('relative-time',0)->getAttribute('datetime'));
        $item['content']=$comment->find('.comment-body',0)->innertext;

        $this->items[]=$item;
      }
    }else{
      foreach($html->find('.js-active-navigation-container .js-navigation-item') as $issue){
        $item=array();
        $info=$issue->find('.opened-by',0);
        $item['author']=$info->find('a',0)->plaintext;
        $item['timestamp']=strtotime($info->find('relative-time',0)->getAttribute('datetime'));
        $item['title']=$issue->find('.js-navigation-open',0)->plaintext;
        $comments=$issue->firstChild()->firstChild()
          ->nextSibling()->nextSibling()->nextSibling()->plaintext;
        $item['content']='Comments: '.($comments?$comments:'0');
        $item['uri']='https://github.com'.$issue->find('.js-navigation-open',0)->getAttribute('href');
        $this->items[]=$item;
      }
    }
  }

  public function getCacheDuration(){
    return 600; // ten minutes
  }
}
