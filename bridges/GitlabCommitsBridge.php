<?php
/**
* GitlabCommitsBridge
*
* @name GitlabCommits Bridge
* @description Returns the commits of a project hosted on a gitlab instance
 */
class GitlabCommitsBridge extends BridgeAbstract{
  public function loadMetadatas() {

    $this->maintainer = 'Pierre Mazière';
    $this->name = 'Gitlab Commits';
    $this->uri = '';
    $this->description = 'Returns the commits of a project hosted on a gitlab instance';

    $this->parameters[] =
      '[
         {
            "name" : "Base URI",
            "identifier" : "uri",
            "defaultValue":"https://gitlab.com"
         },
         {
           "name" : "User name",
           "identifier" : "u",
           "required": true
         },
         {
            "name" : "Project name",
            "identifier" : "p",
            "required":true
         },
         {
            "name" : "Project branch",
            "identifier" : "b",
            "defaultValue":"master"
         }

      ]';
  }

  public function collectData(array $param){
    $uri = $param['uri'].'/'.$param['u'].'/'.$param['p'].'/commits/';
    if(isset($param['b'])){
      $uri.=$param['b'];
    }else{
      $uri.='master';
    }

    $html = $this->getSimpleHTMLDOM($uri)
      or $this->returnServerError('No results for Gitlab Commits of project '.$param['uri'].'/'.$param['u'].'/'.$param['p']);


    foreach($html->find('li.commit') as $commit){

      $item = new \Item();
      $item->uri=$param['uri'];

      foreach($commit->getElementsByTagName('a') as $a){
        $classes=explode(' ',$a->getAttribute("class"));
        if(in_array('commit-short-id',$classes) ||
          in_array('commit_short_id',$classes)){
          $href=$a->getAttribute('href');
          $item->uri.=substr($href,strpos($href,'/'.$param['u'].'/'.$param['p']));
        }
        if(in_array('commit-row-message',$classes)){
          $item->title=$a->plaintext;
        }
        if(in_array('commit-author-link',$classes)){
          $item->author=trim($a->plaintext);
        }
      }

      $pre=$commit->find('pre',0);
      if($pre){
        $item->content=$pre->outertext;
      }else{
        $item->content='';
      }
      $item->timestamp=strtotime($commit->find('time',0)->getAttribute('datetime'));

      $this->items[]=$item;
    }
  }
}
