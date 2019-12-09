<?php
class EconomistBridge extends BridgeAbstract {
	const NAME = 'The Economist: Latest Updates';
	const URI = 'https://www.economist.com';
	const DESCRIPTION = 'Fetches the latest updates from the Economist.';
	const MAINTAINER = 'thefranke';
	const CACHE_TIMEOUT = 3600; // 1h

	public function getIcon() {
		return 'https://www.economist.com/sites/default/files/econfinal_favicon.ico';
	}

	public function collectData() {
		$html = getSimpleHTMLDOM(self::URI . '/latest/')
			or returnServerError('Could not fetch latest updates form The Economist.');

		foreach($html->find('article') as $element) {

			$a = $element->find('a', 0);
			$href = self::URI . $a->href;
			$full = getSimpleHTMLDOMCached($href);
			$article = $full->find('article', 0);

			$header = $article->find('h1', 0);
			$author = $article->find('span[itemprop="author"]', 0);
			$time = $article->find('time[itemprop="dateCreated"]', 0);
			$content = $article->find('div[itemprop="description"]', 0);

			// Remove newsletter subscription box
			$newsletter = $content->find('div[class="newsletter-form__message"]', 0);
			if ($newsletter)
				$newsletter->outertext = '';

			$newsletterForm = $content->find('form', 0);
			if ($newsletterForm)
				$newsletterForm->outertext = '';

			// Remove next and previous article URLs at the bottom
			$nextprev = $content->find('div[class="blog-post__next-previous-wrapper"]', 0);
			if ($nextprev)
				$nextprev->outertext = '';

			$section = array( $article->find('h3[itemprop="articleSection"]', 0)->plaintext );

			$item = array();
			$item['title'] = $header->find('span', 0)->innertext . ': '
				. $header->find('span', 1)->innertext;

			$item['uri'] = $href;
			$item['timestamp'] = strtotime($time->datetime);
			$item['author'] = $author->innertext;
			$item['categories'] = $section;

			$item['content'] = '<img style="max-width: 100%" src="'
				. $a->find('img', 0)->src . '">' . $content->innertext;

			$this->items[] = $item;

			if (count($this->items) >= 10)
				break;
		}
	}
}
