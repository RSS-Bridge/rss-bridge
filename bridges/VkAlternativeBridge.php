<?php
class VkAlternativeBridge extends BridgeAbstract {
	const NAME = 'Alternative VK.com bridge';
	const URI = 'https://vk.com/';
	const DESCRIPTION = 'Alternative VK bridge that doesn\'t use VK api.
	                     Extracts text, comments, images, files, videos, posters,
	                     pools, reposts, maps, expanded links and links
	                     to articles from last 10 posts by a group or a user, but not audios.
	                     Caches posts content for one hour.';
	const MAINTAINER = 'd7sd6u';
	const PARAMETERS = array(
		array(
			'u' => array( // this parameter name is called "u" for compatibility with old VkBridge urls
				'name' => 'Group or user id',
				'required' => true,
				'title' => 'User or group ID is a string after the VK domain in a direct link
to this user or group. For example, if the link is https://vk.com/durov, then the ID is "durov" (without quotes).',
				'exampleValue' => 'durov'
			), // again, this parameter and last option are named this way for compatibility
			'hide_reposts' => array(
				'name' => 'Remove reposts',
				'title' => 'Check "Repost-only", if you don\'t want repost-only posts,
or check "All", if you don\'t want posts with reposts at all.',
				'type' => 'list',
				'values' => array(
					'None' => 'none',
					'Repost-only' => 'only',
					'All' => 'on'
				)
			),
			'dontConvertEmoji' => array(
				'name' => 'Don\'t convert emojis',
				'title' => 'Check this if you don\'t want to convert vk\'s own emojis to unicode emojis.',
				'type' => 'checkbox'
			),
			'inlineComments' => array(
				'name' => 'Inline comments',
				'title' => 'Check this, if you want to inline comments from posts.',
				'type' => 'checkbox'
			),
			'topCommentThreshold' => array(
				'name' => 'Top comment threshold',
				'title' => 'Remove branches with root comments with less then this number of likes.',
				'defaultValue' => 0,
				'type' => 'number'
			),
			'branchCommentThreshold' => array(
				'name' => 'Branch threshold',
				'title' => 'Remove branches that have no comments that have more than this number of likes. Usually all you need is
to set this parameter and "Descending comment threshold" parameter to one value to your liking.',
				'defaultValue' => 0,
				'type' => 'number'
			),
			'descendingCommentThreshold' => array(
				'name' => 'Descending comment threshold',
				'title' => 'Remove descending comments with less than this number of likes.',
				'defaultValue' => 0,
				'type' => 'number'
			),
			'hardDescendingCommentThreshold' => array(
				'name' => 'Hard descending comment threshold',
				'title' => 'Check this, if you don\'t want to keep all comments between valid descending comments.
Checking this may break flow of the conversation in comments.',
				'type' => 'checkbox',
			),
			'descendingCommentThresholdOffset' => array(
				'name' => 'Descending comment threshold offset',
				'title' => 'Show this number of comments after last valid descending comment.',
				'defaultValue' => 0,
				'type' => 'number'
			),
			'dontAddDeletedAmount' => array(
				'name' => 'Don\'t add placeholders with amount of deleted comments',
				'title' => 'Check this to not display placeholders with the number
of deleted comments in the place of those comments.',
				'type' => 'checkbox',
			)
		)
	);
	const CACHE_TIMEOUT = 600;
	const POST_CACHE_TIMEOUT = 3600;

	private $preDownloaded = array();

	private $currentPostId; // for debug purposes only
	private $currentCommentId; // for debug purposes only

	private $totalExecutionTime; // for debug purposes only
	private $preDownloadExecutionTime; // for debug purposes only
	private $postsExtractingTime = array(); // for debug purposes only

	public function getName(){
		$sourceId = $this->getInput('u');

		if($sourceId != '') {
			$sourceDom = $this->getDom('https://vk.com/' . $sourceId, 86400, array('Accept-language: en')); // max 24 hours timeout

			$this->assertc($this->has($sourceDom, '.page_name'), 'getName() failed to extract page name');

			$nameRaw = $sourceDom->find('.page_name')[0]->plaintext;

			return html_entity_decode($nameRaw, ENT_QUOTES | ENT_HTML5);
		} else {
			return self::NAME;
		}
	}

	public function getURI(){
		$sourceId = $this->getInput('u');
		if($sourceId != '') {
			return 'https://vk.com/' . $sourceId;
		} else {
			return self::URI;
		}
	}

	public function collectData() {
		$this->totalExecutionTime = -microtime(true);

		$sourceId = $this->getInput('u');

		foreach($this->getPosts($sourceId) as $post) {
			$item = $this->getItem($post);
			if($item !== 'Removed') {
				$this->items[] = $item;
			}
		}

		$this->totalExecutionTime += microtime(true);

		if(Debug::isEnabled()) {
			$this->showExecutionTimes();
		}
	}

	private function getPosts($sourceId) {
		$posts = array();

		$sourceDom = $this->getDom('https://vk.com/' . $sourceId);

		$this->preDownloadExecutionTime = -microtime(true);

		$this->preDownloadSourceUrls($sourceDom, 0, array('Accept-language: en'));

		$this->preDownloadExecutionTime += microtime(true);

		$this->assertc($this->has($sourceDom, '.post'),
			'No post elements were found in this source: ' . $sourceId,
			false, true);

		foreach($sourceDom->find('.post') as $postElement) {
			//$postElement = $sourceDom->find('.post')[6]; // DEBUG

			// Id attribute of a post element is: post<post id>
			$postId = substr($postElement->getAttribute('id'), 4);

			$this->assertc(preg_match('/^-?\d+_\d+$/', $postId),
				'Post id attribute is empty or malformed: ' . $sourceId,
				false, true);

			$this->currentPostId = $postId;

			$posts[] = $this->getPost($postId);
		}

		return $posts;
	}

	private function getDom($url, $cacheTimeout = 0, $headers = array()) {
		if(isset($this->preDownloaded[$url])) {
			return $this->preDownloaded[$url];
		} else {
			// Actually getSimpleHTMLDOMCached is more powerful than described in wiki, for example it allows to change header for requests
			// See: https://github.com/RSS-Bridge/rss-bridge/blob/07551815554d506f662b56386284d4cef6ddbd1e/lib/contents.php#L288
			return getSimpleHTMLDOMCached($url, $cacheTimeout, $headers);
		}
	}

	private function preDownloadSourceUrls($sourceDom, $timeout, $header) {
		$urls = array();

		foreach($sourceDom->find('.post') as $postElement) {
			$this->assertc($this->hasAttr($postElement, 'id'), 'preDownloadPages() failed to extract post\'s id');
			$postId = substr($postElement->getAttribute('id'), 4);
			$urls[] = $this->getPostUrl($postId);

			if($this->has($postElement, '.copy_quote')) {
				$this->assertc($this->has($postElement, '.copy_post_date .published_by_date'),
					'preDownloadPages() failed to extract repost url');
				$repostUrl = $postElement->find('.copy_post_date .published_by_date')[0]->getAttribute('href');
				// if not comment was reposted
				if(!preg_match('/wall(-?\d+_\d+).+reply=\d+/', $repostUrl, $matches)) {
					$urls[] = 'https://vk.com/' . $repostUrl;
				} else {
					$urls[] = $matches[1];
				}
			}

			if($this->has($postElement, '.page_media_place')) {
				$urls[] = $this->getPostUrl($postId, true);
			}

			foreach($postElement->find('.page_post_thumb_video') as $videoElem) {
				$video = array();

				preg_match('!video(-?\d+_\d+)(\?list=\w+)?!', $videoElem->getAttribute('href'), $matches);
				$this->assertc(isset($matches[1]), 'preDownloadPages() failed to extract video id');
				$videoUrl = 'https://m.vk.com/' . $matches[0];
				$urls[] = $videoUrl;
			}
		}

		$this->preDownloadUrls($urls, $timeout, $header);
	}

