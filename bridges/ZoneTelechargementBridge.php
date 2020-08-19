<?php
class ZoneTelechargementBridge extends BridgeAbstract {

	/*  This bridge was initally done for the Website Zone Telechargement,
	 *  but the website changed it's name and URL.
	 *  Therefore, the class name and filename does not correspond to the
	 *  name of the bridge. This permits to keep the same RSS Feed URL.
	 */

	const NAME = 'Zone Telechargement';
	const URI = 'https://www.zt-za.com/';
	const DESCRIPTION = 'Suivi de série sur Zone Telechargement';
	const MAINTAINER = 'sysadminstory';
	const PARAMETERS = array(
		'Suivre la publication des épisodes d\'une série en cours de diffusion' => array(
			'url' => array(
				'name' => 'URL de la série',
				'type' => 'text',
				'required' => true,
				'title' => 'URL d\'une série sans le https://www.zt-za.com/',
				'exampleValue' => 'telecharger-series/31079-halt-and-catch-fire-saison-4-french-hd720p.html'
			)
		)
	);

	// This is an URL that is not protected by robot protection
	const UNPROTECED_URI = 'https://www.zone-annuaire.com/';

	public function getIcon() {
		return self::URI . '/templates/Default/images/favicon.ico';
	}

	public function collectData(){
		$html = getSimpleHTMLDOM(self::UNPROTECED_URI . $this->getInput('url'))
			or returnServerError('Could not request Zone Telechargement.');

		// Get the TV show title
		$qualityselector = 'div[style=font-size: 18px;margin: 10px auto;color:red;font-weight:bold;text-align:center;]';
		$show = trim($html->find('div[class=smallsep]', 0)->next_sibling()->plaintext);
		$quality = trim(explode("\n", $html->find($qualityselector, 0)->plaintext)[0]);
		$this->showTitle = $show . ' ' . $quality;

		// Get the post content
		$linkshtml = $html->find('div[class=postinfo]', 0);

		$episodes = array();

		$list = $linkshtml->find('a');
		// Construct the tabble of episodes using the links
		foreach($list as $element) {
			// Retrieve episode number from link text
			$epnumber = explode(' ', $element->plaintext)[1];
			$hoster = $this->findLinkHoster($element);

			// Format the link and add the link to the corresponding episode table
			$episodes[$epnumber][] = '<a href="' . $element->href . '">' . $hoster . ' - '
				. $this->showTitle . ' Episode ' . $epnumber . '</a>';

		}

		// Finally construct the items array
		foreach($episodes as $epnum => $episode) {
			$item = array();
			// Add every link available in the episode table separated by a <br/> tag
			$item['content'] = implode('<br/>', $episode);
			$item['title'] = $this->showTitle . ' Episode ' . $epnum;
			// As RSS Bridge use the URI as GUID they need to be unique : adding a md5 hash of the title element
			// should geneerate unique URI to prevent confusion for RSS readers
			$item['uri'] = self::URI . $this->getInput('url') . '#' . hash('md5', $item['title']);
			// Insert the episode at the beginning of the item list, to show the newest episode first
			array_unshift($this->items, $item);
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

	private function findLinkHoster($element) {
		// The hoster name is one level higher than the link tag : get the parent element
		$element = $element->parent();
		//echo "PARENT : $element \n";
		$continue = true;
		// Walk through all elements in the reverse order until finding the one with a div and that is not a <br/>
		while(!($element->find('div', 0) != null && $element->tag != 'br')) {
			$element = $element->prev_sibling();
		}
		// Return the text of the div : it's the file hoster name !
		return $element->find('div', 0)->plaintext;

	}
}
