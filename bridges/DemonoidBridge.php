<?php
class DemonoidBridge extends BridgeAbstract {

	const MAINTAINER = 'metaMMA';
	const NAME = 'Demonoid';
	const URI = 'https://www.demonoid.pw/';
	const DESCRIPTION = 'Returns results from search';

	const PARAMETERS = array(array(
		'q' => array(
			'name' => 'keywords',
			'exampleValue' => 'keyword1 keyword2…',
			'required' => true,
			),
		'category' => array(
			'name' => 'Category',
			'type' => 'list',
			'values' => array(
				'All' => 0,
				'Movies' => 1,
				'Music' => 2,
				'TV' => 3,
				'Games' => 4,
				'Applications' => 5,
				'Pictures' => 8,
				'Anime' => 9,
				'Comics' => 10,
				'Books' => 11,
				'Audiobooks' => 17
				)
			)
		), array(
		'catOnly' => array(
			'name' => 'Category',
			'type' => 'list',
			'values' => array(
				'All' => 0,
				'Movies' => 1,
				'Music' => 2,
				'TV' => 3,
				'Games' => 4,
				'Applications' => 5,
				'Pictures' => 8,
				'Anime' => 9,
				'Comics' => 10,
				'Books' => 11,
				'Audiobooks' => 17
				)
			)
		), array(
		'userid' => array(
			'name' => 'user id',
			'exampleValue' => '00000',
			'required' => true,
			'type' => 'number'
			),
		'category' => array(
			'name' => 'Category',
			'type' => 'list',
			'values' => array(
				'All' => 0,
				'Movies' => 1,
				'Music' => 2,
				'TV' => 3,
				'Games' => 4,
				'Applications' => 5,
				'Pictures' => 8,
				'Anime' => 9,
				'Comics' => 10,
				'Books' => 11,
				'Audiobooks' => 17
				)
			)
		)
	);

	public function collectData() {

		if(!empty($this->getInput('q'))) {

			$html = getSimpleHTMLDOM(
				self::URI .
				'files/?category=' .
				rawurlencode($this->getInput('category')) .
				'&subcategory=All&quality=All&seeded=2&external=2&query=' .
				urlencode($this->getInput('q')) .
				'&uid=0&sort='
				) or returnServerError('Could not request Demonoid.');

		} elseif(!empty($this->getInput('catOnly'))) {

			$html = getSimpleHTMLDOM(
				self::URI .
				'files/?uid=0&category=' .
				rawurlencode($this->getInput('catOnly')) .
				'&subcategory=0&language=0&seeded=2&quality=0&query=&sort='
				) or returnServerError('Could not request Demonoid.');

		} elseif(!empty($this->getInput('userid'))) {

			$html = getSimpleHTMLDOM(
				self::URI .
				'files/?uid=' .
				rawurlencode($this->getInput('userid')) .
				'&seeded=2'
				) or returnServerError('Could not request Demonoid.');

		} else {
			returnServerError('Invalid parameters !');
		}

		if(preg_match('~No torrents found~', $html)) {
			return;
		}

		$table = $html->find('td[class=ctable_content_no_pad]', 0);
		$cursorCount = 4;
		$elementCount = 0;
		while($elementCount != 40) {
			$elementCount++;
			$currentElement = $table->find('tr', $cursorCount);
			if(preg_match('~items total~', $currentElement)) {
				break;
			}
			$item = array();
			//Do we have a date ?
			if(preg_match('~Added.*?(.*)~', $currentElement->plaintext, $dateStr)) {
				if(preg_match('~today~', $dateStr[0])) {
					date_default_timezone_set('UTC');
					$timestamp = mktime(0, 0, 0, gmdate('n'), gmdate('j'), gmdate('Y'));
				} else {
					preg_match('~(?<=ed on ).*\d+~', $currentElement->plaintext, $fullDateStr);
					date_default_timezone_set('UTC');
					$dateObj = strptime($fullDateStr[0], '%A, %b %d, %Y');
					$timestamp = mktime(0, 0, 0, $dateObj['tm_mon'] + 1, $dateObj['tm_mday'], 1900 + $dateObj['tm_year']);
				}
				$cursorCount++;
			}

			$content = $table->find('tr', $cursorCount)->find('a', 1);
			$cursorCount++;
			$torrentInfo = $table->find('tr', $cursorCount);
			$item['timestamp'] = $timestamp;
			$item['title'] = $content->plaintext;
			$item['id'] = self::URI . $content->href;
			$item['uri'] = self::URI . $content->href;
			$item['author'] = $torrentInfo->find('a[class=user]', 0)->plaintext;
			$item['seeders'] = $torrentInfo->find('font[class=green]', 0)->plaintext;
			$item['leechers'] = $torrentInfo->find('font[class=red]', 0)->plaintext;
			$item['size'] = $torrentInfo->find('td', 3)->plaintext;
			$item['content'] = 'Uploaded by ' . $item['author']
				. ' , Size ' . $item['size']
				. '<br>seeders: '
				. $item['seeders']
				. ' | leechers: '
				. $item['leechers']
				. '<br><a href="'
				. $item['id']
				. '">info page</a>';

			$this->items[] = $item;

			$cursorCount++;
		}
	}
}
