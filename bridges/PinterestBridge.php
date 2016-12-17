<?php
class PinterestBridge extends BridgeAbstract {

	const MAINTAINER = "pauder";
	const NAME = "Pinterest Bridge";
	const URI = "http://www.pinterest.com/";
	const DESCRIPTION = "Returns the newest images on a board";

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
		$html = getSimpleHTMLDOM($this->getURI());
		if(!$html){
			switch($this->queriedContext){
			case 'By username and board':
				returnServerError('Username and/or board not found');
			case 'From search':
				returnServerError('Could not request Pinterest.');
			}
		}

		if($this->queriedContext === 'From search'){
			foreach($html->find('div.pinWrapper') as $div){
				$item = array();

				$a = $div->find('a.pinImageWrapper', 0);
				$img = $a->find('img', 0);

				$item['uri'] = $this->getURI() . $a->getAttribute('href');
				$item['content'] = '<img src="'
				. htmlentities(str_replace('/236x/', '/736x/', $img->getAttribute('src')))
				. '" alt="" />';

				$avatar = $div->find('div.creditImg', 0)->find('img', 0);
				$avatar = $avatar->getAttribute('data-src');
				$avatar = str_replace("\\", "", $avatar);

				$username = $div->find('div.creditName', 0);
				$board = $div->find('div.creditTitle', 0);

				$item['username'] = $username->innertext;
				$item['fullname'] = $board->innertext;
				$item['avatar'] = $avatar;

				$item['content'] .= '<br /><img align="left" style="margin: 2px 4px;" src="'
				. htmlentities($item['avatar'])
				. '" /> <strong>'
				. $item['username']
				. '</strong>'
				. '<br />'
				. $item['fullname'];

				$item['title'] = $img->getAttribute('alt');
				$this->items[] = $item;
			}
		} elseif($this->queriedContext === 'By username and board'){
			$container = $html->find('SCRIPT[type="application/ld+json"]', 0)
				or returnServerError('Unable to find data container!');

			$json = json_decode($container->innertext, true);

			foreach($json['itemListElement'] as $element){
				$item = array();

				$item['uri'] = $element['item']['sharedContent']['author']['url'];
				$item['title'] = $element['item']['name'];
				$item['author'] = $element['item']['user']['name'];
				$item['timestamp'] = strtotime($element['item']['datePublished']);
				$item['content'] = <<<EOD
<a href="{$item['uri']}">
	<img src="{$element['item']['image']}">
</a>
<p>{$element['item']['text']}</p>
EOD;

				$this->items[] = $item;
			}
		}
	}

	public function getURI(){
		switch($this->queriedContext){
		case 'By username and board':
			$uri = self::URI . urlencode($this->getInput('u')) . '/' . urlencode($this->getInput('b'));
			break;
		case 'From search':
			$uri = self::URI . 'search/?q=' . urlencode($this->getInput('q'));
			break;
		default: return parent::getURI();
		}
		return $uri;
	}

	public function getName(){
		switch($this->queriedContext){
		case 'By username and board':
			$specific = $this->getInput('u') . '-' . $this->getInput('b');
		break;
		case 'From search':
			$specific = $this->getInput('q');
		break;
		default: return parent::getName();
		}
		return $specific . ' - ' . self::NAME;
	}
}
