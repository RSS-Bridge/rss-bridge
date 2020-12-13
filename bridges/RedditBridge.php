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
		)
	);

	public function getIcon() {
		return 'https://www.redditstatic.com/desktop2x/img/favicon/favicon-96x96.png';
	}

	public function getName() {
		if ($this->queriedContext == 'single') {
			return 'Reddit r/' . $this->getInput('r');
		} else {
			return self::NAME;
		}
	}

	public function collectData() {
		switch ($this->queriedContext) {
			case 'single':
				$subreddits[] = $this->getInput('r');
				break;
			case 'multi':
				$subreddits = explode(',', $this->getInput('rs'));
				break;
		}

		foreach ($subreddits as $subreddit) {
			$name = trim($subreddit);

			$values = getContents(self::URI . '/r/' . $name . '.json')
			or returnServerError('Unable to fetch posts!');
			$decodedValues = json_decode($values);

			foreach ($decodedValues->data->children as $post) {
				$data = $post->data;

				$item = array();
				$item['author'] = $data->author;
				$item['title'] = $data->title;
				$item['uid'] = $data->id;
				$item['timestamp'] = $data->created_utc;
				$item['uri'] = $this->encodePermalink($data->permalink);

				$item['categories'] = array();
				$item['categories'][] = $data->link_flair_text;
				$item['categories'][] = $data->pinned ? 'Pinned' : null;
				$item['categories'][] = $data->over_18 ? 'NSFW' : null;
				$item['categories'][] = $data->spoiler ? 'Spoiler' : null;
				$item['categories'] = array_filter($item['categories']);

				if ($data->is_self) {
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
						$images[] = '<img src="' . $src . '"/>';
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
