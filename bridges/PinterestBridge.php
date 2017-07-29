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
			),
			'r' => array(
				'name' => 'Use custom RSS',
				'type' => 'checkbox',
				'required' => false,
				'title' => 'Uncheck to return data via custom filters (more data)'
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
				if($this->getInput('r')) {
					$html = getSimpleHTMLDOMCached($this->getURI());
					$this->getUserResults($html);
				} else {
					$this->collectExpandableDatas($this->getURI() . '.rss');
				}
				break;
			case 'From search':
			default:
				$html = getSimpleHTMLDOMCached($this->getURI());
				$this->getSearchResults($html);
		}
	}

	private function getUserResults($html){
		$json = json_decode($html->find('#jsInit1', 0)->innertext, true);
		$results = $json['tree']['children'][0]['children'][0]['children'][0]['options']['props']['data']['board_feed'];
		$username = $json['resourceDataCache'][0]['data']['owner']['username'];
		$fullname = $json['resourceDataCache'][0]['data']['owner']['full_name'];
		$avatar = $json['resourceDataCache'][0]['data']['owner']['image_small_url'];

		foreach($results as $result) {
			$item = array();

			$item['uri'] = $result['link'];

			// Some use regular titles, others provide 'advanced' infos, a few
			// provide even less info. Thus we attempt multiple options.
			$item['title'] = trim($result['title']);

			if($item['title'] === "")
				$item['title'] = trim($result['rich_summary']['display_name']);

			if($item['title'] === "")
				$item['title'] = trim($result['description']);

			$item['timestamp'] = strtotime($result['created_at']);
			$item['username'] = $username;
			$item['fullname'] = $fullname;
			$item['avatar'] = $avatar;
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

	private function getSearchResults($html){
		$json = json_decode($html->find('#jsInit1', 0)->innertext, true);
		$results = $json['resourceDataCache'][0]['data']['results'];

		foreach($results as $result) {
			$item = array();

			$item['uri'] = self::URI . $result['board']['url'];

			// Some use regular titles, others provide 'advanced' infos, a few
			// provide even less info. Thus we attempt multiple options.
			$item['title'] = trim($result['title']);

			if($item['title'] === "")
				$item['title'] = trim($result['rich_summary']['display_name']);

			if($item['title'] === "")
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
