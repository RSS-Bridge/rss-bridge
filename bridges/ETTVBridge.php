<?php
class ETTVBridge extends BridgeAbstract {

	const MAINTAINER = 'GregThib';
	const NAME = 'ETTV';
	const URI = 'https://www.ettv.tv/';
	const DESCRIPTION = 'Returns list of 20 latest torrents for a specific search.';
	const CACHE_TIMEOUT = 14400; // 4 hours

	const PARAMETERS = array( array(
		'query' => array(
			'name' => 'Keywords',
			'required' => true
		),
		'cat' => array(
			'type' => 'list',
			'name' => 'Category',
			'values' => array(
				'(ALL TYPES)' => '0',
				'Anime: Movies' => '73',
				'Anime: Dubbed/Subbed' => '74',
				'Anime: Others' => '75',
				'Books: Ebooks' => '53',
				'Books: Magazines' => '54',
				'Books: Comics' => '55',
				'Books: Audio' => '56',
				'Books: Others' => '68',
				'Games: Windows' => '57',
				'Games: Android' => '58',
				'Games: Others' => '71',
				'Movies: HD 1080p' => '1',
				'Movies: HD 720p' => '2',
				'Movies: UltraHD/4K' => '3',
				'Movies: XviD' => '42',
				'Movies: X264/H264' => '47',
				'Movies: 3D' => '49',
				'Movies: Dubs/Dual Audio' => '51',
				'Movies: CAM/TS' => '65',
				'Movies: BluRay Disc/Remux' => '66',
				'Movies: DVDR' => '67',
				'Movies: HEVC/x265' => '76',
				'Music: MP3' => '59',
				'Music: FLAC' => '60',
				'Music: Music Videos' => '61',
				'Music: Others' => '69',
				'Software: Windows' => '62',
				'Software: Android' => '63',
				'Software: Mac' => '64',
				'Software: Others' => '70',
				'TV: HD/X264/H264' => '41',
				'TV: SD/X264/H264' => '5',
				'TV: TV Packs' => '7',
				'TV: SD/XVID' => '50',
				'TV: Sport' => '72',
				'TV: HEVC/x265' => '77',
				'Unsorted: Unsorted' => '78'
			),
			'defaultValue' => '(ALL TYPES)'
		),
		'status' => array(
			'type' => 'list',
			'name' => 'Status',
			'values' => array(
				'Active Transfers' => '0',
				'Included Dead' => '1',
				'Only Dead' => '2'
			),
			'defaultValue' => 'Included Dead'
		),
		'lang' => array(
			'type' => 'list',
			'name' => 'Lang',
			'values' => array(
				'(ALL)' => '0',
				'Arabic' => '17',
				'Chinese ' => '10',
				'Danish' => '13',
				'Dutch' => '11',
				'English' => '1',
				'Finnish' => '18',
				'French' => '2',
				'German' => '3',
				'Greek' => '15',
				'Hindi' => '8',
				'Italian' => '4',
				'Japanese' => '5',
				'Korean' => '9',
				'Polish' => '14',
				'Russian' => '7',
				'Spanish' => '6',
				'Turkish' => '16'
			),
			'defaultValue' => '(ALL)'
		)
	));

	protected $results_link;

	public function collectData(){
		// No control on inputs, because all defaultValue are set
		$query_str = 'torrents-search.php';
		$query_str .= '?search=' . urlencode('+'.str_replace(' ', ' +', $this->getInput('query')));
		$query_str .= '&cat=' . $this->getInput('cat');
		$query_str .= '&incldead=' . $this->getInput('status');
		$query_str .= '&lang=' . $this->getInput('lang');
		$query_str .= '&sort=id&order=desc';

		// Get results page
		$this->results_link = self::URI . $query_str;
		$html = getSimpleHTMLDOM($this->results_link)
			or returnServerError('Could not request ' . $this->getName());

		// Loop on each entry
		foreach($html->find('table.table tr') as $element) {
			if($element->parent->tag == 'thead') continue;
			$entry = $element->find('td', 1)->find('a', 0);

			// retrieve result page to get more details
			$link = rtrim(self::URI, '/') . $entry->href;
			$page = getSimpleHTMLDOM($link)
				or returnServerError('Could not request page ' . $link);

			// get details & download links
			$details = $page->find('fieldset.download table', 0); // WHAT?? It should be the second one…
			$dllinks = $page->find('div#downloadbox table', 0);

			// fill item
			$item = array();
			$item['author'] = $details->children(6)->children(1)->plaintext;
			$item['title'] = $entry->title;
			$item['uri'] = $link;
			$item['timestamp'] = strtotime($details->children(7)->children(1)->plaintext);
			$item['content'] = '';
			$item['content'] .= '<br/><b>Name: </b>' . $details->children(0)->children(1)->innertext;
			$item['content'] .= '<br/><b>Lang: </b>' . $details->children(3)->children(1)->innertext;
			$item['content'] .= '<br/><b>Size: </b>' . $details->children(4)->children(1)->innertext;
			$item['content'] .= '<br/><b>Hash: </b>' . $details->children(5)->children(1)->innertext;
			foreach($dllinks->children(0)->children(1)->find('a') as $dl) {
				$item['content'] .= '<br/>' . $dl->outertext;
			}
			$item['content'] .= '<br/><br/>' . $details->children(1)->children(0)->innertext;
			$this->items[] = $item;
		}
	}

	public function getName(){
		if($this->getInput('query')) {
			return '[' . self::NAME . '] ' . $this->getInput('query');
		}

		return self::NAME;
	}

	public function getURI(){
		if(isset($this->results_link) && !empty($this->results_link)) {
			return $this->results_link;
		}

		return self::URI;
	}
}
