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
    $this->update = '2016-06-19';

    $this->parameters=
      '[
         {
            "name" : "Base URI",
            "identifier" : "uri"
         },
         {
           "name" : "User name",
           "identifier" : "u"
         },
         {
            "name" : "Project name",
            "identifier" : "p"
         }
         {
            "name" : "Project branch",
            "identifier" : "b"
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

    $html = file_get_html($uri)
      or $this->returnError('No results for LWNprev', 404);


    foreach($html->find('li') as $commit){
      if(!in_array('commit',explode(' ',$commit->getAttribute("class")))){
        continue;
      }

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
          $item->name=trim($a->plaintext);
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

  public function getName(){
    return 'Gitlab Commits';
  }

  public function getURI(){
    return '';
  }

  public function getCacheDuration(){
    return 3600; // one hour
  }
}
