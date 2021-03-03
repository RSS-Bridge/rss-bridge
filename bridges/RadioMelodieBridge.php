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
		foreach($list as $element) {
			if($element->tag == 'a') {
				$articleURL = self::URI . $element->href;
				$article = getSimpleHTMLDOM($articleURL);
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

				$item['enclosures'] = array_merge($picture, $audio);
				$item['author'] = $author;
				$item['uri'] = $articleURL;
				$item['title'] = $article->find('meta[property=og:title]', 0)->content;
				$date = $article->find('p[class*=date]', 0)->plaintext;

				// Header Image
				$header = '<img src="' . $picture[0] . '"/>';

				// Remove the Date and Author part
				$textDOM->find('div[class=AuthorDate]', 0)->outertext = '';
				$article->save();
				$text = $textDOM->innertext;
				$item['content'] = '<h1>' . $item['title'] . '</h1>' . $date . '<br/>' . $header . $text;
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
}
