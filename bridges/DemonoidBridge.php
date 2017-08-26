<?php
class DemonoidBridge extends BridgeAbstract {

	const MAINTAINER = 'metaMMA';
	const NAME = 'Demonoid';
	const URI = 'https://www.demonoid.pw/';
	const DESCRIPTION = 'Returns results for the keywords (in all categories or
	a specific category). You can put several keywords separated by a semicolon
 (e.g. "one show;another show"). Searches can by done in a specific category;
 category number must be specified. (All=0, Movies=1, Music=2, TV=3, Games=4,
 Applications=5, Pictures=8, Anime=9, Comics=10, Books=11 Music Videos=8,
 Audio Books=17). User feed takes the Uploader ID number (not  uploader name)
 as keyword. Uploader ID is found by clicking on uploader,  clicking on
 "View this user\'s torrents", and copying the number after  "uid=". An entire
 category feed is accomplished by leaving "keywords" box blank and using the
 corresponding category number.';

	const PARAMETERS = array( array(
		'q' => array(
			'name' => 'keywords/user ID/category, separated by semicolons',
			'exampleValue' => 'first list;second list;…',
			'required' => true
		),
		'crit' => array(
			'type' => 'list',
			'name' => 'Feed type',
			'values' => array(
				'search' => 'search',
				'category' => 'cat',
				'user' => 'usr'
			)
		),
		'catCheck' => array(
			'type' => 'checkbox',
			'name' => 'Specify category for keyword search ?',
		),
		'cat' => array(
			'name' => 'Category number',
		),
	));

	public function collectData() {

		$catBool = $this->getInput('catCheck');
		if($catBool) {
			$catNum = $this->getInput('cat');
		}
		$critList = $this->getInput('crit');

		$keywordsList = explode(';', $this->getInput('q'));
		foreach($keywordsList as $keywords) {
			switch($critList) {
				case 'search':
				if($catBool == false) {
					$html = file_get_contents(
						self::URI .
						'files/?category=0&subcategory=All&quality=All&seeded=2&external=2&query=' .
						urlencode($keywords) . #not rawurlencode so space -> '+'
						'&uid=0&sort='
						) or returnServerError('Could not request Demonoid.');
					} else {
						$html = file_get_contents(
							self::URI .
							'files/?category=' .
							rawurlencode($catNum) .
							'&subcategory=All&quality=All&seeded=2&external=2&query=' .
							urlencode($keywords) . #not rawurlencode so space -> '+'
							'&uid=0&sort='
							) or returnServerError('Could not request Demonoid.');
					}
					break;
				case 'usr':
				$html = file_get_contents(
					self::URI .
					'files/?uid=' .
					rawurlencode($keywords) .
					'&seeded=2'
					) or returnServerError('Could not request Demonoid.');
					break;
				case 'cat':
				$html = file_get_contents(
					self::URI .
					'files/?uid=0&category=' .
					rawurlencode($keywords) .
					'&subcategory=0&language=0&seeded=2&quality=0&query=&sort='
					) or returnServerError('Could not request Demonoid.');
					break;
			}

			if(preg_match('~No torrents found~', $html)) {
				returnServerError('No result for query ' . $keywords);
			}

			$bigTable = explode('<!-- start torrent list -->', $html)[1];
			$last50 = explode('<!-- end torrent list -->', $bigTable)[0];
			$dateChunk = explode('added_today', $last50);
			$item = array ();

			for($block = 1;$block < count($dateChunk);$block++) {
				preg_match('~(?<=>Add).*?(?=<)~', $dateChunk[$block], $dateStr);
				if(preg_match('~today~', $dateStr[0])) {
					date_default_timezone_set('UTC');
					$timestamp = mktime(0, 0, 0, gmdate('n'), gmdate('j'), gmdate('Y'));
				}	else {
					preg_match('~(?<=ed on ).*\d+~', $dateStr[0], $fullDateStr);
					date_default_timezone_set('UTC');
					$dateObj = strptime($fullDateStr[0], '%A, %b %d, %Y');
					$timestamp = mktime(0, 0, 0, $dateObj['tm_mon'] + 1, $dateObj['tm_mday'], 1900 + $dateObj['tm_year']);
				}

				$itemsChunk = explode('<!-- tstart -->', $dateChunk[$block]);

				for($items = 1;$items < count($itemsChunk);$items++) {
					$item = array();
					$cols = explode('<td', $itemsChunk[$items]);
					preg_match('~(?<=href=\"/).*?(?=\")~', $cols[1], $matches);
					$item['id'] = self::URI . $matches[0];
					preg_match('~(?<=href=\").*?(?=\")~', $cols[4], $matches);
					$item['uri'] = $matches[0];
					$item['timestamp'] = $timestamp;
					preg_match('~(?<=href=\"/users/).*?(?=\")~', $cols[3], $matches);
					$item['author'] = $matches[0];
					preg_match('~(?<=/\">).*?(?=</a>)~', $cols[1], $matches);
					$item['title'] = $matches[0];
					preg_match('~(?<=green\">)\d+(?=</font>)~', $cols[8], $matches);
					$item['seeders'] = $matches[0];
					preg_match('~(?<=red\">)\d+(?=</font>)~', $cols[9], $matches);
					$item['leechers'] = $matches[0];
					preg_match('~(?<=>).*?(?=</td>)~', $cols[5], $matches);
					$item['size'] = $matches[0];
					$item['content'] = 'Uploaded by ' . $item['author']
					. ' , Size ' . $item['size']
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
