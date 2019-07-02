<?php
class ShanaprojectBridge extends BridgeAbstract {
	const MAINTAINER = 'logmanoriginal';
	const NAME = 'Shanaproject Bridge';
	const URI = 'https://www.shanaproject.com';
	const DESCRIPTION = 'Returns a list of anime from the current Season Anime List';

	private $uri;

	public function getURI() {
		return isset($this->uri) ? $this->uri : parent::getURI();
	}

	public function collectData(){
		$html = $this->loadSeasonAnimeList();

		$animes = $html->find('div.header_display_box_info')
			or returnServerError('Could not find anime headers!');

		foreach($animes as $anime) {
			$this->items[] = array(
				'title' => $this->extractAnimeTitle($anime),
				'author' => $this->extractAnimeTitle($anime),
				'uri' => $this->extractAnimeUri($anime),
				'timestamp' => $this->extractAnimeTimestamp($anime),
				'content' => $this->buildAnimeContent($anime),
			);
		}
	}

	// Returns an html object for the Season Anime List (latest season)
	private function loadSeasonAnimeList(){

		$html = getSimpleHTMLDOM(self::URI . '/seasons')
			or returnServerError('Could not load \'seasons\' page!');

		$html = defaultLinkTo($html, self::URI . '/seasons');

		$season = $html->find('div.follows_menu > a', 1)
			or returnServerError('Could not find \'Season Anime List\'!');

		$html = getSimpleHTMLDOM($season->href)
			or returnServerError(
				'Could not load \'Season Anime List\' from \''
				. $season->innertext
				. '\'!'
			);

		$this->uri = $season->href;

		$html = defaultLinkTo($html, $season->href);

		return $html;

	}

	// Extracts the anime title
	private function extractAnimeTitle($anime){
		$title = $anime->find('a', 0)
			or returnServerError('Could not find anime title!');
		return trim($title->innertext);
	}

	// Extracts the anime URI
	private function extractAnimeUri($anime){
		$uri = $anime->find('a', 0)
			or returnServerError('Could not find anime URI!');
		return $uri->href;
	}

	// Extracts the anime release date (timestamp)
	private function extractAnimeTimestamp($anime){
		$timestamp = $anime->find('span.header_info_block', 1);

		if(!$timestamp) {
			return null;
		}

		return strtotime($timestamp->innertext);
	}

	// Extracts the anime studio name (author)
	private function extractAnimeAuthor($anime){
		$author = $anime->find('span.header_info_block', 2);

		if(!$author) {
			return null; // Sometimes the studio is unknown, so leave empty
		}

		return trim($author->innertext);
	}

	// Extracts the episode information (x of y released)
	private function extractAnimeEpisodeInformation($anime){
		$episode = $anime->find('div.header_info_episode', 0)
			or returnServerError('Could not find anime episode information!');
		return preg_replace('/\r|\n/', ' ', $episode->plaintext);
	}

	// Extracts the background image
	private function extractAnimeBackgroundImage($anime){
		// Getting the picture is a little bit tricky as it is part of the style.
		// Luckily the style is part of the parent div :)

		if(preg_match('/url\(\/\/([^\)]+)\)/i', $anime->parent->style, $matches)) {
			return $matches[1];
		}

		returnServerError('Could not extract background image!');
	}

	// Builds an URI to search for a specific anime (subber is left empty)
	private function buildAnimeSearchUri($anime){
		return self::URI
		. '/search/?title='
		. urlencode($this->extractAnimeTitle($anime))
		. '&subber=';
	}

	// Builds the content string for a given anime
	private function buildAnimeContent($anime){
		// We'll use a template string to place our contents
		return '<a href="'
		. $this->extractAnimeUri($anime)
		. '"><img src="http://'
		. $this->extractAnimeBackgroundImage($anime)
		. '" alt="'
		. htmlspecialchars($this->extractAnimeTitle($anime))
		. '" style="border: 1px solid black"></a><br><p>'
		. $this->extractAnimeEpisodeInformation($anime)
		. '</p><br><p><a href="'
		. $this->buildAnimeSearchUri($anime)
		. '">Search episodes</a></p>';
	}
}
