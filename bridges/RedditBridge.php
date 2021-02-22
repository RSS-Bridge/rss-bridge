<?php

class RedditBridge extends BridgeAbstract {

	const MAINTAINER = 'dawidsowa';
	const NAME = 'Reddit Bridge';
	const URI = 'https://www.reddit.com';
	const DESCRIPTION = 'Return hot submissions from Reddit';

	const PARAMETERS = array(
		'single' => array(
			'r' => array(
				'name' => 'SubReddit',
				'required' => true,
				'exampleValue' => 'selfhosted',
				'title' => 'SubReddit name'
			)
		),
		'multi' => array(
			'rs' => array(
				'name' => 'SubReddits',
				'required' => true,
				'exampleValue' => 'selfhosted, php',
				'title' => 'SubReddit names, separated by commas'
			)
		),
		'user' => array(
			'u' => array(
				'name' => 'User',
				'required' => true,
				'title' => 'User name'
			),
			'comments' => array(
				'type' => 'checkbox',
				'name' => 'Comments',
				'title' => 'Whether to return comments',
				'defaultValue' => false
			)
		)
	);

	public function getIcon() {
		return 'https://www.redditstatic.com/desktop2x/img/favicon/favicon-96x96.png';
	}

	public function getName() {
		if ($this->queriedContext == 'single') {
			return 'Reddit r/' . $this->getInput('r');
		} elseif ($this->queriedContext == 'user') {
			return 'Reddit u/' . $this->getInput('u');
		} else {
			return self::NAME;
		}
	}

	public function collectData() {

		$user = false;
		$comments = false;

		switch ($this->queriedContext) {
			case 'single':
				$subreddits[] = $this->getInput('r');
				break;
			case 'multi':
				$subreddits = explode(',', $this->getInput('rs'));
				break;
			case 'user':
				$subreddits[] = $this->getInput('u');
				$user = true;
				$comments = $this->getInput('comments');
				break;
		}

		foreach ($subreddits as $subreddit) {
			$name = trim($subreddit);

			$values = getContents(self::URI . ($user ? '/user/' : '/r/') . $name . '.json')
			or returnServerError('Unable to fetch posts!');
			$decodedValues = json_decode($values);

			foreach ($decodedValues->data->children as $post) {
				if ($post->kind == 't1' && !$comments) {
					continue;
				}

				$data = $post->data;

				$item = array();
				$item['author'] = $data->author;
				$item['uid'] = $data->id;
				$item['timestamp'] = $data->created_utc;
				$item['uri'] = $this->encodePermalink($data->permalink);

				$item['categories'] = array();

				if ($post->kind == 't1') {
					$item['title'] = 'Comment: ' . $data->link_title;
				} else {
					$item['title'] = $data->title;

					$item['categories'][] = $data->link_flair_text;
					$item['categories'][] = $data->pinned ? 'Pinned' : null;
					$item['categories'][] = $data->spoiler ? 'Spoiler' : null;
				}

				$item['categories'][] = $data->over_18 ? 'NSFW' : null;
				$item['categories'] = array_filter($item['categories']);

				if ($post->kind == 't1') {
					// Comment

					$item['content']
						= htmlspecialchars_decode($data->body_html);

				} elseif ($data->is_self) {
					// Text post

					$item['content']
						= htmlspecialchars_decode($data->selftext_html);

				} elseif (isset($data->post_hint) ? $data->post_hint == 'link' : false) {
					// Link with preview

					if (isset($data->media)) {
						// Reddit embeds content for some sites (e.g. Twitter)
						$embed = htmlspecialchars_decode(
							$data->media->oembed->html
						);
					} else {
						$embed = '';
					}

					$item['content'] = $this->template(
							$data->url,
							$data->thumbnail,
							$data->domain
						) . $embed;

				} elseif (isset($data->post_hint) ? $data->post_hint == 'image' : false) {
					// Single image

					$item['content'] = $this->link(
						$this->encodePermalink($data->permalink),
						'<img src="' . $data->url . '" />'
					);

				} elseif (isset($data->is_gallery) ? $data->is_gallery : false) {
					// Multiple images

					$images = array();
					foreach ($data->gallery_data->items as $media) {
						$id = $media->media_id;
						$type = $data->media_metadata->$id->m == 'image/gif' ? 'gif' : 'u';
						$src = $data->media_metadata->$id->s->$type;
						$images[] = '<figure><img src="' . $src . '"/></figure>';
					}

					$item['content'] = implode('', $images);

				} elseif ($data->is_video) {
					// Video

					// Higher index -> Higher resolution
					end($data->preview->images[0]->resolutions);
					$index = key($data->preview->images[0]->resolutions);

					$item['content'] = $this->template(
						$data->url,
						$data->preview->images[0]->resolutions[$index]->url,
						'Video'
					);

				} elseif (isset($data->media) ? $data->media->type == 'youtube.com' : false) {
					// Youtube link

					$item['content'] = $this->template(
						$data->url,
						$data->media->oembed->thumbnail_url,
						'YouTube');

				} elseif (explode('.', $data->domain)[0] == 'self') {
					// Crossposted text post
					// TODO (optionally?) Fetch content of the original post.

					$item['content'] = $this->link(
						$this->encodePermalink($data->permalink),
						'Crossposted from r/'
						. explode('.', $data->domain)[1]
					);

				} else {
					// Link WITHOUT preview

					$item['content'] = $this->link($data->url, $data->domain);
				}

				$this->items[] = $item;
			}
		}
	}

	private function encodePermalink($link) {
		return self::URI . implode(
				'/',
				array_map('urlencode', explode('/', $link))
			);
	}

	private function template($href, $src, $caption) {
		return '<a href="' . $href . '"><figure><figcaption>'
			. $caption . '</figcaption><img src="'
			. $src . '"/></figure></a>';
	}

	private function link($href, $text) {
		return '<a href="' . $href . '">' . $text . '</a>';
	}
}
