<?php

class VkBridge extends BridgeAbstract
{

	const MAINTAINER = 'ahiles3005';
	const NAME = 'VK.com';
	const URI = 'https://vk.com/';
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

	protected $pageName;

	public function getURI()
	{
		if (!is_null($this->getInput('u'))) {
			return static::URI . urlencode($this->getInput('u'));
		}

		return parent::getURI();
	}

	public function getName()
	{
		if ($this->pageName) {
			return $this->pageName;
		}

		return parent::getName();
	}

	public function collectData()
	{
		$text_html = $this->getContents()
		or returnServerError('No results for group or user name "' . $this->getInput('u') . '".');

		$text_html = iconv('windows-1251', 'utf-8', $text_html);
		$html = str_get_html($text_html);
		$pageName = $html->find('.page_name', 0)->plaintext;
		$this->pageName = htmlspecialchars_decode($pageName);
		$pinned_post_item = null;
		$last_post_id = 0;

		foreach ($html->find('.post') as $post) {

			$is_pinned_post = false;
			if (strpos($post->getAttribute('class'), 'post_fixed') !== false) {
				$is_pinned_post = true;
			}

			if (is_object($post->find('a.wall_post_more', 0))) {
				//delete link "show full" in content
				$post->find('a.wall_post_more', 0)->outertext = '';
			}

			if (is_object($post->find('div.copy_quote', 0))) {
				$copy_quote = $post->find('div.copy_quote', 0);
				if ($copy_post_header = $copy_quote->find('div.copy_post_header', 0)) {
					$copy_post_header->outertext = '';
				}
				$copy_quote_content = $copy_quote->innertext;
				$copy_quote->outertext = "<br>Reposted: <br>$copy_quote_content";
			}

			$item = array();
			$item['content'] = strip_tags(backgroundToImg($post->find('div.wall_text', 0)->innertext), '<br><img>');

			if (is_object($post->find('a.page_media_link_title', 0))) {
				$link = $post->find('a.page_media_link_title', 0)->getAttribute('href');
				//external link in the post
				$item['content'] .= "\n\rExternal link: "
					. str_replace('/away.php?to=', '', urldecode($link));
			}

			//get video on post
			if (is_object($post->find('span.post_video_title_content', 0))) {
				$titleVideo = $post->find('span.post_video_title_content', 0)->plaintext;
				$linkToVideo = self::URI . $post->find('a.page_post_thumb_video', 0)->getAttribute('href');
				$item['content'] .= "\n\r {$titleVideo}: {$linkToVideo}";
			}

			// get post link
			$post_link = $post->find('a.post_link', 0)->getAttribute('href');
			preg_match("/wall-?\d+_(\d+)/", $post_link, $preg_match_result);
			$item['post_id'] = intval($preg_match_result[1]);
			if (substr(self::URI, -1) == '/') {
				$post_link = self::URI . ltrim($post_link, "/");
			} else {
				$post_link = self::URI . $post_link;
			}
			$item['uri'] = $post_link;
			$item['timestamp'] = $this->getTime($post);
			$item['title'] = $this->getTitle($item['content']);
			$item['author'] = $pageName;
			if ($is_pinned_post) {
				// do not append it now
				$pinned_post_item = $item;
			} else {
				$last_post_id = $item['post_id'];
				$this->items[] = $item;
			}

		}

		if (is_null($pinned_post_item)) {
			return;
		} else if (count($this->items) == 0) {
			$this->items[] = $pinned_post_item;
		} else if ($last_post_id < $pinned_post_item['post_id']) {
			$this->items[] = $pinned_post_item;
			usort($this->items, function ($item1, $item2) {
				return $item1['post_id'] - $item2['post_id'];
			});
		}
	}

	private function getTitle($content)
	{
		preg_match('/^["\w\ \p{Cyrillic}\(\)\?#«»-]+/mu', htmlspecialchars_decode($content), $result);
		if (count($result) == 0) return "untitled";
		return $result[0];
	}

	private function getTime($post)
	{
		if ($time = $post->find('span.rel_date', 0)->getAttribute('time')) {
			return $time;
		} else {
			$strdate = $post->find('span.rel_date', 0)->plaintext;

			$date = date_parse($strdate);
			if (!$date['year']) {
				if (strstr($strdate, 'today') !== false) {
					$strdate = date('d-m-Y') . ' ' . $strdate;
				} elseif (strstr($strdate, 'yesterday ') !== false) {
					$time = time() - 60 * 60 * 24;
					$strdate = date('d-m-Y', $time) . ' ' . $strdate;
				} else {
					$strdate = $strdate . ' ' . date('Y');
				}

				$date = date_parse($strdate);
			}
			return strtotime($date['day'] . '-' . $date['month'] . '-' . $date['year'] . ' ' .
				$date['hour'] . ':' . $date['minute']);
		}

	}

	public function getContents()
	{
		ini_set('user-agent', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:53.0) Gecko/20100101 Firefox/53.0');

		$opts = array(
			'http' => array(
				'method' => "GET",
				'user_agent' => ini_get('user_agent'),
				'accept_encoding' => 'gzip',
				'header' => "Accept-language: en\r\n 
					Cookie: remixlang=3\r\n"
			)
		);

		$context = stream_context_create($opts);

		return getContents($this->getURI(), false, $context);
	}


}
