<?php
namespace d7sd6u\VKPostsFormatterHTML5;

class Formatter {
	private $post;
	private $options;

	const defaultOptions = array(
		'descendingCommentThreshold' => 0,
		'hardDescendingCommentThreshold' => false,
		'descendingCommentThresholdOffset' => 0,
		'dontAddDeletedAmount' => false,
		'topCommentThreshold' => 0,
		'branchCommentThreshold' => 0,
		'dontConvertEmoji' => false
	);

	public function __construct($options = array()) {
		foreach(self::defaultOptions as $name => $value) {
			if(!isset($options[$name])) {
				$this->options[$name] = self::defaultOptions[$name];
			} else {
				$this->options[$name] = $options[$name];
			}
		}
	}

	public function setPost($post) {
		$this->post = $post;
	}

	public function formatContent() {
		if($this->options['dontConvertEmoji']) {
			$content = $this->post['text']['emojis'];
		} else {
			$content = $this->post['text']['html'];
		}

		$content .= $this->formatImages($this->post['images']);
		$content .= $this->formatVideos($this->post['videos']);
		$content .= $this->formatFiles($this->post['files']);
		$content .= $this->formatAudios($this->post['audios']);
		$content .= $this->formatPool();
		$content .= $this->formatArticle();
		$content .= $this->formatMap();
		$content .= $this->formatPoster();
		$content .= $this->formatExpandedLink();
		$content .= $this->formatRepost();
		$content .= $this->formatOrigin();

		return $content;
	}

