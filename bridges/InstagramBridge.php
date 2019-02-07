<?php
class InstagramBridge extends BridgeAbstract {

	const MAINTAINER = 'pauder';
	const NAME = 'Instagram Bridge';
	const URI = 'https://www.instagram.com/';
	const DESCRIPTION = 'Returns the newest images';

	const PARAMETERS = array(
		'Username' => array(
			'u' => array(
				'name' => 'username',
				'required' => true
			)
		),
		'Hashtag' => array(
			'h' => array(
				'name' => 'hashtag',
				'required' => true
			)
		),
		'Location' => array(
			'l' => array(
				'name' => 'location',
				'required' => true
			)
		),
		'global' => array(
			'media_type' => array(
				'name' => 'Media type',
				'type' => 'list',
				'required' => false,
				'values' => array(
					'All' => 'all',
					'Story' => 'story',
					'Video' => 'video',
					'Picture' => 'picture',
				),
				'defaultValue' => 'all'
			)
		)

	);

	public function collectData(){

		if(is_null($this->getInput('u')) && $this->getInput('media_type') == 'story') {
			returnClientError('Stories are not supported for hashtags nor locations!');
		}

		$data = $this->getInstagramJSON($this->getURI());

		if(!is_null($this->getInput('u'))) {
			$userMedia = $data->entry_data->ProfilePage[0]->graphql->user->edge_owner_to_timeline_media->edges;
		} elseif(!is_null($this->getInput('h'))) {
			$userMedia = $data->entry_data->TagPage[0]->graphql->hashtag->edge_hashtag_to_media->edges;
		} elseif(!is_null($this->getInput('l'))) {
			$userMedia = $data->entry_data->LocationsPage[0]->graphql->location->edge_location_to_media->edges;
		}

		foreach($userMedia as $media) {
			$media = $media->node;

			if(!is_null($this->getInput('u'))) {
				switch($this->getInput('media_type')) {
					case 'all': break;
					case 'video':
						if($media->__typename != 'GraphVideo') continue 2;
						break;
					case 'picture':
						if($media->__typename != 'GraphImage') continue 2;
						break;
					case 'story':
						if($media->__typename != 'GraphSidecar') continue 2;
						break;
					default: break;
				}
			} else {
				if($this->getInput('media_type') == 'video' && !$media->is_video) continue;
			}

			$item = array();
			$item['uri'] = self::URI . 'p/' . $media->shortcode . '/';

			if (isset($media->owner->username)) {
				$item['author'] = $media->owner->username;
			}

			if (isset($media->edge_media_to_caption->edges[0]->node->text)) {
				$textContent = $media->edge_media_to_caption->edges[0]->node->text;
			} else {
				$textContent = basename($media->display_url);
			}

			$item['title'] = ($media->is_video ? '▶ ' : '') . trim($textContent);
			$titleLinePos = strpos(wordwrap($item['title'], 120), "\n");
			if ($titleLinePos != false) {
				$item['title'] = substr($item['title'], 0, $titleLinePos) . '...';
			}

			if(!is_null($this->getInput('u')) && $media->__typename == 'GraphSidecar') {
				$data = $this->getInstagramStory($item['uri']);
				$item['content'] = $data[0];
				$item['enclosures'] = $data[1];
			} else {
				$item['content'] = '<a href="' . htmlentities($item['uri']) . '" target="_blank">';
				$item['content'] .= '<img src="' . htmlentities($media->display_url) . '" alt="' . $item['title'] . '" />';
				$item['content'] .= '</a><br><br>' . nl2br(htmlentities($textContent));
				$item['enclosures'] = array($media->display_url);
			}

			$item['timestamp'] = $media->taken_at_timestamp;

			$this->items[] = $item;
		}
	}

	protected function getInstagramStory($uri) {

		$data = $this->getInstagramJSON($uri);
		$mediaInfo = $data->entry_data->PostPage[0]->graphql->shortcode_media;

		//Process the first element, that isn't in the node graph
		if (count($mediaInfo->edge_media_to_caption->edges) > 0) {
			$caption = $mediaInfo->edge_media_to_caption->edges[0]->node->text;
		} else {
			$caption = '';
		}

		$enclosures = [$mediaInfo->display_url];
		$content = '<img src="' . htmlentities($mediaInfo->display_url) . '" alt="' . $caption . '" />';

		foreach($mediaInfo->edge_sidecar_to_children->edges as $media) {
			$display_url = $media->node->display_url;
			if(!in_array($display_url, $enclosures)) { // add only if not added yet
				$content .= '<img src="' . htmlentities($display_url) . '" alt="' . $caption . '" />';
				$enclosures[] = $display_url;
			}
		}

		return [$content, $enclosures];

	}

	protected function getInstagramJSON($uri) {

		$html = getContents($uri)
			or returnServerError('Could not request Instagram.');
		$scriptRegex = '/window\._sharedData = (.*);<\/script>/';

		preg_match($scriptRegex, $html, $matches, PREG_OFFSET_CAPTURE, 0);

		return json_decode($matches[1][0]);

	}

	public function getName(){
		if(!is_null($this->getInput('u'))) {
			return $this->getInput('u') . ' - Instagram Bridge';
		}

		return parent::getName();
	}

	public function getURI(){
		if(!is_null($this->getInput('u'))) {
			return self::URI . urlencode($this->getInput('u')) . '/';
		} elseif(!is_null($this->getInput('h'))) {
			return self::URI . 'explore/tags/' . urlencode($this->getInput('h'));
		} elseif(!is_null($this->getInput('l'))) {
			return self::URI . 'explore/locations/' . urlencode($this->getInput('l'));
		}
		return parent::getURI();
	}
}