	private function preDownloadUrls($urls, $timeout = 0, $header = array()) {
		foreach($urls as $pos => $url) {
			if(in_array($url, $this->preDownloaded)) {
				unset($urls[$pos]);
			}
		}

		$handles = array();

		$multiHandle = curl_multi_init();

		foreach($urls as $url) {
			$handle = curl_init($url);

			curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($handle, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($handle, CURLOPT_USERAGENT, ini_get('user_agent'));
			curl_setopt($handle, CURLOPT_ENCODING, '');
			curl_setopt($handle, CURLOPT_PROTOCOLS, CURLPROTO_HTTP | CURLPROTO_HTTPS);

			if(is_array($header) && count($header) !== 0) {
				curl_setopt($handle, CURLOPT_HTTPHEADER, $header);
			}

			if(defined('PROXY_URL') && !defined('NOPROXY')) {
				curl_setopt($handle, CURLOPT_PROXY, PROXY_URL);
			}

			$handles[$url] = $handle;
			curl_multi_add_handle($multiHandle, $handle);
		}

		do {
			$status = curl_multi_exec($multiHandle, $active);
			if ($active) {
				curl_multi_select($multiHandle);
			}
		} while ($active && $status == CURLM_OK);

		foreach($handles as $url => $handle) {
			$rawPage = curl_multi_getcontent($handle);
			$this->preDownloaded[$url] = str_get_html($rawPage);
			curl_multi_remove_handle($multiHandle, $handle);
		}

		curl_multi_close($multiHandle);
	}

	private function getPost($postId) {
		$postDownloadingTime = -microtime(true);

		$postDom = $this->getPostDom($postId);

		$postDownloadingTime += microtime(true);
		$postParsingTime = -microtime(true);

		$this->assertc($this->has($postDom, '.wall_post_cont'), 'getPost() failed to find .wall_post_cont element');
		$body = $postDom->find('.wall_post_cont')[0];

		$post = array();

		$post['author'] = $this->extractAuthor($postDom);

		$post['origin'] = $this->extractOrigin($postDom);

		$this->assertc($this->has($postDom, '.post_header .rel_date'),
			'getPost() failed to find .rel_date element for cutting context for timestamp extracting');
		$post['timestamp'] = $this->extractTimestamp($postDom->find('.post_header .rel_date')[0]);

		$this->assertc($this->has($postDom, '.post_header .author'),
			'getPost() failed to find .post_header .author element for use in source extracting');
		$post['source'] = $postDom->find('.post_author .author')[0]->plaintext;

		$post['tags'] = $this->extractTags($body);
		$post['text'] = $this->extractPostText($body);

		$post['images'] = $this->extractImages($body);
		$post['files'] = $this->extractFiles($body);
		$post['audios'] = $this->extractAudios($body);
		$post['videos'] = $this->extractVideos($body);

		$post['map'] = $this->extractMap($body, $postId);
		$post['pool'] = $this->extractPool($body);
		$post['poster'] = $this->extractPoster($body);
		$post['article'] = $this->extractArticle($body);
		$post['expandedLink'] = $this->extractExpandedLink($body);

		$post['id'] = $postId;
		$post['url'] = $this->getPostUrl($postId);

		$post['comments'] = $this->extractComments($postDom);

		if($this->has($postDom, '.copy_quote')) {
			$repostId = $postDom->find('.copy_author')[0]->getAttribute('data-post-id');
			$repostUrl = $postDom->find('.copy_post_date .published_by_date')[0]->getAttribute('href');
			// if repost is comment
			if(preg_match('/wall(-?\d+_\d+).+reply=\d+/', $repostUrl, $matches)) {
				$repostDom = $this->getPostDom($matches[1]);

				$repostElem = $postDom->find('.copy_quote')[0];

				$post['repost'] = $this->extractRepostedComment($repostElem);
			} else {
				$post['repost'] = $this->getPost($repostId);
			}
		}

		$postParsingTime += microtime(true);
		$this->postsExtractingTime[$postId] = array(
			'parsing' => $postParsingTime,
			'downloading' => $postDownloadingTime
		);

		return $post;
	}

	private function extractOrigin($postElem) {
		$origin = array();
		if($this->has($postElem, '.Post__copyrightLink')) {
			$originElem = $postElem->find('.Post__copyrightLink')[0];

			preg_match('/^Source: (.*)$/', $originElem->plaintext, $matches);
			$this->assertc(isset($matches[1]), 'extractOrigin() failed to extract post\'s origin');
			$origin['name'] = $matches[1];

			$this->assertc($this->hasAttr($originElem, 'href'), 'extractOrigin() failed to extract origin\'s link');
			$origin['link'] = $originElem->getAttribute('href');

			return $origin;
		}
	}

	private function extractRepostedComment($repostElem) {
		$repost = array();

		$this->assertc($this->has($repostElem, '.copy_author'),
			'extractRepostedComment() failed to extract comment author');
		$repost['source'] = $repost['author'] = $repostElem->find('.copy_author')[0]->plaintext;

		$this->assertc($this->has($repostElem, '.copy_post_date .published_by_date'),
			'extractRepostedComment() failed to extract comment timestamp');
		$repost['timestamp'] = $this->extractTimestamp($repostElem->find('.copy_post_date .published_by_date')[0]);

		$repost['text'] = $this->extractPostText($repostElem);

		$this->assertc($this->hasAttr($repostElem, 'href', '.copy_post_date .published_by_date'),
			'extractRepostedComment() failed to extract comment url');
		$repost['url'] = $repostElem->find('.copy_post_date .published_by_date')[0]->getAttribute('href');

		if(preg_match('/wall(-?\d+_\d+)/', $repost['url'], $matches)) {
			$repost['id'] = $matches[1];
		} else {
			$this->assertc(false, 'extractRepostedComment() failed to extract comment id');
		}

		$repost['images'] = $this->extractImages($repostElem);
		$repost['files'] = $this->extractFiles($repostElem);
		$repost['audios'] = $this->extractAudios($repostElem);
		$repost['videos'] = $this->extractVideos($repostElem);

		$repost['comments'] = $repost['tags'] = $repost['pool'] = $repost['poster'] = array();
		$repost['article'] = $repost['expandedLink'] = $repost['map'] = $repost['map'] = array();

		return $repost;
	}

	private function formatCommentAsPost($comment) {
		$repost['id'] = $comment['id'];
		$repost['url'] = $comment['url'];
		$repost['timestamp'] = $comment['timestamp'];
		$repost['source'] = $repost['author'] = $comment['author']['name'];

		$repost['text']['html'] = $comment['text'];

		$repost['images'] = $comment['images'];
		$repost['videos'] = $comment['videos'];
		$repost['files'] = $comment['files'];
		$repost['audios'] = $comment['audios'];

		$repost['comments'] = $repost['tags'] = $repost['pool'] = $repost['poster'] = array();
		$repost['article'] = $repost['expandedLink'] = $repost['map'] = $repost['map'] = array();

		return $repost;
	}

	private function getPostDom($postId, $isMobile = false) {
			$dom = $this->getDom($this->getPostUrl($postId, $isMobile),
				self::POST_CACHE_TIMEOUT,
				array('Accept-language: en'));
			$dom = $this->cleanRedirects($dom);
			$dom = defaultLinkTo($dom, $this->getURI());

			return $dom;
	}

	private function getPostUrl($postId, $isMobile = false) {
		if($isMobile) {
			return 'https://m.vk.com/wall' . $postId;
		} else {
			return 'https://vk.com/wall' . $postId;
		}
	}

	private function cleanRedirects($dom) {
		foreach($dom->find('.wall_post_cont a') as $link) {
			// check if url in link is redirect, i.e. /away.php?to=<canonical url>&<some vk's parameters>
			// first subexpression is canonical url: all chars after "/away.php?to=", except other possible parameters in "dirty" url
			if(preg_match('#^/away.php\?to=(.*?)(&.*)*$#', $link->getAttribute('href'), $matches)) {
				$clean_url = $matches[1];
				$link->setAttribute('href', urldecode($clean_url));
			}
		}
		return $dom;
	}

	private function extractAuthor($post) {
		if(!$this->has($post, '.wall_post_cont .wall_signed_by')) {
			// if author is not specified, then use group name
			$this->assertc($this->has($post, '.post_author .author'), 'extractAuthor() failed to find .author element');
			return $post->find('.post_author .author')[0]->plaintext;
		} else {
			return $post->find('.wall_post_cont .wall_signed_by')[0]->plaintext;
		}
	}

	private function extractTimestamp($stampElem) {
		// if post is less then few hours old, then .rel-date element has "time" attribute, which contains valid Unix timestamp:
		if($stampElem->getAttribute('time') != '') {
			return $stampElem->getAttribute('time');
		} else {
			// clean timestamp from all strange chars
			$stampRaw = preg_replace('/[^\w:]/u', ' ', $stampElem->plaintext);
			// post timestamp is in one of the following formats:
			// "today<or yesterday> at <time(with meridian)>", "<day of month> <short month name> at <time>", "<day of month> <short month name> <year>", "<day of month> <short month name>, <year> at <time>"
			// but strtotime() understands those:
			// "today<or yesterday> <time(with meridian)>", "<time> <day of month> <short month name>", "<day of month> <short month name> <year>", "<time> <day of month> <short month name>, <year>"
			// ...therefore transforming timestamp to satisfy:
			$stampRaw = preg_replace('/ at/u', '', $stampRaw);
			// finds time, prepends it to the timestamp and removes it from the end, if timestamp is not in "today|yesterday <time>" format
			if(preg_match('/\d?\d:\d\d (am|pm)/u', $stampRaw, $matches) && !preg_match('/(today|yesterday)/u', $stampRaw)) {
				$stampRaw = $matches[0] . ' ' . preg_replace('/\d?\d:\d\d (am|pm)/u', '', $stampRaw);
			}

			$this->assertc(strtotime($stampRaw) !== false,
				'Incorrectly parsed time: "' . $stampElem->plaintext . '" in timestamp: "' . $stampRaw . '"');
			return strtotime($stampRaw);
		}
	}

	private function extractComments($post) {
		$comments = array();
		$this->assertc($this->has($post, '.replies_list'), 'extractComments() failed to find .replies_list');
		$repliesElem = $post->find('.replies_list')[0];

		$this->assertc(!empty($repliesElem->children()), 'In extractComments() $repliesElem doesn\'t have childrens', true);
		foreach($repliesElem->children() as $listElem) {
			// if it is branch root
			if(strpos($listElem->getAttribute('class'), 'reply') !== false) {
				if(isset($comment)) {
					$comments[] = $comment;
				}
				$comment = $this->extractComment($listElem);
				$comment['replies'] = array();
			// else this is branch remainder
			} elseif(strpos($listElem->getAttribute('class'), 'replies_wrap_deep') !== false) {
				$this->assertc($this->has($listElem, '.reply'),
					'In extractComments() reply branch wrapper doesn\'t have childrens');
				foreach($listElem->find('.reply') as $commentElem) {
					$comment['replies'][] = $this->extractComment($commentElem);
				}
			}
		}
		if(isset($comment)) {
			$comments[] = $comment; // force push last comment branch, since there is no next branch root during processing of which this branch will be added
		}
		return $comments;
	}

	private function extractComment($commentElem) {
		$comment = array();
		// if comment was deleted
		$this->assertc($this->has($commentElem, '.reply_text'), 'extractComment() failed to find .reply_text');
		$body = $commentElem->find('.reply_text')[0];

		$this->currentCommentId = $this->extractCommentId($commentElem);

		if(!$this->has($commentElem, '.reply_text > div')) {
			$comment['author'] = array(
				'name' => 'Comment deleted by author or moderator',
				'link' => '',
				'avatar' => 'https://vk.com/images/wall/deleted_avatar_50.png'
			);
			$comment['text'] = '';
			$comment['likes'] = 0;
			$comment['videos'] = $comment['files'] = $comment['audios'] = $comment['images'] = array();
		} else {
			$this->assertc($this->has($commentElem, '.author'),
				'extractComment() failed to find .author for author name extracting');
			$this->assertc($this->has($commentElem, '.reply_image'),
				'extractComment() failed to find .reply_image for author link extracting');
			$this->assertc($this->has($commentElem, '.reply_img'),
				'extractComment() failed to find .reply_img for author avatar extracting');
			$comment['author'] = array(
				'name' => $commentElem->find('.author')[0]->plaintext,
				'link' => $commentElem->find('.reply_image')[0]->getAttribute('href'),
				'avatar' => $commentElem->find('.reply_img')[0]->getAttribute('src')
			);

			$comment['text'] = $this->extractCommentText($body);

			$this->assertc($this->has($commentElem, '.like_button_count'),
				'extractComment() failed to find .like_button_count for likes extracting');
			$likes = $commentElem->find('.like_button_count')[0]->plaintext;
			$comment['likes'] = empty($likes) ? 0 : $likes;

			$comment['videos'] = $this->extractVideos($body);
			$comment['images'] = $this->extractImages($body);
			$comment['files'] = $this->extractFiles($body);
			$comment['audios'] = $this->extractAudios($body);
		}
		$this->assertc($this->has($commentElem, '.rel_date'),
			'extractComment() failed to find .rel_date for timestamp extracting');
		$comment['timestamp'] = $this->extractTimestamp($commentElem->find('.rel_date')[0]);

		$comment['id'] = $this->extractCommentId($commentElem);
		// if this comment is reply to other comment in it's branch
		$comment['replyId'] = $this->extractReplyId($commentElem);
		$comment['url'] = $this->getCommentUrl($comment['id']);

		$this->currentCommentId = null;

		return $comment;
	}

	private function getCommentUrl($id) {
		return 'https://vk.com/wall' . $id;
	}

	private function extractCommentId($body) {
		preg_match('/post(-?\d+_\d+)/', $body->getAttribute('id'), $matches);
		$this->assertc(isset($matches[1]), 'extractCommentId() failed to extract comment id');
		return $matches[1];
	}

	private function extractCommentText($body) {
		$text = '';
		if($this->has($body, '.wall_reply_text')) {
			if(!$this->getInput('dontConvertEmoji')) {
				$this->cleanEmojis($body->find('.wall_reply_text')[0]);
			}
			$text = $body->find('.wall_reply_text')[0]->outertext;
			// innertext doesn't work here :/, seems like bug in in simple_html_dom, maybe caused by encoding or cyrillic characters, so using this hack instead:
			$text = preg_replace('!</?div.*?>!', '', $text);
		}
		return $text;
	}

	private function extractReplyId($comment) {
		if($this->has($comment, '.reply_to')) {
			preg_match('/return wall\.showReply\(this, \'-?\d+_\d+\', \'(-?\d+_\d+)\'/',
				$comment->find('.reply_to')[0]->getAttribute('onclick'),
				$matches);
			$id = $matches[1];
		// else this comment is...
		} else {
			preg_match('/wall(-?\d+)_\d+\?reply=(\d+)(&thread=(\d+))?/',
				$comment->find('.wd_lnk')[0]->getAttribute('href'), $matches);
			if(isset($matches[4])) {
				$id = $matches[1] . $matches[4]; // reply to a branch root
			} else {
				$id = $matches[1] . $matches[2]; // reply to post itself
			}
		}
		$this->assertc(isset($id), 'Comment reply id extraction failed');

		return $id;
	}

	private function extractTags($post) {
		$categories = array();
		$links = $post->find('.wall_post_text > a');
		foreach($links as $link) {
			// if link is valid tag: #<tag's word chars(classic word char and all unicode letters)>@<optional group name's chars>
			if(preg_match('/^#([\w\pL]+)(@[\w\pL]+)?$/u', $link->plaintext, $matches)) {
				$categories[] = $matches[1]; // then take real tag
			}
		}
		return $categories;
	}

	private function extractPostText($body) {
		$text = array(
			'plaintext' => '',
			'html' => ''
		);

		if($this->has($body, '.wall_post_text')) {
			$text = $this->extractText($body->find('.wall_post_text')[0]);
		}

		return $text;
	}

	private function extractText($textElem) {
		$text = array(
			'plaintext' => '',
			'html' => ''
		);

		if(!$this->getInput('dontConvertEmoji')) {
			$this->cleanEmojis($textElem);
		}
		// innertext doesn't work here :/, seems like bug in simple_html_dom, maybe caused by encoding or cyrillic characters, so using this hack instead:
		$text['html'] = $textElem->outertext;
		$text['html'] = preg_replace('!</?div.*?>!', '', $text['html']);

		$this->cleanEmojis($textElem);

		$plaintext = $textElem->outertext;
		$plaintext = preg_replace('!</?div.*?>!', '', $plaintext);
		$plaintext = preg_replace('!</?a.*?>!', '', $plaintext);
		$plaintext = html_entity_decode($plaintext, ENT_QUOTES | ENT_HTML5);
		$text['plaintext'] = preg_replace('/<br\/?>/u', "\n", $plaintext);

		return $text;
	}

	private function cleanEmojis($body) {
		foreach($body->find('img.emoji') as $emojiElem) {
			$this->assertc(!empty($emojiElem->getAttribute('alt')), 'extractContent() failed to extract emojis');
			$emoji = $emojiElem->getAttribute('alt');
			$emojiElem->outertext = $emoji;
		}
	}

	private function extractImages($body) {
		$images = array();

		foreach($body->find('.image_cover') as $imageElem) {
			if(strpos($imageElem->getAttribute('class'), 'page_post_thumb_video') === false) {
				// onclick attribute contains urls to image in diffrent resolutions, from worst to best in format:
				// https:\/\/sun<some number>-<some number>.userapi.com\/<some word chars>\/<some word chars>.<some image format, i.e. more than one word char>
				$this->assertc($this->hasAttr($imageElem, 'onclick'),
					'In extractImages() $imageElem\'s "onclick" attribute is empty or doesn\'t exist');
				$images[] = $this->extractImage($imageElem->getAttribute('onclick'));
			}
		}
		// matches elements with Page.showGif onclick handler (gif containers in posts with single gif)
		// ...and elements with Page.showGifBox onclick handler (gif containers in posts with other media content)

		foreach($body->find('a[onclick*=showGif]') as $gifElem) {
			$image = array();
			$this->assertc($this->hasAttr($gifElem, 'href'),
				'In extractImages() $gifElem\'s "href" attribute is empty or doesn\'t exist');
			$image['thumb'] = $image['original'] = $this->getFileByUrl($gifElem->getAttribute('href'));
			$images[] = $image;
		}

		foreach($body->find('a[href*=doc]') as $bigImageElem) {
			// images imported as files have empty 'onclick' attribute
			if($bigImageElem->getAttribute('onclick') == ' ') {
				$image = array();
				$image['thumb'] = $this->extractBackgroundImage($bigImageElem);
				$this->assertc($this->hasAttr($bigImageElem, 'href'),
					'In extractImages() $bigImageElem\'s "href" attribute is empty or doesn\'t exist');
				$image['original'] = $this->getFileByUrl($bigImageElem->getAttribute('href'));
				$images[] = $image;
			}
		}

		// comment has no more than one sticker
		foreach($body->find('.sticker_img') as $stickerElem) {
			$sticker = $stickerElem->getAttribute('src');
			$images[] = array(
				'thumb' => $sticker,
				'original' => $sticker
			);
		}

		return $images;
	}

	private function extractImage($attr) {
		$image = array();

		if(!preg_match('/sign=|quality=|size=/', $attr)) {
			$extension = '.jpg';
		} else {
			$extension = '';
		}

		preg_match('/return showPhoto\(.+?({.*})/', $attr, $matches);
		$this->assertc(isset($matches[1]), 'extractImage() failed to extract data json');
		$arg = htmlspecialchars_decode($matches[1]);
		$data = json_decode($arg, true)['temp'];

		$this->assertc(!empty($data['x']), 'In extractImage() data["x"] is empty');
		// by default expect image small enough that it does not have separate thumbnail
		$image['original'] = $image['thumb'] = $data['base'] . $data['x'];
		// ...but if that's not the case
		foreach(array('y_', 'z_', 'w_') as $key) {
			if (!isset($data[$key])) continue;
			if (!isset($data[$key][0])) continue;
			if (substr($data[$key][0], 0, 4) === 'http') {
				$base = '';
			} else {
				$base = $data['base'];
			}
			$image['original'] = $base . $data[$key][0] . $extension;
		}
		return $image;
	}

	private function extractBackgroundImage($elem) {
		preg_match('/background(-image)?: url\((.+?)\)/', $elem->getAttribute('style'), $matches);
		$this->assertc(isset($matches[2]), 'extractBackgroundImage() failed to extract image from element"');
		return $matches[2];
	}

	private function getFileByUrl($nativeFileUrl) {
		preg_match('/doc(-?\d+_-?\d+)(\?.*)?/', $nativeFileUrl, $matches);
		$this->assertc(isset($matches[1]), 'getFileByUrl() failed to extract file from url');
		return $this->getFile($matches[1]);
	}

	private function getFile($fileId) {
		$fileUrl = 'https://m.vk.com/doc' . $fileId; // mobile version always redirects to direct url, so that is good enough for now
		return $fileUrl;
	}

	private function extractFiles($body) {
		$files = array();
		foreach($body->find('a.page_doc_title') as $fileElem) {
			$file = array();
			$this->assertc(!empty($fileElem->plaintext), 'extractFiles() failed to extract file name');
			$file['title'] = $fileElem->plaintext;
			$file['url'] = $this->getFileByUrl($fileElem->getAttribute('href'));
			$files[] = $file;
		}
		return $files;
	}

	private function extractAudios($body) {
		$audios = array();
		foreach($body->find('.audio_row') as $audioElem) {
			$audio = array();
			$this->assertc($this->check($audioElem, '.audio_row__performer_title a'),
				'extractAudios() failed to find ".audio_row__performer_title a"');
			$audio['title'] = $audioElem->find('.audio_row__performer_title a')[0]->plaintext;
			// TODO: implement
			$audio['url'] = '';
			//$audio['url'] = $this->getFileByUrl($audioElem->getAttribute('href'));
			$audios[] = $audio;
		}
		return $audios;
	}

	private function decryptDirectAudioUrl($brokenUrl) {

		$fixedUrl = s($brokenUrl);
		return $fixedUrl;
	}

	private function extractVideos($body) {
		$videos = array();

		foreach($body->find('.page_post_thumb_video') as $videoElem) {
			$video = array();

			preg_match('!video(-?\d+_\d+)(\?list=\w+)?!', $videoElem->getAttribute('href'), $matches);
			$this->assertc(isset($matches[1]), 'extractVideos() failed to extract video id');
			$video['id'] = $matches[1];
			$video['nativeUrl'] = 'https://m.vk.com/' . $matches[0];

			$this->assertc(preg_match('/background(-image)?: url\(.+?\)/', $videoElem->getAttribute('style')),
				'extractVideos() failed to extract video preview', true);
			if(preg_match('/background(-image)?: url\(.+?\)/', $videoElem->getAttribute('style'))) {
				$video['image'] = $this->extractBackgroundImage($videoElem);
			}

			// TODO: fix high quality video sources

			// use url with "list" parameter which sometimes allows to access private videos
			$videoDom = $this->getDom($video['nativeUrl'], self::POST_CACHE_TIMEOUT, array('Accept-language: en'));

			$video['urls'] = array();

			if($this->has($videoDom, '.VideoPage__video iframe')) {
				$this->assertc($this->hasAttr($videoDom, 'src', '.VideoPage__video iframe'),
					'In extractVideos() iframe\'s "src" attribute is empty or doesn\'t exist', true);
				// TODO: optionally convert youtube links to invidious (or similar project's) links
				$video['iframe'] = 'https:' . $videoDom->find('.VideoPage__video iframe')[0]->getAttribute('src');
			} elseif($this->has($videoDom, 'source')) {
				foreach($videoDom->find('source') as $source) {
					$this->assertc($this->hasAttr($source, 'src'),
						'In extractVideos() source\'s "src" attribute is empty or doesn\'t exist', true);
					if($this->hasAttr($source, 'src')) {
						$video['urls'][] = $source->getAttribute('src');
					}
				}
				$this->assertc(count($video['urls']) > 1,
					'In extractVideos() $video["urls"] only has one url (a broken .m3u8)', true);
			} else {
				$this->assertc(false, 'In extractVideos() no iframe or source element was found', true);
			}
			// using aria-label allows extracting title even for private videos
			preg_match('/(.*) is/u', $videoElem->getAttribute('aria-label'), $matches);
			$this->assertc(isset($matches[1]), 'extractVideos() failed to extract video title');
			$video['title'] = $matches[1];

			$videos[] = $video;
		}
		return $videos;
	}

	private function extractMap($body, $postId) {
		$map = array();
		if($this->has($body, '.page_media_place')) {
			// if post has other media other than map, than map will be hidden, but on mobile version that is not the case
			// ...also desktop version doesn't have link to map, therefore extracting map from mobile version
			$mobileDom = $this->getPostDom($postId, true);

			$this->assertc($this->has($mobileDom, '.medias_map_first_line'),
				'extractMap() failed to find first part of map title element');
			$this->assertc($this->has($mobileDom, '.medias_map_second_line'),
				'extractMap() failed to find second part of map title element');
			$firstLine = $mobileDom->find('.medias_map_first_line')[0]->plaintext;
			$secondLine = $mobileDom->find('.medias_map_second_line')[0]->plaintext;

			$map['text'] = (empty($firstLine) ? 'Unknown' : $firstLine) . ', ' . (empty($secondLine) ? 'Unknown' : $secondLine);

			$this->assertc($this->has($mobileDom, '.medias_map_second_line'), 'extractMap() failed to find .medias_map_img');
			$imageElem = $mobileDom->find('.medias_map_img')[0];
			$rawImage = 'https:' . $this->extractBackgroundImage($imageElem);
			$rawImage = preg_replace('/&key=.*$/', '', $rawImage); // map provider refuses to give image by url with "key" parameter
			$map['image'] = preg_replace('/&size=\d+,\d+/', '&size=450,450', $rawImage); // take square image of better resolution

			$this->assertc($this->has($mobileDom, '.medias_map_fill'), 'extractMap() failed to find .medias_map_fill');
			$this->assertc($this->hasAttr($mobileDom, 'href', '.medias_map_fill'),
				'In extractMap() "href" attribute of .medias_map_fill element is empty or doesn\'t exist');
			$map['url'] = $mobileDom->find('.medias_map_fill')[0]->getAttribute('href');
		}
		return $map;
	}

	private function extractPool($body) {
		$pool = array();
		if($this->has($body, '.post_media_voting')) {
			$this->assertc($this->check($body, '.media_voting_question'),
				'extractPool() failed to extract .media_voting_question');
			$pool['title'] = $body->find('.media_voting_question')[0]->plaintext;
			$this->assertc($this->check($body, '.media_voting_author'),
				'extractPool() failed to extract .media_voting_author');
			$pool['author'] = $body->find('.media_voting_author')[0]->plaintext;
			$this->assertc($this->check($body, '.media_voting_subtitle'),
				'extractPool() failed to extract .media_voting_subtitle');
			$pool['type'] = $body->find('.media_voting_subtitle')[0]->plaintext;
			$pool['options'] = array();
			$this->assertc($this->has($body, '.media_voting_option_text'),
				'extractPool() failed to find .media_voting_option_text');
			foreach($body->find('.media_voting_option_text') as $optionElem) {
				// again innertext is buggy and removes all cyrillic chars from innertext, so using outertext
				preg_match('/>(.*?)</u', $optionElem->outertext, $matches);
				$this->assertc(isset($matches[1]), 'extractPool() failed to extract pool option');
				$pool['options'][] = $matches[1];
			}
			$this->assertc($this->has($body, '._media_voting_footer_voted_text b'),
				'extractPool() failed to find ._media_voting_footer_voted_text b', true);
			if($this->has($body, '._media_voting_footer_voted_text b')) {
				$this->assertc($this->check($body, '._media_voting_footer_voted_text b'),
					'extractAudios() failed to extract pool votes amount');
				$pool['total'] = $body->find('._media_voting_footer_voted_text b')[0]->plaintext;
			} else {
				$pool['total'] = 0;
			}
		}
		return $pool;
	}

	private function extractPoster($body) {
		$poster = array();
		if($this->has($body, '.poster')) {
			$this->assertc($this->check($body, '.poster__text'), 'extractPoster() failed to extract .poster__text');
			$poster['text'] = $body->find('.poster__text')[0]->plaintext;
			$this->assertc($this->has($body, '.poster__image'), 'extractPoster() failed to find .poster__image');
			$poster['image'] = $this->extractBackgroundImage($body->find('.poster__image')[0]);
		}
		return $poster;
	}

	private function extractArticle($body) {
		$article = array();
		if($this->has($body, '.article_snippet')) {
			$this->assertc($this->check($body, '.article_snippet__title'),
				'extractArticle() failed to extract .article_snippet__title');
			$article['title'] = $body->find('.article_snippet__title')[0]->plaintext;
			$this->assertc($this->check($body, '.article_snippet__author'),
				'extractArticle() failed to extract .article_snippet__author');
			$article['author'] = $body->find('.article_snippet__author')[0]->plaintext;
			$this->assertc($this->has($body, '.article_snippet'),
				'extractArticle() failed to find .article_snippet');
			$this->assertc($this->hasAttr($body, 'href', '.article_snippet'),
				'In extractArticle() "href" attribute of .article_snippet is empty or doesn\'t exist');
			$article['url'] = $body->find('.article_snippet')[0]->getAttribute('href');
			$this->assertc($this->has($body, '.article_snippet__image'),
				'extractArticle() failed to find .article_snippet__image');
			$article['image'] = $this->extractBackgroundImage($body->find('.article_snippet__image')[0]);
		}
		return $article;
	}

	private function extractExpandedLink($body) {
		$link = array();

		if($this->has($body, '.thumbed_link')) {
			$this->assertc($this->check($body, '.thumbed_link__title'),
				'extractExpandedLink() failed to extract .thumbed_link__title');
			$link['title'] = $body->find('.thumbed_link__title')[0]->plaintext;
			$this->assertc($this->hasAttr($body, 'href', '.thumbed_link__title'),
				'In extractArticle() "href" attribute of .thumbed_link__title is empty or doesn\'t exist');
			$link['url'] = $body->find('.thumbed_link__title')[0]->getAttribute('href');
			$this->assertc($this->has($body, '.thumbed_link__thumb'),
				'extractExpandedLink() failed to find .thumbed_link__thumb');
			$link['image'] = $this->extractBackgroundImage($body->find('.thumbed_link__thumb')[0]);
		} elseif($this->has($body, '.media_link')) {
			$this->assertc($this->check($body, '.media_link__title'),
				'extractExpandedLink() failed to extract .media_link__title');
			$link['title'] = $body->find('.media_link__title')[0]->plaintext;
			$this->assertc($this->has($body, '.media_link__media'),
				'extractExpandedLink() failed to find .media_link__media');
			$this->assertc($this->hasAttr($body, 'href', '.media_link__media'),
				'In extractArticle() "href" attribute of .media_link__media is empty or doesn\'t exist');
			$link['url'] = $body->find('.media_link__media')[0]->getAttribute('href');
			$this->assertc($this->has($body, '.media_link__photo'),
				'extractExpandedLink() failed to find .media_link__photo');
			$this->assertc($this->hasAttr($body, 'src', '.media_link__photo'),
				'In extractArticle() "src" attribute of .media_link__photo is empty or doesn\'t exist');
			$link['image'] = $body->find('.media_link__photo')[0]->getAttribute('src');
		}

		return $link;
	}

	private function getItem($post) {
		$itemFormattingTime = -microtime(true);

		$item = array();

		if(isset($post['repost'])) {
			$type = $this->getInput('hide_reposts');
			if($type === 'on' || $type === 'only' && $this->postIsEmpty($post)) {
				$itemFormattingTime += microtime(true); // DEBUG
				$this->postsExtractingTime[$post['id']]['formatting'] = $itemFormattingTime;
				return 'Removed';
			}
		}

		$item['uri'] = $post['url'];
		$item['title'] = $this->formatTitle($post);
		// prepend '@' to Unix timestamp so that strtotime() can use it, because item[timestamp] must be valid strtotime() input
		// see https://github.com/RSS-Bridge/rss-bridge/wiki/The-collectData-function#item-parameters
		$item['timestamp'] = '@' . $post['timestamp'];
		$item['author'] = $post['author'];
		$item['content'] = $this->formatPost($post);
		$item['enclosures'] = $this->filterMedia($post);
		$item['categories'] = $post['tags'];
		$item['uid'] = $post['id'];

		$itemFormattingTime += microtime(true);
		$this->postsExtractingTime[$post['id']]['formatting'] = $itemFormattingTime;

		return $item;
	}

	private function formatTitle($post) {

		$isEmpty = $this->postIsEmpty($post);
		$hasRepost = isset($post['repost']);
		$hasPool = !empty($post['pool']);
		$hasText = !empty($post['text']['plaintext']);
		$hasArticle = !empty($post['article']);
		$hasPoster = !empty($post['poster']);
		$extras = array(
			array('type' => 'images', 'amount' => count($post['images'])),
			array('type' => 'videos', 'amount' => count($post['videos'])),
			array('type' => 'files', 'amount' => count($post['files'])),
			array('type' => 'audios', 'amount' => count($post['audios'])),
			array('type' => 'expandedLinks', 'amount' => !empty($post['expandedLink'])),
			array('type' => 'map', 'amount' => !empty($post['map'])),
		);

		if($isEmpty && $hasRepost) {
			$title = $post['source'] . ' reposted ' . $post['repost']['source'];
		} elseif($hasPoster) {
			$title = $post['poster']['text'];
		} elseif($hasText) {
			$text = $post['text']['plaintext'];
			$text = preg_replace('/#([\w\pL]+)(@[\w\pL]+)?/u', '', $text); // remove hashtags
			$text = str_replace(array('\r\n', '\n', '\r'), ' ', $text); // remove newlines
			if(mb_strlen($text) > 4) {
				$title = mb_strimwidth($text, 0, 60, '...');
			} else {
				$title = $post['source'] . ' posted short message';
			}
		} elseif($hasArticle) {
			$title = $post['source'] . ' posted article "';
			$title .= $post['article']['title'];
			$title .= '"';
		} elseif($hasPool) {
			$title = $post['source'] . ' posted pool "';
			$title .= $post['pool']['title'];
			$title .= '"';
		} elseif(count($post['videos']) === 1 && count($post['images']) === 0) {
			$title = $post['source'] . ' posted video "';
			$title .= $post['videos'][0]['title'];
			$title .= '"';
		} else {
			$title = $post['source'] . ' posted ';

			// traverse through extras and remove nonexistent ones
			$extrasInitialSize = count($extras);
			for($pos = 0; $pos < $extrasInitialSize; $pos++) {
				// also will remove nonexistent maps and expanded links, which have bool 'false' in 'amount'
				if($extras[$pos]['amount'] == 0) {
					unset($extras[$pos]);
				}
			}
			$extras = array_values($extras);
			if(empty($extras)) {
				$title .= 'unknown message';
				$this->assertc(false, 'formatTitle() encountered unknown payload in post', true);
			} else {
				for($pos = 0; $pos < count($extras); $pos++) {
					switch($extras[$pos]['type']) {
						case 'videos':
							$title .= 'video';
							break;
						case 'images':
							$title .= 'image';
							break;
						case 'files':
							$title .= 'file';
							break;
						case 'audios':
							$title .= 'audio';
							break;
						case 'expandedLinks':
							$title .= 'link';
							break;
						case 'map':
							$title .= 'map';
							break;
					}
					if($extras[$pos]['amount'] > 1) {
						$title .= 's';
					}
					// if not penultimate, than add a comma, else if not the last add 'and'
					if($pos === count($extras) - 2) {
						$title .= ' and ';
					} elseif($pos != count($extras) - 1) {
						$title .= ', ';
					}
				}
			}
		}

		return $title;
	}

	private function formatPost($post) {
		$content = $this->formatPostContent($post);

		if($this->getInput('inlineComments') === true) {
			$content .= $this->formatComments($post['comments'], $post['id']);
		}

		return $content;
	}

	private function formatComments($comments, $postId) {
		$content = '';

		$commentsAmount = count($comments);
		foreach($comments as $comment) {
			if(isset($comment['replies'])) {
				$commentsAmount += count($comment['replies']);
			}
		}

		if($commentsAmount === 0) {
			$content .= '<br/>No comments.';
		} else {
			$content .= "<br/><details><summary>$commentsAmount+ comments:</summary>";
		}

		$firstIteration = true;
		foreach($comments as $comment) {
			$threshold = $this->getInput('descendingCommentThreshold');
			$hard = $this->getInput('hardDescendingCommentThreshold');
			$offset = $this->getInput('descendingCommentThresholdOffset');
			$addAmount = !$this->getInput('dontAddDeletedAmount');
			// if no descending comment was found, then $i <= $lastValidComment should be always false
			// therefore $lastValidComment by default equals to -1
			$lastValidComment = -1;
			$topCommentLikes = $comment['likes'];
			for($i = 0; $i < count($comment['replies']); $i++) {
				if($comment['replies'][$i]['likes'] > $topCommentLikes) {
					$topCommentLikes = $comment['replies'][$i]['likes'];
				}
				if($comment['replies'][$i]['likes'] >= $threshold) {
					$lastValidComment = $i;
				}
			}
			if($comment['likes'] >= $this->getInput('topCommentThreshold')
			&& $topCommentLikes >= $this->getInput('branchCommentThreshold')) {
				if($firstIteration) {
					$firstIteration = false;
				} else {
					$content .= '<hr/>';
				}

				$content .= "<br/><i id='$comment[id]'>Comment: </i><br/>";

				$content .= $this->formatComment($comment, $postId);

				$deletedAmount = 0;
				for($i = 0; $i < count($comment['replies']); $i++) {
					$reply = $comment['replies'][$i];
					if(!$hard && $i <= ($lastValidComment + $offset) || $hard && $reply['likes'] >= $threshold) {
						// add notice about comments filtered after last valid reply
						if($addAmount && $hard && $deletedAmount !== 0) {
							$content .= "<hr/><i>$deletedAmount repl";
							if($deletedAmount > 1) {
								$content .= 'ies';
							} else {
								$content .= 'y';
							}
							$content .= ' was filtered out';
							$deletedAmount = 0;
						}
						$content .= '<hr/>';
						$content .= "<br/><i><a id='$reply[id]' href='#$reply[replyId]'>Reply: </a></i><br/>";
						$content .= $this->formatComment($reply, $postId);
					}
					if($addAmount && $hard && $reply['likes'] < $threshold) {
						$deletedAmount++;
					}
				}
				// add notice about comments filtered after last valid reply
				$deletedAmount = count($comment['replies']) - $lastValidComment - $offset - 1;
				if($addAmount && $deletedAmount >= 1) {
					$content .= "<hr/><i>$deletedAmount repl";
					if($deletedAmount > 1) {
						$content .= 'ies';
					} else {
						$content .= 'y';
					}
					$content .= ' was filtered out';
				}
			} elseif($addAmount) {
				// add notice about filtered branch
				if($firstIteration) {
					$firstIteration = false;
				} else {
					$content .= '<hr/>';
				}

				$deletedAmount = 1 + count($comment['replies']);
				$content .= "<i>Branch with $deletedAmount comment";
				if($deletedAmount > 1) {
					$content .= 's';
				}
				$content .= ' was filtered out</i>';
			}
		}

		$content .= '</details>';

		return $content;
	}

	private function formatComment($comment, $postId) {
		$content = "<i>Author: <a href='{$comment['author']['link']}'>{$comment['author']['name']}</a></i><br/>";
		$content .= '<i>Avatar: </i>';
		$content .= "<a href='{$comment['author']['link']}'>";
		$content .= "<img src='{$comment['author']['avatar']}'/>";
		$content .= '</a><br/><br/>';

		$content .= $comment['text'];

		$content .= $this->formatImages($comment['images']);
		$content .= $this->formatVideos($comment['videos'], $postId);
		$content .= $this->formatAudios($comment['audios']);

		$content .= "<br/><br/><i>Likes: </i>$comment[likes]";

		return $content;
	}

	private function formatPostContent($post) {
		$content = $post['text']['html'];

		$content .= $this->formatImages($post['images']);
		$content .= $this->formatVideos($post['videos'], $post['id']);
		$content .= $this->formatFiles($post['files']);
		$content .= $this->formatAudios($post['audios']);
		$content .= $this->formatPool($post['pool']);
		$content .= $this->formatArticle($post['article']);
		$content .= $this->formatMap($post['map']);
		$content .= $this->formatPoster($post['poster']);
		$content .= $this->formatExpandedLink($post['expandedLink']);

		if(isset($post['repost'])) {
			if(!empty($content)) {
				$content .= '<br/><br/><br/>';
			}
			$content .= '<i>Repost:</i><br/><br/>';
			$content .= "<i>Source: </i><a href='{$post['repost']['url']}'>{$post['repost']['source']}</a><br/>";
			$content .= "<i>Author: </i>{$post['repost']['author']}<br/>";
			$content .= '<i>Timestamp: </i>';
			$content .= strftime('%c', $post['repost']['timestamp']);
			$content .= '<br/><br/>';
			$content .= $this->formatPostContent($post['repost']);
		}

		if(isset($post['origin'])) {
			$content .= '<br/><br/>';
			$content .= "<i>Source: </i><a href='{$post['origin']['link']}'>{$post['origin']['name']}</a>";
		}

		return $content;
	}

	private function formatImages($images) {
		$content = '';
		foreach($images as $image) {
			$content .= "<br/><a href='$image[original]'><img src='$image[thumb]'/></a><br/>";
		}
		return $content;
	}

	private function formatVideos($videos, $postId) {
		$content = '';
		if(!empty($videos)) {
			$content .= '<br/><br/><i>Attached videos:</i><br/><br/>';
			foreach($videos as $video) {
				// TODO: handle blocked videos
				// if successfully extracted video preview, add it to fallback
				if(empty($video['image'])) {
					$videoPreview = $video['title'];
				} else {
					$videoPreview = "<img src='$video[image]'/>";
				}

				if(isset($video['iframe'])) {
					$content .= "<a href='$video[iframe]'>$videoPreview</a>";
				} elseif(!empty($video['urls'])) {
					$content .= '<video controls>';
					foreach($video['urls'] as $source) {
						$content .= "<source src='$source'/>";
					}
					if(count($video['urls']) > 1) {
						$content .= "<a href='{$video['urls'][1]}'>$videoPreview</a>";
					} else {
						// there is no reason to attach broken direct url, so attach native url instead
						$content .= "<a href='$video[nativeUrl]'>$videoPreview</a>";
					}
					$content .= '</video><br/>';
				} else {
					// if no iframe or video was found, then it is most likely private video and only link to video in post will work, not direct one
					$content .= "<a href='https://vk.com/post$postId?z=video$video[id]'>$videoPreview</a>";
				}
			}
		}
		return $content;
	}

	private function formatPool($pool) {
		$content = '';
		if(!empty($pool)) {
			$content .= "<br/><br/><i>Pool: </i>$pool[title]<br/><br/>";
			$content .= "<i>Author: </i>$pool[author]<br/>";
			$content .= "<i>Type: </i>$pool[type]<br/><br/>";
			foreach($pool['options'] as $option) {
				$content .= "<i>Option: </i>$option<br/>";
			}
			$content .= "<br/><i>Total voted: </i>$pool[total]<br/><br/>";
		}
		return $content;
	}

	private function formatFiles($files) {
		$content = '';
		if(!empty($files)) {
			$content .= '<br/><br/><i>Attached files:</i><br/><br/>';
			foreach($files as $file) {
				$content .= "<a href='$file[url]'>$file[title]</a><br/>";
			}
		}
		return $content;
	}

	private function formatAudios($audios) {
		$content = '';
		if(!empty($audios)) {
			$content .= '<br/><br/><i>Attached audio:</i><br/><br/>';
			foreach($audios as $audio) {
				//$content .= '<audio src="' . $audio['url'] . '" controls>';
				$content .= "Audio: <a href='$audio[url]'>$audio[title]</a>";
				//$content .= '</audio>';
			}
		}
		return $content;
	}

	private function formatPoster($poster) {
		$content = '';
		if(!empty($poster)) {
			$content .= '<br/><br/><i>Poster:</i><br/><br/>';
			$content .= $poster['text'];
			$content .= "<img src='$poster[image]'/>";
		}
		return $content;
	}

	private function formatMap($map) {
		$content = '';
		if(!empty($map)) {
			$content .= "<br/><br/><i>Location: </i>$map[text]<br/>";
			$content .= '<i>Map:</i><br/>';
			$content .= "<a href='$map[url]'><img src='$map[image]'/></a>";
		}
		return $content;
	}

	private function formatArticle($article) {
		$content = '';
		if(!empty($article)) {
			$content .= '<br/><br/><i>Article: </i>';
			$content .= "<a href='$article[url]'>$article[title]</a><br/>";
			$content .= "<i>Author: </i>$article[author]<br/>";
			$content .= "<i>Image: </i><br/><a href='$article[url]'>";
			$content .= "<img src='$article[image]'/>";
			$content .= '</a><br/>';
		}
		return $content;
	}

	private function formatExpandedLink($link) {
		$content = '';
		if(!empty($link)) {
			$content .= '<br/><br/><i>Link: </i>';
				$content .= "<a href='$link[url]'>$link[title]</a><br/>";
			$content .= '<i>Image: </i><br/>';
			$content .= "<a href='$link[url]'>";
			$content .= "<img src='$link[image]'/>";
			$content .= '</a><br/>';
		}
		return $content;
	}

	private function filterMedia($post) {
		$enclosures = array();

		foreach($post['images'] as $image) {
			$enclosures[] = $image['original'];
		}

		foreach($post['videos'] as $video) {
			// highest quality video, but not broken .m3u8
			if(isset($video['urls'][1])) {
				$enclosures[] = $video['urls'][1];
			}
		}

		foreach($post['files'] as $file) {
			$enclosures[] = $file['url'];
		}

		foreach($post['audios'] as $audio) {
			$enclosures[] = $audio['url'];
		}

		if(!empty($post['poster'])) {
			$enclosures[] = $post['poster']['image'];
		}

		if(!empty($post['article'])) {
			$enclosures[] = $post['article']['image'];
		}

		if(!empty($post['map'])) {
			$enclosures[] = $post['map']['image'];
		}

		if(!empty($post['expandedLink'])) {
			$enclosures[] = $post['expandedLink']['image'];
		}

		if(isset($post['repost'])) {
			$enclosures = array_merge($enclosures, $this->filterMedia($post['repost']));
		}

		return $enclosures;
	}

	// checks if any descendent of element matches selector
	private function has($elem, $selector) {
		return count($elem->find($selector)) != 0;
	}

	private function postIsEmpty($post) {
		$isEmpty = true;
		$contents = array('content', 'images', 'files', 'audios', 'videos', 'map',
			'pool', 'poster', 'article', 'expandedLinks');
		foreach($contents as $part) {
			$isEmpty = $isEmpty && empty($post[$part]);
		}
		return $isEmpty;
	}

	// checks if any descendent of element matches selector and has not empty plaintext
	private function check($elem, $selector) {
		return $this->has($elem, $selector) && !empty(trim($elem->find($selector)[0]->plaintext));
	}

	private function hasAttr($elem, $attr, $selector = false) {
		if($selector === false) {
			return !empty(trim($elem->getAttribute($attr)));
		} else {
			return !empty(trim($elem->find($selector)[0]->getAttribute($attr)));
		}
	}

	// really basic error handling, may be changed in the future
	private function assertc($condition, $description, $isNotFatal = false, $postContext = true) {
		if(!$condition) {
			if($postContext) {
				$description .= ' at post: "' . $this->currentPostId . '"';
				if(isset($this->currentCommentId)) {
					$description .= ' at comment: "' . $this->currentCommentId . '"';
				}
			}
			if($isNotFatal) {
				if(Debug::isEnabled()) {
					trigger_error($description, E_USER_WARNING);
				}
			} else {
				returnServerError($description);
			}
		}

		return;
	}

	private function showExecutionTimes() {
		trigger_error('Data collected in ' . $this->totalExecutionTime . ' seconds', E_USER_NOTICE);

		trigger_error('Predownload took ' . $this->preDownloadExecutionTime . ' seconds', E_USER_NOTICE);

		$pre = 'Predownloaded: ';
		foreach($this->preDownloaded as $url => $data) {
			$pre .= $url . ' ';
		}
		trigger_error($pre, E_USER_NOTICE);

		$totalDownloading = 0.0;
		$totalParsing = 0.0;
		$totalFormatting = 0.0;
		$messages = array();

		foreach($this->postsExtractingTime as $id => $time) {
			$totalParsing += $time['parsing'];
			$totalDownloading += $time['downloading'];

			if(isset($time['formatting'])) {
				$totalFormatting += $time['formatting'];
				$message = 'Post ';
			} else {
				$message = 'Repost ';
			}
			$message .= $id . ' downloaded in ' . $time['downloading'] . ' seconds';
			$message .= ', parsed in ' . $time['parsing'] . ' seconds';
			if(isset($time['formatting'])) {
				$message .= ', formatted in ' . $time['formatting'] . ' seconds';
			}
			$messages[] = $message;
		}

		$total = 'In total ' . count($this->postsExtractingTime) . ' posts downloaded in ' . $totalDownloading . ' seconds';
		$total .= ', parsed in ' . $totalParsing . ' seconds';
		$total .= ', formatted in ' . $totalFormatting . ' seconds';

		trigger_error($total, E_USER_NOTICE);

		foreach($messages as $message) {
			trigger_error($message, E_USER_NOTICE);
		}
	}
}
