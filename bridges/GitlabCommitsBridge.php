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
        $param=$this->parameters[$this->queriedContext];
    $uri = $param['uri']['value'].'/'.$param['u']['value'].'/'.$param['p']['value'].'/commits/';
    if(isset($param['b']['value'])){
      $uri.=$param['b']['value'];
    }else{
      $uri.='master';
    }

    $html = $this->getSimpleHTMLDOM($uri)
      or $this->returnServerError('No results for Gitlab Commits of project '.$param['uri']['value'].'/'.$param['u']['value'].'/'.$param['p']['value']);


    foreach($html->find('li.commit') as $commit){

      $item = array();
      $item['uri']=$param['uri']['value'];

      foreach($commit->getElementsByTagName('a') as $a){
        $classes=explode(' ',$a->getAttribute("class"));
        if(in_array('commit-short-id',$classes) ||
          in_array('commit_short_id',$classes)){
          $href=$a->getAttribute('href');
          $item['uri'].=substr($href,strpos($href,'/'.$param['u']['value'].'/'.$param['p']['value']));
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
