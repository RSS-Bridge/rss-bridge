<?php
class DevToBridge extends BridgeAbstract {

	const CONTEXT_BY_TAG = 'By tag';

	const NAME = 'dev.to Bridge';
	const URI = 'https://dev.to';
	const DESCRIPTION = 'Returns feeds for tags';
	const MAINTAINER = 'logmanoriginal';
	const CACHE_TIMEOUT = 10800; // 15 min.

	const PARAMETERS = array(
		self::CONTEXT_BY_TAG => array(
			'tag' => array(
				'name' => 'Tag',
				'type' => 'text',
				'required' => true,
				'title' => 'Insert your tag',
				'exampleValue' => 'python'
			),
			'full' => array(
				'name' => 'Full article',
				'type' => 'checkbox',
				'required' => false,
				'title' => 'Enable to receive the full article for each item',
				'defaultValue' => false
			)
		)
	);

	public function getURI() {
		switch($this->queriedContext) {
			case self::CONTEXT_BY_TAG:
				if($tag = $this->getInput('tag')) {
					return static::URI . '/t/' . urlencode($tag);
				}
				break;
		}

		return parent::getURI();
	}

	public function getIcon() {
		return 'https://practicaldev-herokuapp-com.freetls.fastly.net/assets/
apple-icon-5c6fa9f2bce280428589c6195b7f1924206a53b782b371cfe2d02da932c8c173.png';
	}

	public function collectData() {

		$html = getSimpleHTMLDOMCached($this->getURI())
			or returnServerError('Could not request ' . $this->getURI());

		$html = defaultLinkTo($html, static::URI);

		$articles = $html->find('div[class="single-article"]')
			or returnServerError('Could not find articles!');

		foreach($articles as $article) {

			if($article->find('[class*="cta"]', 0)) { // Skip ads
				continue;
			}

			$item = array();

			$item['uri'] = $article->find('a[id*=article-link]', 0)->href;
			$item['title'] = $article->find('h3', 0)->plaintext;

			// i.e. "Charlie Harrington・Sep 21"
			$item['timestamp'] = strtotime(explode('・', $article->find('h4 a', 0)->plaintext, 2)[1]);
			$item['author'] = explode('・', $article->find('h4 a', 0)->plaintext, 2)[0];

			// Profile image
			$item['enclosures'] = array($article->find('img', 0)->src);

			if($this->getInput('full')) {
				$fullArticle = $this->getFullArticle($item['uri']);
				$item['content'] = <<<EOD
<img src="{$item['enclosures'][0]}" alt="{$item['author']}">
<p>{$fullArticle}</p>
EOD;
			} else {
				$item['content'] = <<<EOD
<img src="{$item['enclosures'][0]}" alt="{$item['author']}">
<p>{$item['title']}</p>
EOD;
			}

			$item['categories'] = array_map(function($e){ return $e->plaintext; }, $article->find('div.tags span.tag'));

			$this->items[] = $item;
		}

	}

	private function getFullArticle($url) {
		$html = getSimpleHTMLDOMCached($url)
			or returnServerError('Unable to load article from "' . $url . '"!');

		$html = defaultLinkTo($html, static::URI);

		return $html->find('[id="article-body"]', 0);
	}

}
