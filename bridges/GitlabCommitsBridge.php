<?php
class GitlabCommitsBridge extends BridgeAbstract{

    public $maintainer = 'Pierre Mazière';
    public $name = 'Gitlab Commits';
    public $uri = '';
    public $description = 'Returns the commits of a project hosted on a gitlab instance';

    public $parameters = array( array(
      'uri'=>array(
        'name'=>'Base URI',
        'defaultValue'=>'https://gitlab.com'
      ),
      'u'=>array(
        'name'=>'User name',
        'required'=>true
      ),
      'p'=>array(
        'name'=>'Project name',
        'required'=>true
      ),
      'b'=>array(
        'name'=>'Project branch',
        'defaultValue'=>'master'
      )
    ));

  public function collectData(){
    $uri = $this->getInput('uri').'/'.$this->getInput('u').'/'.$this->getInput('p').'/commits/';
    if(isset($this->getInput('b'))){
      $uri.=$this->getInput('b');
    }else{
      $uri.='master';
    }

    $html = $this->getSimpleHTMLDOM($uri)
      or $this->returnServerError('No results for Gitlab Commits of project '.$this->getInput('uri').'/'.$this->getInput('u').'/'.$this->getInput('p'));


    foreach($html->find('li.commit') as $commit){

      $item = array();
      $item['uri']=$this->getInput('uri');

      foreach($commit->getElementsByTagName('a') as $a){
        $classes=explode(' ',$a->getAttribute("class"));
        if(in_array('commit-short-id',$classes) ||
          in_array('commit_short_id',$classes)){
          $href=$a->getAttribute('href');
          $item['uri'].=substr($href,strpos($href,'/'.$this->getInput('u').'/'.$this->getInput('p')));
        }
        if(in_array('commit-row-message',$classes)){
          $item['title']=$a->plaintext;
        }
        if(in_array('commit-author-link',$classes)){
          $item['author']=trim($a->plaintext);
        }
      }

      $pre=$commit->find('pre',0);
      if($pre){
        $item['content']=$pre->outertext;
      }else{
        $item['content']='';
      }
      $item['timestamp']=strtotime($commit->find('time',0)->getAttribute('datetime'));

      $this->items[]=$item;
    }
  }
}
