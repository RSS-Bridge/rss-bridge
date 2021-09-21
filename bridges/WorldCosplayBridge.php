<?php
class WorldCosplayBridge extends BridgeAbstract {
	const NAME = 'WorldCosplay Bridge';
	const URI = 'https://worldcosplay.net/';
	const DESCRIPTION = 'Returns WorldCosplay photos';
	const MAINTAINER = 'AxorPL';

	const API_CHARACTER = 'api/photo/list.json?character_id=%u&limit=%u';
	const API_COSPLAYER = 'api/member/photos.json?member_id=%u&limit=%u';
	const API_SERIES = 'api/photo/list.json?title_id=%u&limit=%u';
	const API_TAG = 'api/tag/photo_list.json?id=%u&limit=%u';

	const CONTENT_HTML
		= '<a href="%s" target="_blank"><img src="%s" alt="%s" title="%s"></a>';

	const ERR_CONTEXT = 'No context provided';
	const ERR_QUERY = 'Unable to query: %s';

	const LIMIT_MIN = 1;
	const LIMIT_MAX = 24;

	const PARAMETERS = array(
		'Character' => array(
			'cid' => array(
				'name' => 'Character ID',
				'type' => 'number',
				'required' => true,
				'title' => 'WorldCosplay character ID',
				'exampleValue' => 18204
			)
		),
		'Cosplayer' => array(
			'uid' => array(
				'name' => 'Cosplayer ID',
				'type' => 'number',
				'required' => true,
				'title' => 'Cosplayer\'s WorldCosplay profile ID',
				'exampleValue' => 406782
			)
		),
		'Series' => array(
			'sid' => array(
				'name' => 'Series ID',
				'type' => 'number',
				'required' => true,
				'title' => 'WorldCosplay series ID',
				'exampleValue' => 3139
			)
		),
		'Tag' => array(
			'tid' => array(
				'name' => 'Tag ID',
				'type' => 'number',
				'required' => true,
				'title' => 'WorldCosplay tag ID',
				'exampleValue' => 33643
			)
		),
		'global' => array(
			'limit' => array(
				'name' => 'Limit',
				'type' => 'number',
				'required' => false,
				'title' => 'Maximum number of photos to return',
				'exampleValue' => 5,
				'defaultValue' => 5
			)
		)
	);

	public function collectData() {
		$limit = $this->getInput('limit');
		$limit = min(self::LIMIT_MAX, max(self::LIMIT_MIN, $limit));
		switch($this->queriedContext) {
			case 'Character':
				$id = $this->getInput('cid');
				$url = self::API_CHARACTER;
				break;
			case 'Cosplayer':
				$id = $this->getInput('uid');
				$url = self::API_COSPLAYER;
				break;
			case 'Series':
				$id = $this->getInput('sid');
				$url = self::API_SERIES;
				break;
			case 'Tag':
				$id = $this->getInput('tid');
				$url = self::API_TAG;
				break;
			default:
				returnClientError(self::ERR_CONTEXT);
		}
		$url = self::URI . sprintf($url, $id, $limit);

		$json = json_decode(getContents($url))
			or returnServerError(sprintf(self::ERR_QUERY, $url));
		if($json->has_error) {
			returnServerError($json->message);
		}
		$list = $json->list;

		foreach($list as $img) {
			$item = array();
			$item['uri'] = self::URI . substr($img->photo->url, 1);
			$item['title'] = $img->photo->subject;
			$item['timestamp'] = $img->photo->created_at;
			$item['author'] = $img->member->global_name;
			$item['enclosures'] = array($img->photo->large_url);
			$item['uid'] = $img->photo->id;
			$item['content'] = sprintf(
				self::CONTENT_HTML,
				$item['uri'],
				$item['enclosures'][0],
				$item['title'],
				$item['title']
			);
			$this->items[] = $item;
		}
	}

	public function getName() {
		switch($this->queriedContext) {
			case 'Character':
				$id = $this->getInput('cid');
				break;
			case 'Cosplayer':
				$id = $this->getInput('uid');
				break;
			case 'Series':
				$id = $this->getInput('sid');
				break;
			case 'Tag':
				$id = $this->getInput('tid');
				break;
			default:
				return parent::getName();
		}
		return sprintf('%s %u - ', $this->queriedContext, $id) . self::NAME;
	}
}
