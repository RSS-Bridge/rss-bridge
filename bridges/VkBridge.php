<?php
class VkBridge extends BridgeAbstract {

	const MAINTAINER = 'ahiles3005';
	const NAME = 'VK.com';
	const URI = 'http://vk.com/';
	const CACHE_TIMEOUT = 300; // 5min
	const DESCRIPTION = 'Working with open pages';
	const PARAMETERS = array(
		array(
			'u' => array(
				'name' => 'Group or user name',
				'required' => true
			)
		)
	);

	public function getURI(){
		if(!is_null($this->getInput('u'))) {
			return static::URI . urlencode($this->getInput('u'));
		}

		return parent::getURI();
	}

	public function collectData(){

		ini_set('user-agent', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:53.0) Gecko/20100101 Firefox/53.0');

		$text_html = getContents($this->getURI())
			or returnServerError('No results for group or user name "' . $this->getInput('u') . '".');

		$text_html = iconv('windows-1251', 'utf-8', $text_html);
		$html = str_get_html($text_html);

		foreach($html->find('.post') as $post) {

			if(is_object($post->find('a.wall_post_more', 0))) {
				//delete link "show full" in content
				$post->find('a.wall_post_more', 0)->outertext = '';
			}
			$item = array();
			$item['content'] = strip_tags(backgroundToImg($post->find('div.wall_text', 0)->innertext), '<br><img>');
			if(is_object($post->find('a.page_media_link_title', 0))) {
				$link = $post->find('a.page_media_link_title', 0)->getAttribute('href');

				//external link in the post
				$item['content'] .= "\n\rExternal link: "
				. str_replace('/away.php?to=', '', urldecode($link));
			}

			//get video on post
			if(is_object($post->find('span.post_video_title_content', 0))) {
				$titleVideo = $post->find('span.post_video_title_content', 0)->plaintext;
				$linkToVideo = self::URI . $post->find('a.page_post_thumb_video', 0)->getAttribute('href');
				$item['content'] .= "\n\r {$titleVideo}: {$linkToVideo}";
			}

			// get post link
			$item['uri'] = self::URI . $post->find('a.post_link', 0)->getAttribute('href');
			$item['date'] = $post->find('span.rel_date', 0)->plaintext;
			$this->items[] = $item;
			// var_dump($item['date']);
		}
	}
}
