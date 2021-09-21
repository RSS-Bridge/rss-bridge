<?php
class RadioMelodieBridge extends BridgeAbstract {
	const NAME = 'Radio Melodie Actu';
	const URI = 'https://www.radiomelodie.com';
	const DESCRIPTION = 'Retourne les actualités publiées par Radio Melodie';
	const MAINTAINER = 'sysadminstory';

	public function getIcon() {
		return self::URI . '/img/favicon.png';
	}

	public function collectData(){
		$html = getSimpleHTMLDOM(self::URI . '/actu/')
			or returnServerError('Could not request Radio Melodie.');
		$list = $html->find('div[class=displayList]', 0)->children();

		$dateFormat = '%A  %e %B %Y à %H:%M';
		// Set locale and Timezone to parse the date
		setlocale (LC_TIME, 'fr_FR.utf8');
		date_default_timezone_set('Europe/Paris');

		foreach($list as $element) {
			if($element->tag == 'a') {
				$articleURL = self::URI . $element->href;
				$article = getSimpleHTMLDOM($articleURL);
				$this->rewriteAudioPlayers($article);
				// Reload the modified content
				$article = str_get_html($article->save());
				$textDOM = $article->find('article', 0);

				// Initialise arrays
				$item = array();
				$audio = array();
				$picture = array();

				// Get the Main picture URL
				$picture[] = self::URI . $article->find('div[id=pictureTitleSupport]', 0)->find('img', 0)->src;
				$audioHTML = $article->find('audio');

				// Add the audio element to the enclosure
				foreach($audioHTML as $audioElement) {
					$audioURL = $audioElement->src;
					$audio[] = $audioURL;
				}

				// Rewrite pictures URL
				$imgs = $textDOM->find('img[src^="http://www.radiomelodie.com/image.php]');
				foreach($imgs as $img) {
					$img->src = $this->rewriteImage($img->src);
					$article->save();
				}

				// Remove Google Ads
				$ads = $article->find('div[class=adInline]');
				foreach($ads as $ad) {
					$ad->outertext = '';
					$article->save();
				}

				// Remove Radio Melodie Logo
				$logoHTML = $article->find('div[id=logoArticleRM]', 0);
				$logoHTML->outertext = '';
				$article->save();

				$author = $article->find('p[class=AuthorName]', 0)->plaintext;

				// Handle date to timestamp
				$dateHTML = $article->find('p[class=date]', 0)->plaintext;
				preg_match('/\| ([^-]*)( - .*|)$/', $dateHTML, $matches);
				$dateText = $matches[1];
				$dateArray = strptime($dateText, $dateFormat);
				$timestamp = mktime(
					$dateArray['tm_hour'],
					$dateArray['tm_min'],
					$dateArray['tm_sec'],
					$dateArray['tm_mon'] + 1,
					$dateArray['tm_mday'],
					$dateArray['tm_year'] + 1900
				);

				$item['enclosures'] = array_merge($picture, $audio);
				$item['author'] = $author;
				$item['uri'] = $articleURL;
				$item['title'] = $article->find('meta[property=og:title]', 0)->content;
				if($timestamp !== false) {
					$item['timestamp'] = $timestamp;
				}

				// Header Image
				$header = '<img src="' . $picture[0] . '"/>';

				// Remove the Date and Author part
				$textDOM->find('div[class=AuthorDate]', 0)->outertext = '';

				// Remove Facebook javascript
				$textDOM->find('script[src^=https://connect.facebook.net]', 0)->outertext = '';

				// Rewrite relative Links
				$textDOM = defaultLinkTo($textDOM, self::URI . '/');

				$article->save();
				//$this->rewriteAudioPlayers($textDOM);
				$text = $textDOM->innertext;
				$item['content'] = '<h1>' . $item['title'] . '</h1>' . $dateHTML . '<br/>' . $header . $text;
				$this->items[] = $item;
			}
		}
	}

	/*
	 * Function to rewrite image URL to use the real Image URL and not the resized one (which is very slow)
	 */
	private function rewriteImage($url)
	{
		$parts = explode('?', $url);
		parse_str(html_entity_decode($parts[1]), $params);
		return self::URI . '/' . $params['image'];

	}

	/*
	 * Function to rewrite Audio Players to use the <audio> tag and not the javascript audio player
	 */
	private function rewriteAudioPlayers($html)
	{
		// Find all audio Players
		$audioPlayers = $html->find('div[class=audioPlayer]');

		foreach($audioPlayers as $audioPlayer) {
			// Get the javascript content below the player
			$js = $audioPlayer->next_sibling();

			// Extract the audio file URL
			preg_match('/wavesurfer[0-9]+.load\(\'(.*)\'\)/m', $js->innertext, $urls);

			// Create the plain HTML <audio> content to play this audio file
			$content = '<audio style="width: 100%" src="' . $urls[1] . '" controls ></audio>';

			// Replace the <script> tag by the <audio> tag
			$js->outertext = $content;
			// Remove the initial Audio Player
			$audioPlayer->outertext = '';
		}

	}
}
