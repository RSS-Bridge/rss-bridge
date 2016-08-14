<?php
/**
* GithubIssueBridge
*
* @name GithubIssue Bridge
* @description Returns the comments of a github project issue
 */
class GithubIssueBridge extends BridgeAbstract{
  public function loadMetadatas() {

    $this->maintainer = 'Pierre Mazière';
    $this->name = 'Github Issue';
    $this->uri = '';
    $this->description = 'Returns the comments of a github project issue';
    $this->update = '2016-08-09';

    $this->parameters[]=
      '[
         {
           "name" : "User name",
           "identifier" : "u"
         },
         {
            "name" : "Project name",
            "identifier" : "p"
         },
         {
            "name" : "Issue number",
            "identifier" : "i"
         }

      ]';
  }

  public function collectData(array $param){
    $uri = 'https://github.com/'.$param['u'].'/'.$param['p'].'/issues/'.$param['i'];
    $html = file_get_html($uri)
      or $this->returnError('No results for Github Issue '.$param['i'].' in project '.$param['u'].'/'.$param['p'], 404);

    foreach($html->find('.js-comment-container') as $comment){

      $item = new \Item();
      $item->author=$comment->find('img',0)->getAttribute('alt');

      $comment=$comment->firstChild()->nextSibling();

      $item->uri=$uri.'#'.$comment->getAttribute('id');
      $item->title=trim($comment->firstChild()->plaintext);
      $item->timestamp=strtotime($comment->find('relative-time',0)->getAttribute('datetime'));
      $item->content=$comment->find('.comment-body',0)->innertext;

      $this->items[]=$item;
    }
  }

  public function getCacheDuration(){
    return 600; // ten minutes
  }
}
