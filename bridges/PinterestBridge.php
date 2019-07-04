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
		)
	);

	public function getIcon() {
		return 'https://s.pinimg.com/webapp/style/images/favicon-9f8f9adf.png';
	}

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
