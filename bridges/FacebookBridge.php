<?php
/**
*
* @name Facebook
* @homepage http://facebook.com/
* @description Facebook bridge
* @update 03/08/2015
* @maintainer teromene
* @use1(u="username")
*/

class FacebookBridge extends BridgeAbstract{

    public function collectData(array $param){
	
	$html = '';
	
	if(isset($param['u'])) {

        	$html = file_get_html('https://facebook.com/'.urlencode($param['u']).'?_fb_noscript=1') or $this->returnError('No results for this query.', 404);
	} else {

		$this->returnError('You must specify a Facebook username.', 400);

	}



	$element = $html->find("[id^=PagePostsSectionPagelet-]")[0]->children(0)->children(0);
	
	foreach($element->children() as $post) {
		
		$item = new \Item();

		if($post->hasAttribute("data-time")) {

			//Clean the content of the page
			$content = preg_replace('/(?i)><div class=\"clearfix([^>]+)>(.+?)div\ class=\"userContent\"/i', "", $post);
			$content = preg_replace('/(?i)><div class=\"_59tj([^>]+)>(.+?)<\/div><\/div><a/i', "", $content);
			$content = preg_replace('/(?i)><div class=\"_3dp([^>]+)>(.+?)div\ class=\"[^u]+userContent\"/i', "", $content);
			$content = preg_replace('/(?i)><div class=\"_4l5([^>]+)>(.+?)<\/div>/i', "", $content);

			$content = strip_tags($content,"<a><img>");
			

			$date = $post->find("abbr")[0];
			if(isset($date) && $date->hasAttribute("data-utime")) {
				$date = $date->getAttribute("data-utime");
			} else {
				$date = 0;
			}

			$item->uri = 'https://facebook.com/'.urlencode($param['u']);
			$item->content = $content;
			$item->title = $param['u']." | ".strip_tags($content);
			$item->timestamp = $date;
		
			$this->items[] = $item;
		}
	}



    }

    public function getName(){
        return 'Facebook Bridge';
    }

    public function getURI(){
        return 'http://facebook.com';
    }

    public function getCacheDuration(){
        return 300; // 5 minutes
    }
}

?>
