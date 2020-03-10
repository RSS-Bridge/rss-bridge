<?php
class GizmodoBridge extends FeedExpander {

	const MAINTAINER = 'polopollo';
	const NAME = 'Gizmodo';
	const URI = 'https://gizmodo.com';
	const CACHE_TIMEOUT = 1800; // 30min
	const DESCRIPTION = 'Returns the newest posts from Gizmodo.';

	protected function parseItem($item) {
		$item = parent::parseItem($item);

		$articleHtml = getSimpleHTMLDOMCached($item['uri'])
			or returnServerError('Could not request: ' . $item['uri']);

		$articleHtml = $this->stripTags($articleHtml);
		$articleHtml = $this->handleFigureTags($articleHtml);

		// Get header image
		$image = $articleHtml->find('meta[property="og:image"]', 0)->content;

		$item['content'] = $articleHtml->find('div.js_post-content', 0)->innertext;

		// Get categories
		$categories = explode(',', $articleHtml->find('meta[name="keywords"]', 0)->content);
		$item['categories'] = array_map('trim', $categories);

		$item['enclosures'][] = $articleHtml->find('meta[property="og:image"]', 0)->content;

		return $item;
	}

	public function collectData() {
		$this->collectExpandableDatas(self::URI . '/rss', 20);
	}

	private function stripTags($articleHtml) {

		foreach ($articleHtml->find('aside') as $index => $aside) {
			$articleHtml->find('aside', $index)->outertext = '';
		}

		foreach ($articleHtml->find('div.ad-unit') as $index => $aside) {
			$articleHtml->find('div.ad-unit', $index)->outertext = '';
		}

		return $articleHtml;
	}

	private function handleFigureTags($articleHtml) {

		foreach ($articleHtml->find('figure') as $index => $figure) {

			if (isset($figure->attr['data-id'])) {
				$id = $figure->attr['data-id'];
				$format = $figure->attr['data-format'];

			} else {
				$img = $figure->find('img', 0);
				$id = $img->attr['data-chomp-id'];
				$format = $img->attr['data-format'];
				$figure->find('div.img-permalink-sub-wrapper', 0)->style = '';
			}

			$imageUrl = 'https://i.kinja-img.com/gawker-media/image/upload/' . $id . '.' . $format;

			$figure->find('span', 0)->outertext = <<<EOD
<img src="{$imageUrl}">
EOD;

			$articleHtml->find('figure', $index)->outertext = $figure->outertext;
		}

		return $articleHtml;
	}
}
