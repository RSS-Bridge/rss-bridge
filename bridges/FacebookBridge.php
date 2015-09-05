<?php
/**
* @name Facebook
* @homepage http://facebook.com/
* @description Input a page title or a profile log. For a profile log, please insert the parameter as follow : myExamplePage/132621766841117
* @update 05/09/2015
* @maintainer teromene
* @use1(u="username")
*/
class FacebookBridge extends BridgeAbstract{

	private $name;

	public function collectData(array $param){

		$html = '';

		if(isset($param['u'])) {
			if(!strpos($param['u'], "/")) {
				$html = file_get_html('https://facebook.com/'.urlencode($param['u']).'?_fb_noscript=1') or $this->returnError('No results for this query.', 404);
			} else {
				$html = file_get_html('https://facebook.com/pages/'.$param['u'].'?_fb_noscript=1') or $this->returnError('No results for this query.', 404);
			}
		} else {
			$this->returnError('You must specify a Facebook username.', 400);
		}

		$element = $html->find('[id^=PagePostsSectionPagelet-]')[0]->children(0)->children(0);

		if(isset($element)) {

			$author = str_replace(' | Facebook', '', $html->find('title#pageTitle', 0)->innertext);
			$profilePic = 'https://graph.facebook.com/'.$param['u'].'/picture?width=200&amp;height=200';
			$this->name = $author;

			foreach($element->children() as $post) {
			
				$item = new \Item();

				if($post->hasAttribute("data-time")) {

					//Clean the content of the page and convert relative links into absolute links
					$content = preg_replace('/(?i)><div class=\"clearfix([^>]+)>(.+?)div\ class=\"userContent\"/i', '', $post);
					$content = preg_replace('/(?i)><div class=\"_59tj([^>]+)>(.+?)<\/div><\/div><a/i', '', $content);
					$content = preg_replace('/(?i)><div class=\"_3dp([^>]+)>(.+?)div\ class=\"[^u]+userContent\"/i', '', $content);
					$content = preg_replace('/(?i)><div class=\"_4l5([^>]+)>(.+?)<\/div>/i', '', $content);
					$content = str_replace(' href="/', ' href="https://facebook.com/', $content);
					$content = preg_replace('/ onmouseover=\"[^"]+\"/i', '', $content);
					$content = preg_replace('/ onclick=\"[^"]+\"/i', '', $content);
					$content = preg_replace('/<\/a [^>]+>/i', '</a>', $content);
					$content = strip_tags($content,'<a><img>');

					//Retrieve date of the post
					$date = $post->find("abbr")[0];
					if(isset($date) && $date->hasAttribute('data-utime')) {
						$date = $date->getAttribute('data-utime');
					} else {
						$date = 0;
					}

					//Build title from username and content
					$title = $author;
					if (strlen($title) > 24)
						$title = substr($title, 0, strpos(wordwrap($title, 24), "\n")).'...';
					$title = $title.' | '.strip_tags($content);
					if (strlen($title) > 64)
						$title = substr($title, 0, strpos(wordwrap($title, 64), "\n")).'...';

					//Use first image as thumbnail if available, or profile pic fallback
					$thumbnail = $post->find('img', 1)->src;
					if (strlen($thumbnail) == 0)
						$thumbnail = $profilePic;

					//Build and add final item
					$item->uri = 'https://facebook.com'.str_replace('&amp;', '&', $post->find('abbr')[0]->parent()->getAttribute('href'));
					$item->thumbnailUri = $thumbnail;
					$item->content = $content;
					$item->title = $title;
					$item->author = $author;
					$item->timestamp = $date;
					$this->items[] = $item;
				}
			}
		}

	}

	public function getName() {
		return (isset($this->name) ? $this->name.' - ' : '').'Facebook Bridge';
	}

	public function getURI() {
		return 'http://facebook.com';
	}

	public function getCacheDuration() {
		return 300; // 5 minutes
	}
}
