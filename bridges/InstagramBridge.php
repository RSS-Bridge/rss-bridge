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
			),
			'media_type' => array(
				'name' => 'Media type',
				'type' => 'list',
				'required' => false,
				'values' => array(
					'Both' => 'all',
					'Video' => 'video',
					'Picture' => 'picture'
				),
				'defaultValue' => 'all'
			)
		),
		array(
			'h' => array(
				'name' => 'hashtag',
				'required' => true
			),
			'media_type' => array(
				'name' => 'Media type',
				'type' => 'list',
				'required' => false,
				'values' => array(
					'Both' => 'all',
					'Video' => 'video',
					'Picture' => 'picture'
				),
				'defaultValue' => 'all'
			)
		)
	);

	public function collectData(){
		$html = getSimpleHTMLDOM($this->getURI())
			or returnServerError('Could not request Instagram.');

		$innertext = null;

		foreach($html->find('script') as $script) {
			if('' === $script->innertext) {
				continue;
			}

			$pos = strpos(trim($script->innertext), 'window._sharedData');
			if(0 !== $pos) {
				continue;
			}

			$innertext = $script->innertext;
			break;
		}

		$json = trim(substr($innertext, $pos + 18), ' =;');
		$data = json_decode($json);

		if(!is_null($this->getInput('u'))) {
			$userMedia = $data->entry_data->ProfilePage[0]->graphql->user->edge_owner_to_timeline_media->edges;
		} else {
			$userMedia = $data->entry_data->TagPage[0]->graphql->hashtag->edge_hashtag_to_media->edges;
		}

		foreach($userMedia as $media) {
			$media = $media->node;
			// Check media type
			switch($this->getInput('media_type')) {
				case 'all': break;
				case 'video':
					if($media->is_video === false) continue 2;
					break;
				case 'picture':
					if($media->is_video === true) continue 2;
					break;
				default: break;
			}

			$item = array();
			$item['uri'] = self::URI . 'p/' . $media->shortcode . '/';
			$item['content'] = '<img src="' . htmlentities($media->display_url) . '" />';
			if (isset($media->edge_media_to_caption->edges[0]->node->text)) {
				$item['title'] = $media->edge_media_to_caption->edges[0]->node->text;
			} else {
				$item['title'] = basename($media->display_url);
			}
			$item['timestamp'] = $media->taken_at_timestamp;
			$item['enclosures'] = array($media->display_url);
			$this->items[] = $item;
		}
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
