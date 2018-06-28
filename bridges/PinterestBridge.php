<?php
class PinterestBridge extends FeedExpander {

	const MAINTAINER = 'pauder';
	const NAME = 'Pinterest Bridge';
	const URI = 'https://www.pinterest.com';
	const DESCRIPTION = 'Returns the newest images on a board';

	const PARAMETERS = array(
		'By username and board' => array(
			'u' => array(
				'name' => 'username',
				'required' => true
			),
			'b' => array(
				'name' => 'board',
				'required' => true
			)
		),
		'From search' => array(
			'q' => array(
				'name' => 'Keyword',
				'required' => true
			)
		)
	);

	public function collectData(){
		switch($this->queriedContext) {
			case 'By username and board':
				$this->collectExpandableDatas($this->getURI() . '.rss');
				$this->fixLowRes();
				break;
			case 'From search':
			default:
				$html = getSimpleHTMLDOMCached($this->getURI());
				$this->getSearchResults($html);
		}
	}

	private function fixLowRes() {

		$newitems = [];
		$pattern = '/https\:\/\/i\.pinimg\.com\/[a-zA-Z0-9]*x\//';
		foreach($this->items as $item) {

			$item['content'] = preg_replace($pattern, 'https://i.pinimg.com/originals/', $item['content']);
			$newitems[] = $item;
		}
		$this->items = $newitems;

	}

	private function getSearchResults($html){
		$json = json_decode($html->find('#jsInit1', 0)->innertext, true);
		$results = $json['resourceDataCache'][0]['data']['results'];

		foreach($results as $result) {
			$item = array();

			$item['uri'] = self::URI . $result['board']['url'];

			// Some use regular titles, others provide 'advanced' infos, a few
			// provide even less info. Thus we attempt multiple options.
			$item['title'] = trim($result['title']);

			if($item['title'] === '')
				$item['title'] = trim($result['rich_summary']['display_name']);

			if($item['title'] === '')
				$item['title'] = trim($result['grid_description']);

			$item['timestamp'] = strtotime($result['created_at']);
			$item['username'] = $result['pinner']['username'];
			$item['fullname'] = $result['pinner']['full_name'];
			$item['avatar'] = $result['pinner']['image_small_url'];
			$item['author'] = $item['username'] . ' (' . $item['fullname'] . ')';
			$item['content'] = '<img align="left" style="margin: 2px 4px;" src="'
				. htmlentities($item['avatar'])
				. '" /><p><strong>'
				. $item['username']
				. '</strong><br>'
				. $item['fullname']
				. '</p><br><img src="'
				. $result['images']['736x']['url']
				. '" alt="" /><br><p>'
				. $result['description']
				. '</p>';

			$item['enclosures'] = array($result['images']['orig']['url']);

			$this->items[] = $item;
		}
	}

	public function getURI(){
		switch($this->queriedContext) {
		case 'By username and board':
			$uri = self::URI . '/' . urlencode($this->getInput('u')) . '/' . urlencode($this->getInput('b'));// . '.rss';
			break;
		case 'From search':
			$uri = self::URI . '/search/?q=' . urlencode($this->getInput('q'));
			break;
		default: return parent::getURI();
		}
		return $uri;
	}

	public function getName(){
		switch($this->queriedContext) {
		case 'By username and board':
			$specific = $this->getInput('u') . ' - ' . $this->getInput('b');
		break;
		case 'From search':
			$specific = $this->getInput('q');
		break;
		default: return parent::getName();
		}
		return $specific . ' - ' . self::NAME;
	}
}