	public function formatComments() {
		$content = '<br/>';

		if($this->post['comments'] === null) {
			$content .= '<br/>Comments are disabled or their extraction failed';
			return $content;
		}

		$commentsAmount = count($this->post['comments']);
		foreach($this->post['comments'] as $comment) {
			if(isset($comment['replies'])) {
				$commentsAmount += count($comment['replies']);
			}
		}

		if($commentsAmount === 0) {
			$content .= 'No comments.';
		} else {
			$content .= "<details><summary>$commentsAmount+ comments:</summary><br/>";
		}

		$firstIteration = true;
		foreach($this->post['comments'] as $comment) {
			$threshold = $this->options['descendingCommentThreshold'];
			$hard = $this->options['hardDescendingCommentThreshold'];
			$offset = $this->options['descendingCommentThresholdOffset'];
			$addAmount = !$this->options['dontAddDeletedAmount'];

			$separator = '<br/><hr/>';

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
			if($comment['likes'] >= $this->options['topCommentThreshold']
			&& $topCommentLikes >= $this->options['branchCommentThreshold']) {
				if($firstIteration) {
					$firstIteration = false;
				} else {
					$content .= $separator;
				}

				$content .= "<br/><i id='$comment[id]'>Comment: </i><br/>";

				$content .= $this->formatComment($comment, $this->post['id']);

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
						$content .= $separator;
						$content .= "<br/><i><a id='$reply[id]' href='#$reply[replyId]'>Reply: </a></i><br/>";
						$content .= $this->formatComment($reply, $this->post['id']);
					}
					if($addAmount && $hard && $reply['likes'] < $threshold) {
						$deletedAmount++;
					}
				}
				// add notice about comments filtered after last valid reply
				$deletedAmount = count($comment['replies']) - $lastValidComment - $offset - 1;
				if($addAmount && $deletedAmount >= 1) {
					$content .= $separator;
					$content .= "<i>$deletedAmount repl";
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
					$content .= $separator;
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

	private function formatComment($comment) {
		$content = "<i>Author: <a href='{$comment['author']['link']}'>{$comment['author']['name']}</a></i><br/>";
		$content .= '<i>Avatar: </i>';
		$content .= "<a href='{$comment['author']['link']}'>";
		$content .= "<img src='{$comment['author']['avatar']}'/>";
		$content .= '</a><br/><br/>';

		
		if($this->options['dontConvertEmoji']) {
			$content .= $comment['text']['emojis'];
		} else {
			$content .= $comment['text']['html'];
		}

		$content .= $this->formatImages($comment['images']);
		$content .= $this->formatVideos($comment['videos']);
		$content .= $this->formatAudios($comment['audios']);

		$content .= "<br/><br/><i>Likes: </i>$comment[likes]";

		return $content;
	}

	private function formatImages($images) {
		$content = '';
		foreach($images as $image) {
			$content .= "<br/><a href='$image[original]'><img src='$image[thumb]'/></a><br/>";
		}
		return $content;
	}

	private function formatVideos($videos) {
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
					$postId = $this->post['id'];
					$content .= "<a href='https://vk.com/post$postId?z=video$video[id]'>$videoPreview</a><br/>";
				}
			}
		}
		return $content;
	}

	private function formatPool() {
		$content = '';
		if(!empty($this->pool)) {
			$content .= "<br/><br/><i>Pool: </i>{$this->pool['title']}<br/><br/>";
			$content .= "<i>Author: </i>{$this->pool['author']}<br/>";
			$content .= "<i>Type: </i>{$this->pool['type']}<br/><br/>";
			foreach($this->pool['options'] as $option) {
				$content .= "<i>Option: </i>$option<br/>";
			}
			$content .= "<br/><i>Total voted: </i>{$this->pool['total']}<br/>";
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
				$content .= "Audio: <a href='$audio[url]'>$audio[title]</a><br/>";
				//$content .= '</audio>';
			}
		}
		return $content;
	}

	private function formatPoster() {
		$content = '';
		if(!empty($this->post['poster'])) {
			$content .= '<br/><br/><i>Poster:</i><br/><br/>';
			$content .= $this->post['poster']['text'];
			$content .= "<br/><img src='{$this->post['poster']['image']}'/><br/>";
		}
		return $content;
	}

	private function formatMap() {
		$content = '';
		if(!empty($this->post['map'])) {
			$content .= "<br/><br/><i>Location: </i>{$this->post['map']['text']}<br/>";
			$content .= '<i>Map:</i><br/>';
			$content .= "<a href='{$this->post['map']['url']}'><img src='{$this->post['map']['image']}'/></a><br/>";
		}
		return $content;
	}

	private function formatArticle() {
		$content = '';
		if(!empty($this->post['article'])) {
			$content .= '<br/><br/><i>Article: </i>';
			$content .= "<a href='{$this->post['article']['url']}'>{$this->post['article']['title']}</a><br/>";
			$content .= "<i>Author: </i>{$this->post['article']['author']}<br/>";
			$content .= "<i>Image: </i><br/><a href='{$this->post['article']['url']}'>";
			$content .= "<img src='{$this->post['article']['image']}'/>";
			$content .= '</a><br/>';
		}
		return $content;
	}

	private function formatExpandedLink() {
		$content = '';
		if(!empty($this->post['link'])) {
			$content .= '<br/><br/><i>Link: </i>';
				$content .= "<a href='{$this->post['link']['url']}'>{$this->post['link']['title']}</a><br/>";
			$content .= '<i>Image: </i><br/>';
			$content .= "<a href='{$this->post['link']['url']}'>";
			$content .= "<img src='{$this->post['link']['image']}'/>";
			$content .= '</a><br/>';
		}
		return $content;
	}

	private function formatRepost() {
		$content = '';

		if(!empty($this->post['repost'])) {
			if(!empty($content)) {
				$content .= '<br/><br/><br/>';
			}
			$content .= '<i>Repost:</i><br/><br/>';
			$content .= "<i>Source: </i><a href='{$this->post['repost']['url']}'>{$this->post['repost']['source']}</a><br/>";
			$content .= "<i>Author: </i>{$this->post['repost']['author']}<br/>";
			$content .= '<i>Timestamp: </i>';
			$content .= strftime('%c', $this->post['repost']['timestamp']);
			$content .= '<br/><br/>';

			$formatter = new Formatter($this->options);
			$formatter->setPost($this->post['repost']);

			$content .= $formatter->formatContent();
		}

		return $content;
	}

	private function formatOrigin() {
		$content = '';

		if(!empty($this->post['origin'])) {
			$content .= '<br/><br/>';
			$content .= "<i>Source: </i><a href='{$this->post['origin']['link']}'>{$this->post['origin']['name']}</a>";
		}

		return $content;
	}
}

?>
