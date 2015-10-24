<?php
/**
* @name Facebook
* @homepage http://facebook.com/
* @description Input a page title or a profile log. For a profile log, please insert the parameter as follow : myExamplePage/132621766841117
* @update 23/10/2015
* @maintainer teromene
* @use1(u="username")
*/
class FacebookBridge extends BridgeAbstract{

	private $name;

	public function collectData(array $param){

		//Extract a string using start and end delimiters
		function ExtractFromDelimiters($string, $start, $end) {
			if (strpos($string, $start) !== false) {
				$section_retrieved = substr($string, strpos($string, $start) + strlen($start));
				$section_retrieved = substr($section_retrieved, 0, strpos($section_retrieved, $end));
				return $section_retrieved;
			} return false;
		}

		//Utility function for cleaning a Facebook link
		$unescape_fb_link = function ($matches) {
			if (is_array($matches) && count($matches) > 1) {
				$link = $matches[1];
				if (strpos($link, '/') === 0)
					$link = 'https://facebook.com'.$link.'"';
				if (strpos($link, 'facebook.com/l.php?u=') !== false)
					$link = urldecode(ExtractFromDelimiters($link, 'facebook.com/l.php?u=', '&'));
				return ' href="'.$link.'"';
			}
		};

		//Utility function for converting facebook emoticons
		$unescape_fb_emote = function ($matches) {
			static $facebook_emoticons = array(
				'smile' => ':)',
				'frown' => ':(',
				'tongue' => ':P',
				'grin' => ':D',
				'gasp' => ':O',
				'wink' => ';)',
				'pacman' => ':<',
				'grumpy' => '>_<',
				'unsure' => ':/',
				'cry' => ':\'(',
				'kiki' => '^_^',
				'glasses' => '8-)',
				'sunglasses' => 'B-)',
				'heart' => '<3',
				'devil' => ']:D',
				'angel' => '0:)',
				'squint' => '-_-',
				'confused' => 'o_O',
				'upset' => 'xD',
				'colonthree' => ':3',
				'like' => '&#x1F44D;');
			$len = count($matches);
			if ($len > 1)
				for ($i = 1; $i < $len; $i++)
					foreach ($facebook_emoticons as $name => $emote)
						if ($matches[$i] === $name)
							return $emote;
			return $matches[0];
		};

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

					//Retrieve post contents
					$content = preg_replace('/(?i)><div class=\"clearfix([^>]+)>(.+?)div\ class=\"userContent\"/i', '', $post);
					$content = preg_replace('/(?i)><div class=\"_59tj([^>]+)>(.+?)<\/div><\/div><a/i', '', $content);
					$content = preg_replace('/(?i)><div class=\"_3dp([^>]+)>(.+?)div\ class=\"[^u]+userContent\"/i', '', $content);
					$content = preg_replace('/(?i)><div class=\"_4l5([^>]+)>(.+?)<\/div>/i', '', $content);

					//Remove html nodes, keep only img, links, basic formatting
					$content = strip_tags($content,'<a><img><i><u>');

					//Adapt link hrefs: convert relative links into absolute links and bypass external link redirection
					$content = preg_replace_callback('/ href=\"([^"]+)\"/i', $unescape_fb_link, $content);

					//Clean useless html tag properties and fix link closing tags
					foreach (array('onmouseover', 'onclick', 'target', 'ajaxify', 'tabindex',
						'class', 'style', 'data-[^=]*', 'aria-[^=]*', 'role', 'rel', 'id') as $property_name)
							$content = preg_replace('/ '.$property_name.'=\"[^"]*\"/i', '', $content);
					$content = preg_replace('/<\/a [^>]+>/i', '</a>', $content);

					//Convert textual representation of emoticons eg "<i><u>smile emoticon</u></i>" back to ASCII emoticons eg ":)"
					$content = preg_replace_callback('/<i><u>([^ <>]+) ([^<>]+)<\/u><\/i>/i', $unescape_fb_emote, $content);

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
