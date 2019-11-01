<?php

class RevolutBridge extends BridgeAbstract {

	const NAME = 'Revolut Blog';
	const URI = 'https://blog.revolut.com/';
	const DESCRIPTION = 'Returns recent blog posts from Revolut.';
	const MAINTAINER = 'dominik-th';

	public function getIcon() {
		return self::URI . 'favicon.png';
	}

	public function collectData() {
		$articleOverview = getSimpleHTMLDOM(self::URI . 'sitemap-posts.xml')
			or returnServerError('Error while downloading the website content');

		$articles = array_slice($articleOverview->find('url'), 0, 15);

		foreach($articles as $article) {
			$item = array();

			$item['uri'] = $article->find('loc', 0)->plaintext;
			$item['timestamp'] = $article->find('lastmod', 0)->plaintext;
			$item['enclosures'] = array(
				$article->find('image:loc', 0)->plaintext
			);

			$fullArticle = getSimpleHTMLDOMCached($item['uri'])
				or returnServerError('Error while downloading the full article');

			$item['author'] = $fullArticle
				->find('h4[class="author-card-name"] a', 0)
				->plaintext;
			$item['title'] = $fullArticle
				->find('h1[class="post-full-title"]', 0)
				->plaintext;

			$content = $fullArticle
				->find('section[class="post-full-content"]', 0);

			foreach($content->find('img') as $image) {
				$image->src = $this->generateAbsoluteUrl($image->src);
			}

			foreach($content->find('a') as $hyperlink) {
				$hyperlink->href = $this->generateAbsoluteUrl($hyperlink->href);
			}

			foreach($content->find('iframe') as $iframe) {
				$iframe->outertext = $this->generateYoutubeReplacement($iframe);
			}

			$item['content'] = $content->innertext;
			$this->items[] = $item;
		}
	}

	private function generateAbsoluteUrl($path) {
		if (filter_var($path, FILTER_VALIDATE_URL)) {
			return $path;
		} else {
			return self::URI . $path;
		}
	}

	private function generateYoutubeReplacement($iframe) {
		$embedUrl = $iframe->src;
		if (parse_url($embedUrl, PHP_URL_HOST) === 'www.youtube.com') {
			$urlParts = explode('/', parse_url($embedUrl, PHP_URL_PATH));
			$videoId = end($urlParts);
			$thumbnailUrl = 'https://img.youtube.com/vi/' . $videoId . '/0.jpg';
			$videoUrl = 'https://www.youtube.com/watch?v=' . $videoId;
			$videoReplacement = str_get_html('<a><img /></a>');
			$videoReplacement->find('a', 0)->href = $videoUrl;
			$videoReplacement->find('img', 0)->src = $thumbnailUrl;
			return $videoReplacement;
		}
		return $iframe->outertext;
	}
}
