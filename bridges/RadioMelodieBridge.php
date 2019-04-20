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
		$list = $html->find('div[class=actu_col1]', 0)->children();;
		foreach($list as $element) {
			if($element->tag == 'a') {
				$articleURL = self::URI . $element->href;
				$article = getSimpleHTMLDOM($articleURL);

				// Initialise arrays
				$item = array();
				$audio = array();
				$picture = array();

				// Get the Main picture URL
				$picture[] = $this->rewriteImage($article->find('img[id=picturearticle]', 0)->src);
				$audioHTML = $article->find('div[class=sm2-playlist-wrapper]');

				// Remove the audio placeholder under the Audio player with an <audio>
				// element and add the audio element to the enclosure
				foreach($audioHTML as $audioElement) {
					$audioURL = $audioElement->find('a', 0)->href;
					$audio[] = $audioURL;
					$audioElement->outertext = '<audio controls src="' . $audioURL . '"></audio>';
					$article->save();
				}

				// Rewrite pictures URL
				$imgs = $article->find('img[src^="https://www.radiomelodie.com/image.php]');
				foreach($imgs as $img) {
					$img->src = $this->rewriteImage($img->src);
					$article->save();
				}

				// Remove inline audio player HTML
				$inlinePlayers = $article->find('div[class*=sm2-main-controls]');
				foreach($inlinePlayers as $inlinePlayer) {
					$inlinePlayer->outertext = '';
					$article->save();
				}

				// Remove Google Ads
				$ads = $article->find('div[style^=margin:25px 0;  position:relative; height:auto;]');
				foreach($ads as $ad) {
					$ad->outertext = '';
					$article->save();
				}

				$author = $article->find('div[id=author]', 0)->find('span', 0)->plaintext;

				$item['enclosures'] = array_merge($picture, $audio);
				$item['author'] = $author;
				$item['uri'] = $articleURL;
				$item['title'] = $article->find('meta[property=og:title]', 0)->content;
				$date_category = $article->find('div[class*=date]', 0)->plaintext;
				$header = $article->find('a[class=fancybox]', 0)->innertext;
				$textDOM = $article->find('div[class=text_content]', 0);
				$textDOM->find('div[id=author]', 0)->outertext = '';
				$article->save();
				$text = $textDOM->innertext;
				$item['content'] = '<h1>' . $item['title'] . '</h1>' . $date_category . $header . $text;
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
		parse_str($parts[1], $params);
		return self::URI . '/' . $params['image'];

	}
}
