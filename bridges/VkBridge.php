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
		// makes album link generating work correctly
		$text_html = str_replace('"class="page_album_link">', '" class="page_album_link">', $text_html);
		$html = str_get_html($text_html);
		$pageName = $html->find('.page_name', 0);
		if (is_object($pageName)) {
			$pageName = $pageName->plaintext;
			$this->pageName = htmlspecialchars_decode($pageName);
		}
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

			$content_suffix = "";

			// looking for external links
			$external_link_selectors = array(
				'a.page_media_link_title',
				'div.page_media_link_title > a',
				'div.media_desc > a.lnk',
			);

			foreach($external_link_selectors as $sel) {
				if (is_object($post->find($sel, 0))) {
					$a = $post->find($sel, 0);
					$innertext = $a->innertext;
					$parsed_url = parse_url($a->getAttribute('href'));
					if (strpos($parsed_url['path'], '/away.php') !== 0) continue;
					parse_str($parsed_url["query"], $parsed_query);
					$content_suffix .= "<br>External link: <a href='" . $parsed_query["to"] . "'>$innertext</a>";
				}
			}

			// remove external link from content
			$external_link_selectors_to_remove = array(
				'div.page_media_thumbed_link',
				'div.page_media_link_desc_wrap',
				'div.media_desc > a.lnk',
			);

			foreach($external_link_selectors_to_remove as $sel) {
				if (is_object($post->find($sel, 0))) {
					$post->find($sel, 0)->outertext = '';
				}
			}

			// looking for article
			$article = $post->find("a.article_snippet", 0);
			if (is_object($article)) {
				$article_title = $article->find("div.article_snippet__title", 0)->innertext;
				$article_author = $article->find("div.article_snippet__author", 0)->innertext;
				$article_link = self::URI . ltrim($article->getAttribute('href'), '/');
				$article_img_element_style = $article->find("div.article_snippet__image", 0)->getAttribute('style');
				preg_match('/background-image: url\((.*)\)/', $article_img_element_style, $matches);
				if (count($matches) > 0) {
					$content_suffix .= "<br><img src='" . $matches[1] . "'>";
				}
				$content_suffix .= "<br>Article: <a href='$article_link'>$article_title ($article_author)</a>";
				$article->outertext = '';
			}

			// get video on post
			$video = $post->find('div.post_video_desc', 0);
			if (is_object($video)) {
				$video_title = $video->find('div.post_video_title', 0)->plaintext;
				$video_link = self::URI . ltrim( $video->find('a.lnk', 0)->getAttribute('href'), '/' );
				$content_suffix .= "<br>Video: <a href='$video_link'>$video_title</a>";
				$video->outertext = '';
			}

			// get all photos
			foreach($post->find('div.wall_text > a.page_post_thumb_wrap') as $a) {
				$result = $this->getPhoto($a);
				if ($result == null) continue;
				$a->outertext = '';
				$content_suffix .= "<br>$result";
			}

			// get albums
			foreach($post->find('.page_album_wrap') as $el) {
				$a = $el->find('.page_album_link', 0);
				$album_title = $a->find('.page_album_title_text', 0)->getAttribute('title');
				$album_link = self::URI . ltrim($a->getAttribute('href'), '/');
				$el->outertext = '';
				$content_suffix .= "<br>Album: <a href='$album_link'>$album_title</a>";
			}

			// get photo documents
			foreach($post->find('a.page_doc_photo_href') as $a) {
				$doc_link = self::URI . ltrim($a->getAttribute('href'), '/');
				$doc_gif_label_element = $a->find(".page_gif_label", 0);
				$doc_title_element = $a->find(".doc_label", 0);

				if (is_object($doc_gif_label_element)) {
					$gif_preview_img = backgroundToImg($a->find('.page_doc_photo', 0));
					$content_suffix .= "<br>Gif: <a href='$doc_link'>$gif_preview_img</a>";

				} else if (is_object($doc_title_element)) {
					$doc_title = $doc_title_element->innertext;
					$content_suffix .= "<br>Doc: <a href='$doc_link'>$doc_title</a>";

				} else {
					continue;

				}

				$a->outertext = '';
			}

			// get other documents
			foreach($post->find('div.page_doc_row') as $div) {
				$doc_title_element = $div->find("a.page_doc_title", 0);

				if (is_object($doc_title_element)) {
					$doc_title = $doc_title_element->innertext;
					$doc_link = self::URI . ltrim($doc_title_element->getAttribute('href'), '/');
					$content_suffix .= "<br>Doc: <a href='$doc_link'>$doc_title</a>";

				} else {
					continue;

				}

				$div->outertext = '';
			}

			// get sign
			$post_author = $pageName;
			foreach($post->find('a.wall_signed_by') as $a) {
				$post_author = $a->innertext;
				$a->outertext = '';
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
			$item['content'] .= $content_suffix;

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
			$item['author'] = $post_author;
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
				return $item2['post_id'] - $item1['post_id'];
			});
		}
	}

	private function getPhoto($a) {
		$onclick = $a->getAttribute('onclick');
		preg_match('/return showPhoto\(.+?({.*})/', $onclick, $preg_match_result);
		if (count($preg_match_result) == 0) return;

		$arg = htmlspecialchars_decode( str_replace('queue:1', '"queue":1', $preg_match_result[1]) );
		$data = json_decode($arg, true);
		if ($data == null) return;

		$thumb = $data['temp']['base'] . $data['temp']['x_'][0] . ".jpg";
		$original = '';
		foreach(array('y_', 'z_', 'w_') as $key) {
			if (!isset($data['temp'][$key])) continue;
			$original = $data['temp']['base'] . $data['temp'][$key][0] . ".jpg";
		}

		if ($original) {
			return "<a href='$original'><img src='$thumb'></a>";
		} else {
			return "<img src='$thumb'>";
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

		$header = array('Accept-language: en', 'Cookie: remixlang=3');

		return getContents($this->getURI(), $header);
	}


}
