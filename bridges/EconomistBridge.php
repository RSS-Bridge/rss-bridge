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

		foreach($html->find('div.teaser') as $element) {

			$a = $element->find('a.headline-link', 0);
			$href = $a->href;

			if (substr($href, 0, 4) != 'http')
				$href = self::URI . $a->href;

			$full = getSimpleHTMLDOMCached($href);
			$article = $full->find('article', 0);
			$header = $article->find('span[itemprop="headline"]', 0);
			$headerimg = $article->find('div[itemprop="image"]', 0)->find('img', 0);
			$author = $article->find('p[itemprop="byline"]', 0);
			$time = $article->find('time', 0);
			$content = $article->find('div[itemprop="text"]', 0);
			$section = array( $article->find('strong[itemprop="articleSection"]', 0)->plaintext );

			// Author
			if ($author)
				$author = substr($author->innertext, 3, strlen($author));
			else
				$author = 'The Economist';

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

			$item = array();
			$item['title'] = $header->innertext;
			$item['uri'] = $href;
			$item['timestamp'] = strtotime($time->datetime);
			$item['author'] = $author;
			$item['categories'] = $section;

			$item['content'] = '<img style="max-width: 100%" src="'
				. $headerimg->src . '">' . $content->innertext;

			$this->items[] = $item;

			if (count($this->items) >= 10)
				break;
		}
	}
}
