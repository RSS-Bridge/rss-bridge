<?php
class NyaaTorrentsBridge extends BridgeAbstract {

	const MAINTAINER = 'ORelio';
	const NAME = 'NyaaTorrents';
	const URI = 'https://nyaa.si/';
	const DESCRIPTION = 'Returns the newest torrents, with optional search criteria.';
	const PARAMETERS = array(
		array(
			'f' => array(
				'name' => 'Filter',
				'type' => 'list',
				'values' => array(
					'No filter' => '0',
					'No remakes' => '1',
					'Trusted only' => '2'
				)
			),
			'c' => array(
				'name' => 'Category',
				'type' => 'list',
				'values' => array(
					'All categories' => '0_0',
					'Anime' => '1_0',
					'Anime - AMV' => '1_1',
					'Anime - English' => '1_2',
					'Anime - Non-English' => '1_3',
					'Anime - Raw' => '1_4',
					'Audio' => '2_0',
					'Audio - Lossless' => '2_1',
					'Audio - Lossy' => '2_2',
					'Literature' => '3_0',
					'Literature - English' => '3_1',
					'Literature - Non-English' => '3_2',
					'Literature - Raw' => '3_3',
					'Live Action' => '4_0',
					'Live Action - English' => '4_1',
					'Live Action - Idol/PV' => '4_2',
					'Live Action - Non-English' => '4_3',
					'Live Action - Raw' => '4_4',
					'Pictures' => '5_0',
					'Pictures - Graphics' => '5_1',
					'Pictures - Photos' => '5_2',
					'Software' => '6_0',
					'Software - Apps' => '6_1',
					'Software - Games' => '6_2',
				)
			),
			'q' => array(
				'name' => 'Keyword',
				'description' => 'Keyword(s)',
				'type' => 'text'
			)
		)
	);

	public function collectData() {

		// Build Search URL from user-provided parameters
		$search_url = self::URI . '?s=id&o=desc&'
		. http_build_query(array(
			'f' => $this->getInput('f'),
			'c' => $this->getInput('c'),
			'q' => $this->getInput('q')
		));

		// Retrieve torrent listing from search results, which does not contain torrent description
		$html = getSimpleHTMLDOM($search_url)
		or returnServerError('Could not request Nyaa: ' . $search_url);
		$links = $html->find('a');
		$results = array();
		foreach ($links as $link)
			if (strpos($link->href, '/view/') === 0 && !in_array($link->href, $results))
				$results[] = $link->href;
		if (empty($results) && empty($this->getInput('q')))
			returnServerError('No results from Nyaa: ' . $url, 500);

		//Process each item individually
		foreach ($results as $element) {

			//Limit total amount of requests
			if(count($this->items) >= 20) {
				break;
			}

			$torrent_id = str_replace('/view/', '', $element);

			//Ignore entries without valid torrent ID
			if ($torrent_id != 0 && ctype_digit($torrent_id)) {

				//Retrieve data for this torrent ID
				$item_uri = self::URI . 'view/' . $torrent_id;

				//Retrieve full description from torrent page
				if ($item_html = getSimpleHTMLDOMCached($item_uri)) {

					//Retrieve data from page contents
					$item_title = str_replace(' :: Nyaa', '', $item_html->find('title', 0)->plaintext);
					$item_desc = str_get_html(markdownToHtml($item_html->find('#torrent-description', 0)->innertext));
					$item_author = extractFromDelimiters($item_html->outertext, 'href="/user/', '"');
					$item_date = intval(extractFromDelimiters($item_html->outertext, 'data-timestamp="', '"'));

					//Retrieve image for thumbnail or generic logo fallback
					$item_image = $this->getURI() . 'static/img/avatar/default.png';
					foreach ($item_desc->find('img') as $img) {
						if (strpos($img->src, 'prez') === false) {
							$item_image = $img->src;
							break;
						}
					}

					//Build and add final item
					$item = array();
					$item['uri'] = $item_uri;
					$item['title'] = $item_title;
					$item['author'] = $item_author;
					$item['timestamp'] = $item_date;
					$item['enclosures'] = array($item_image);
					$item['content'] = $item_desc;
					$this->items[] = $item;
				}
			}
			$element = null;
		}
		$results = null;
	}
}
