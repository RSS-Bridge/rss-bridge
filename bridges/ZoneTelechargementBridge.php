<?php
class ZoneTelechargementBridge extends BridgeAbstract {

	/*  This bridge was initally done for the Website Zone Telechargement,
	 *  but the website changed it's name and URL.
	 *  Therefore, the class name and filename does not correspond to the
	 *  name of the bridge. This permits to keep the same RSS Feed URL.
	 */

	const NAME = 'Zone Telechargement';
	const URI = 'https://www.zone-telechargement.cam/';
	const DESCRIPTION = 'Suivi de série sur Zone Telechargement';
	const MAINTAINER = 'sysadminstory';
	const PARAMETERS = array(
		'Suivre la publication des épisodes d\'une série en cours de diffusion' => array(
			'url' => array(
				'name' => 'URL de la série',
				'type' => 'text',
				'required' => true,
				'title' => 'URL d\'une série sans le https://www.zone-telechargement.cam/',
				'exampleValue' => 'telecharger-series/31079-halt-and-catch-fire-saison-4-french-hd720p.html'),
			'filter' => array(
				'name' => 'Type de contenu',
				'type' => 'list',
				'title' => 'Type de contenu à suivre : Téléchargement, Streaming ou les deux',
				'values' => array(
					'Streaming et Téléchargement' => 'both',
					'Téléchargement' => 'download',
					'Streaming' => 'streaming'
					),
				'defaultValue' => 'both'
			)
		)
	);

	// This is an URL that is not protected by robot protection for Direct Download
	const UNPROTECTED_URI = 'https://www.zone-telechargement.net/';

	// This is an URL that is not protected by robot protection for Streaming Links
	const UNPROTECTED_URI_STREAMING = 'https://zone-telechargement.stream/';

	// This function use curl library with curl as User Agent instead of
	// simple_html_dom to load the HTML content as the website has some captcha
	// request for other user agents
	private function loadURL($url){
		$header = array();
		$opts = array(CURLOPT_USERAGENT => 'curl/7.64.0');
		$html = getContents($url, $header, $opts)
			or returnServerError('Could not request Zone Telechargement.');
		return str_get_html($html);
	}

	public function getIcon(){
		return self::UNPROTECTED_URI . '/templates/Default/images/favicon.ico';
	}

	public function collectData(){
		$html = $this->loadURL(self::UNPROTECTED_URI . $this->getInput('url'));
		$filter = $this->getInput('filter');

		// Get the TV show title
		$qualityselector = 'div[style=font-size: 18px;margin: 10px auto;color:red;font-weight:bold;text-align:center;]';
		$show = trim($html->find('div[class=smallsep]', 0)->next_sibling()->plaintext);
		$quality = trim(explode("\n", $html->find($qualityselector, 0)->plaintext)[0]);
		$this->showTitle = $show . ' ' . $quality;

		$episodes = array();

		// Handle the Direct Download links
		if($filter == 'both' || $filter == 'download') {
			// Get the post content
			$linkshtml = $html->find('div[class=postinfo]', 0);

			$list = $linkshtml->find('a');
			// Construct the table of episodes using the links
			foreach($list as $element) {
				// Retrieve episode number from link text
				$epnumber = explode(' ', $element->plaintext)[1];
				$hoster = $this->findLinkHoster($element);

				// Format the link and add the link to the corresponding episode table
				$episodes[$epnumber]['ddl'][] = '<a href="' . $element->href . '">' . $hoster . ' - '
					. $this->showTitle . ' Episode ' . $epnumber . '</a>';

			}
		}

		// Handle the Streaming links
		if($filter == 'both' || $filter == 'streaming') {
			// Get the post content, on the dedicated streaming website
			$htmlstreaming = $this->loadURL(self::UNPROTECTED_URI_STREAMING . $this->getInput('url'));
			// Get the HTML element containing all the links
			$streaminglinkshtml = $htmlstreaming->find('p[style=background-color: #FECC00;]', 1)->parent()->next_sibling();
			// Get all streaming Links
			$liststreaming = $streaminglinkshtml->find('a');
			foreach($liststreaming as $elementstreaming) {
				// Retrieve the episode number from the link text
				$epnumber = explode(' ', $elementstreaming->plaintext)[1];

				// Format the link and add the link to the corresponding episode table
				$episodes[$epnumber]['streaming'][] = '<a href="' . $elementstreaming->href . '">'
					. $this->showTitle . ' Episode ' . $epnumber . '</a>';
			}
		}

		// Finally construct the items array
		foreach($episodes as $epnum => $episode) {
			// Handle the Direct Download links
			if(array_key_exists('ddl', $episode)) {
				$item = array();
				// Add every link available in the episode table separated by a <br/> tag
				$item['content'] = implode('<br/>', $episode['ddl']);
				$item['title'] = $this->showTitle . ' Episode ' . $epnum . ' - Téléchargement';
				// Generate an unique UID by hashing the item title to prevent confusion for RSS readers
				$item['uid'] = hash('md5', $item['title']);
				$item['uri'] = self::URI . $this->getInput('url');
				// Insert the episode at the beginning of the item list, to show the newest episode first
				array_unshift($this->items, $item);
			}
			// Handle the streaming link
			if(array_key_exists('streaming', $episode)) {
				$item = array();
				// Add every link available in the episode table separated by a <br/> tag
				$item['content'] = implode('<br/>', $episode['streaming']);
				$item['title'] = $this->showTitle . ' Episode ' . $epnum . ' - Streaming';
				// Generate an unique UID by hashing the item title to prevent confusion for RSS readers
				$item['uid'] = hash('md5', $item['title']);
				$item['uri'] = self::URI . $this->getInput('url');
				// Insert the episode at the beginning of the item list, to show the newest episode first
				array_unshift($this->items, $item);
			}
		}
	}

	public function getName() {
		switch($this->queriedContext) {
		case 'Suivre la publication des épisodes d\'une série en cours de diffusion':
			return $this->showTitle . ' - ' . self::NAME;
			break;
		default:
			return self::NAME;
		}
	}

	public function getURI() {
		switch($this->queriedContext) {
		case 'Suivre la publication des épisodes d\'une série en cours de diffusion':
			return self::URI . $this->getInput('url');
			break;
		default:
			return self::URI;
		}
	}

	private function findLinkHoster($element) {
		// The hoster name is one level higher than the link tag : get the parent element
		$element = $element->parent();
		// Walk through all elements in the reverse order until finding the one with a div and that is not a <br/>
		while(!($element->find('div', 0) != null && $element->tag != 'br')) {
			$element = $element->prev_sibling();
		}
		// Return the text of the div : it's the file hoster name !
		return $element->find('div', 0)->plaintext;

	}
}
