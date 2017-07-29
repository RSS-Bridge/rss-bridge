<?php
class KATBridge extends BridgeAbstract {
	const MAINTAINER = 'niawag';
	const NAME = 'KickassTorrents';
	const URI = 'https://katcr.co/new/';
	const DESCRIPTION = 'Returns results for the keywords. You can put several
 list of keywords by separating them with a semicolon (e.g. "one show;another
 show"). Category based search needs the category number as input. User based
 search takes the Uploader ID: see KAT URL for user feed. Search can be done in a specified category';

	const PARAMETERS = array( array(
		'q' => array(
			'name' => 'keywords, separated by semicolons',
			'exampleValue' => 'first list;second list;…',
			'required' => true
		),
		'crit' => array(
			'type' => 'list',
			'name' => 'Search type',
			'values' => array(
				'search' => 'search',
				'category' => 'cat',
				'user' => 'usr'
			)
		),
		'cat_check' => array(
			'type' => 'checkbox',
			'name' => 'Specify category for normal search ?',
		),
		'cat' => array(
			'name' => 'Category number',
			'exampleValue' => '100, 200… See KAT for category number'
		),
		'trusted' => array(
			'type' => 'checkbox',
			'name' => 'Only get results from Elite or Verified uploaders ?',
		),
	));
	public function collectData(){
		function parseDateTimestamp($element){
				$guessedDate = strptime($element, '%d-%m-%Y %H:%M:%S');
				$timestamp = mktime(
				$guessedDate['tm_hour'],
				$guessedDate['tm_min'],
				$guessedDate['tm_sec'],
				$guessedDate['tm_mon'] + 1,
				$guessedDate['tm_mday'],
				$guessedDate['tm_year'] + 1900);
				return $timestamp;
		}
		$catBool = $this->getInput('cat_check');
		if($catBool) {
			$catNum = $this->getInput('cat');
		}
		$critList = $this->getInput('crit');
		$trustedBool = $this->getInput('trusted');
		$keywordsList = explode(';', $this->getInput('q'));
		foreach($keywordsList as $keywords) {
			switch($critList) {
			case 'search':
				if($catBool == false) {
					$html = getSimpleHTMLDOM(
						self::URI .
						'torrents-search.php?search=' .
						rawurlencode($keywords)
					) or returnServerError('Could not request KAT.');
				} else {
					$html = getSimpleHTMLDOM(
						self::URI .
						'torrents-search.php?search=' .
						rawurlencode($keywords) .
						'&cat=' .
						rawurlencode($catNum)
						) or returnServerError('Could not request KAT.');
				}
				break;
			case 'cat':
				$html = getSimpleHTMLDOM(
					self::URI .
					'torrents.php?cat=' .
					rawurlencode($keywords)
				) or returnServerError('Could not request KAT.');
				break;
			case 'usr':
				$html = getSimpleHTMLDOM(
					self::URI .
					'account-details.php?id=' .
					rawurlencode($keywords)
				) or returnServerError('Could not request KAT.');
				break;
			}
			if ($html->find('table.ttable_headinner', 0) == false)
				returnServerError('No result for query ' . $keywords);
			foreach($html->find('tr.t-row') as $element) {
				if(!$trustedBool
				|| !is_null($element->find('i[title="Elite Uploader"]', 0))
				|| !is_null($element->find('i[title="Verified Uploader"]', 0))) {
					$item = array();
					$item['uri'] = self::URI . $element->find('a', 2)->href;
					$item['id'] = self::URI . $element->find('a.cellMainLink', 0)->href;
					$item['timestamp'] = parseDateTimestamp($element->find('td', 2)->plaintext);
					$item['author'] = $element->find('a.plain', 0)->plaintext;
					$item['title'] = $element->find('a.cellMainLink', 0)->plaintext;
					$item['seeders'] = (int)$element->find('td', 3)->plaintext;
					$item['leechers'] = (int)$element->find('td', 4)->plaintext;
					$item['size'] = $element->find('td', 1)->plaintext;
					$item['content'] = $item['title']
					. '<br>size: '
					. $item['size']
					. '<br>seeders: '
					. $item['seeders']
					. ' | leechers: '
					. $item['leechers']
					. '<br><a href="'
					. $item['id']
					. '">info page</a>';
					if(isset($item['title']))
						$this->items[] = $item;
				}
			}
		}
	}
}
