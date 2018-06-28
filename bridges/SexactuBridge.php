<?php
class SexactuBridge extends BridgeAbstract {

	const MAINTAINER = 'Riduidel';
	const NAME = 'Sexactu';
	const AUTHOR = 'Maïa Mazaurette';
	const URI = 'http://www.gqmagazine.fr';
	const CACHE_TIMEOUT = 7200; // 2h
	const DESCRIPTION = 'Sexactu via rss-bridge';

	const REPLACED_ATTRIBUTES = array(
			'href' => 'href',
			'src' => 'src',
			'data-original' => 'src'
	);

	public function getURI(){
		return self::URI . '/sexactu';
	}

	public function collectData(){
		$html = getSimpleHTMLDOM($this->getURI())
			or returnServerError('Could not request ' . $this->getURI());

		$sexactu = $html->find('.container_sexactu', 0);
		$rowList = $sexactu->find('.row');
		foreach($rowList as $row) {
			// only use first list as second one only contains pages numbers

			$title = $row->find('.title', 0);
			if($title) {
				$item = array();
				$item['author'] = self::AUTHOR;
				$item['title'] = $title->plaintext;
				$urlAttribute = 'data-href';
				$uri = $title->$urlAttribute;
				if($uri === false)
					continue;
				if(substr($uri, 0, 1) === 'h') { // absolute uri
					$item['uri'] = $uri;
				} else if(substr($uri, 0, 1) === '/') { // domain relative url
					$item['uri'] = self::URI . $uri;
				} else {
					$item['uri'] = $this->getURI() . $uri;
				}
				$article = $this->loadFullArticle($item['uri']);
				$item['content'] = $this->replaceUriInHtmlElement($article->find('.article_content', 0));

				$publicationDate = $article->find('time[itemprop=datePublished]', 0);
				$short_date = $publicationDate->datetime;
				$item['timestamp'] = strtotime($short_date);
			} else {
				// Sometimes we get rubbish, ignore.
				continue;
			}
			$this->items[] = $item;
		}
	}

	/**
	 * Loads the full article and returns the contents
	 * @param $uri The article URI
	 * @return The article content
	 */
	private function loadFullArticle($uri){
		$html = getSimpleHTMLDOMCached($uri);

		$content = $html->find('#article', 0);
		if($content) {
			return $content;
		}

		return null;
	}

	/**
	 * Replaces all relative URIs with absolute ones
	 * @param $element A simplehtmldom element
	 * @return The $element->innertext with all URIs replaced
	 */
	private function replaceUriInHtmlElement($element){
		$returned = $element->innertext;
		foreach (self::REPLACED_ATTRIBUTES as $initial => $final) {
			$returned = str_replace($initial . '="/', $final . '="' . self::URI . '/', $returned);
		}
		return $returned;
	}
}
