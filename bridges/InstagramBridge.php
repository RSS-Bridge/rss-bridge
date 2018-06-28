<?php
class InstagramBridge extends BridgeAbstract {

	const MAINTAINER = 'pauder';
	const NAME = 'Instagram Bridge';
	const URI = 'https://instagram.com/';
	const DESCRIPTION = 'Returns the newest images';

	const PARAMETERS = array(
		array(
			'u' => array(
				'name' => 'username',
				'required' => true
			)
		),
		array(
			'h' => array(
				'name' => 'hashtag',
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

		if(!is_null($this->getInput('h')) && $this->getInput('media_type') == 'story') {
			returnClientError('Stories are not supported for hashtags!');
		}

		$data = $this->getInstagramJSON($this->getURI());

		if(!is_null($this->getInput('u'))) {
			$userMedia = $data->entry_data->ProfilePage[0]->graphql->user->edge_owner_to_timeline_media->edges;
		} else {
			$userMedia = $data->entry_data->TagPage[0]->graphql->hashtag->edge_hashtag_to_media->edges;
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

			if (isset($media->edge_media_to_caption->edges[0]->node->text)) {
				$item['title'] = $media->edge_media_to_caption->edges[0]->node->text;
			} else {
				$item['title'] = basename($media->display_url);
			}

			if(!is_null($this->getInput('u')) && $media->__typename == 'GraphSidecar') {
				$data = $this->getInstagramStory($item['uri']);
				$item['content'] = $data[0];
				$item['enclosures'] = $data[1];
			} else {
				$item['content'] = '<img src="' . htmlentities($media->display_url) . '" alt="'. $item['title'] . '" />';
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
		$caption = $mediaInfo->edge_media_to_caption->edges[0]->node->text;

		$enclosures = [$mediaInfo->display_url];
		$content = '<img src="' . htmlentities($mediaInfo->display_url) . '" alt="'. $caption . '" />';

		foreach($mediaInfo->edge_sidecar_to_children->edges as $media) {

			$content .= '<img src="' . htmlentities($media->node->display_url) . '" alt="'. $caption . '" />';
			$enclosures[] = $media->node->display_url;

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
			return self::URI . urlencode($this->getInput('u'));
		} elseif(!is_null($this->getInput('h'))) {
			return self::URI . 'explore/tags/' . urlencode($this->getInput('h'));
		}

		return parent::getURI();
	}
}
